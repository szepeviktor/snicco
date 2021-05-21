<?php


	declare( strict_types = 1 );


	namespace Tests\unit\Http;

	use Mockery;
    use Tests\stubs\TestRequest;
    use Tests\UnitTest;
    use Tests\traits\CreateDefaultWpApiMocks;
    use Tests\traits\SetUpKernel;
    use Tests\stubs\Middleware\GlobalMiddleware;
    use Tests\stubs\Middleware\WebMiddleware;
	use WPEmerge\Application\ApplicationEvent;
	use WPEmerge\Events\BodySent;
	use WPEmerge\Events\HeadersSent;
    use WPEmerge\ExceptionHandling\Exceptions\InvalidResponseException;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Request;

	class HttpKernelTest extends UnitTest {

		use SetUpKernel;
        use CreateDefaultWpApiMocks;


        protected function beforeTestRun()
        {
            $this->router = $this->newRouter($c = $this->createContainer());
            $this->kernel = $this->newKernel($this->router, $c );
            ApplicationEvent::make($c);
            ApplicationEvent::fake();
            WP::setFacadeContainer($c);

        }

        protected function beforeTearDown()
        {
            ApplicationEvent::setInstance(null);
            WP::reset();
            Mockery::close();

        }

        /**
         *
         *
         *
         *
         * CONFIG: DEFAULT
         *
         *
         *
         *
         */

		/** @test */
		public function no_response_gets_send_when_no_route_matched() {

			$request = $this->createIncomingWebRequest( 'GET', '/foo' );

			$output = $this->runAndGetKernelOutput($request);

			$this->assertNothingSent($output);

		}

		/** @test */
		public function for_matching_request_headers_and_body_get_send() {


			$this->router->get( '/foo', function ( Request $request ) {

				return 'foo';

			});

			$this->router->loadRoutes();

			$request = $this->createIncomingWebRequest( 'GET', '/foo' );

			$this->assertBodySent('foo', $this->runAndGetKernelOutput($request));

		}

		/** @test */
		public function for_admin_requests_the_body_does_not_get_send_immediately () {


			$this->router->get( '/admin', function () {

				return 'foo';

			});

			$this->router->loadRoutes();

			$request = $this->createIncomingAdminRequest( 'GET', '/admin' );

			$this->assertNothingSent($this->runAndGetKernelOutput($request));

			ob_start();
			$this->kernel->sendResponseDeferred();
			$body = ob_get_clean();

			$this->assertBodySent('foo', $body);

		}

		/** @test */
		public function events_are_dispatched_when_a_headers_and_body_get_send () {

			$this->router->get( '/foo', function ( ) {

				return 'foo';

			} );

			$this->router->loadRoutes();

			$request = $this->createIncomingWebRequest( 'GET', '/foo' );
			$this->runAndGetKernelOutput($request);

			ApplicationEvent::assertDispatched(HeadersSent::class , function ($event) use ( $request ) {

				return $event->request = $request;


			});
			ApplicationEvent::assertDispatched(BodySent::class , function ($event) use ( $request ) {

				return $event->request = $request;


			});


		}

		/** @test */
		public function the_body_will_never_be_sent_when_the_kernel_did_not_receive_a_response_for_admin_requests() {

		    $this->router->loadRoutes();

			ob_start();
			$this->kernel->sendResponseDeferred();

			$this->assertNothingSent(ob_get_clean());

		}

		/** @test */
		public function when_a_route_matches_null_is_returned_to_WP_and_the_current_template_is_not_included () {

            $this->router->get( '/foo', function (  ) {

                return 'foo';

            } );

            $this->router->loadRoutes();

            $output = $this->runAndGetKernelOutput(

                $request_event = $this->createIncomingWebRequest( 'GET', '/foo' )

            );

            $this->assertOutput('foo', $output);
            $this->assertNull(  $request_event->default() );

		}

        /** @test */
        public function the_kernel_will_return_the_template_WP_tried_to_load_when_no_route_was_found() {

            $this->router->get( '/foo', function (  ) {

                //

            } );


            $this->router->loadRoutes();

            $output = $this->runAndGetKernelOutput(

                $request_event = $this->createIncomingWebRequest( 'GET', '/bar' )

            );

            $this->assertSame('', $output);
            $this->assertSame( 'wordpress.php', $request_event->default() );

        }

        /** @test */
        public function middleware_is_synced_to_the_router_and_run_before_a_matching_route() {

            $GLOBALS['test'][ WebMiddleware::run_times ] = 0;

            $this->kernel->setRouteMiddlewareAliases( [

                'web' => WebMiddleware::class,

            ] );
            $this->router->get( '/foo', function () {

                return 'foo';

            } )->middleware( 'web' );

            $this->router->loadRoutes();


            $request = $this->createIncomingWebRequest( 'GET', '/foo' );

            $output = $this->runAndGetKernelOutput($request);

            $this->assertOutput('foo', $output);
            $this->assertMiddlewareRunTimes(1 , WebMiddleware::class);

        }

        /**
         * @test
         *
         * NOTE: for web facing routes we always have a matching route which is the fallback controller.
         * This is needed because otherwise it would not be possible to create routes without url conditions
         *
         */
        public function the_kernel_does_not_run_global_middleware_when_not_matching_a_route() {

            $GLOBALS['test'][ GlobalMiddleware::run_times ] = 0;

            $request = $this->createIncomingAdminRequest( 'GET', 'foo' );

            $this->kernel->setMiddlewareGroups( [

                'global' => [ GlobalMiddleware::class ],

            ]);

            $this->router->loadRoutes();

            $this->assertOutput('', $this->runAndGetKernelOutput($request) );

            $this->assertMiddlewareRunTimes(0, GlobalMiddleware::class);

        }

        /** @test */
        public function global_middleware_is_only_run_once_when_a_route_matched() {

            $GLOBALS['test'][ GlobalMiddleware::run_times ] = 0;

            $this->kernel->setMiddlewareGroups([
                'global' => [ GlobalMiddleware::class]
            ]);
            $this->router->get( '/foo', function () {

                return 'foo';

            } );

            $this->router->loadRoutes();

            // matching request
            $request = $this->createIncomingWebRequest( 'GET', '/foo' );
            $this->assertOutput('foo', $this->runAndGetKernelOutput($request));
            $this->assertMiddlewareRunTimes(1 , GlobalMiddleware::class);

        }

        /** @test */
        public function global_middleware_can_be_disabled_for_testing_purposes () {

            $GLOBALS['test'][ GlobalMiddleware::run_times ] = 0;

            $this->kernel->setMiddlewareGroups( [
                'global' => [ GlobalMiddleware::class]
            ] );
            $this->router->get( '/foo', function () {

                return 'foo';

            } );

            $this->router->loadRoutes();


            $this->kernel->runInTestMode();
            $request = $this->createIncomingWebRequest( 'GET', '/foo' );


            $this->assertSame('foo', $this->runAndGetKernelOutput($request));
            $this->assertMiddlewareRunTimes(0 , GlobalMiddleware::class);

        }

        /** @test */
        public function an_invalid_response_returned_from_the_handler_will_lead_to_an_exception () {

            $this->router->get( '/foo', function ( ) {

                return 1;

            });

            $this->router->loadRoutes();


            $this->expectExceptionMessage('The response returned by the route action is not valid.');

            $this->kernel->run($this->createIncomingWebRequest('GET', '/foo'));


        }

        /**
         *
         *
         *
         *
         * CONFIG: ALWAYS WITH MIDDLEWARE
         *
         *
         *
         *
         */

        /** @test */
        public function the_kernel_will_always_run_global_middleware_even_when_not_matching_a_route() {

            $GLOBALS['test'][ GlobalMiddleware::run_times ] = 0;
            $GLOBALS['test'][ WebMiddleware::run_times ]    = 0;

            $this->kernel->alwaysWithGlobalMiddleware();
            $this->kernel->setMiddlewareGroups( [

                'global' => [ GlobalMiddleware::class ],
                'web'    => [ WebMiddleware::class ],

            ] );

            $this->router->loadRoutes();


            $request_event = $this->createIncomingWebRequest( 'GET', 'foo' );

            ob_start();
            $this->kernel->run($request_event);
            $this->assertSame('', ob_get_clean());


            $this->assertMiddlewareRunTimes( 1, GlobalMiddleware::class );
            $this->assertMiddlewareRunTimes( 0, WebMiddleware::class );


        }

        /** @test */
        public function for_matching_requests_global_middleware_will_not_be_run_again_by_the_router() {

            $GLOBALS['test'][ GlobalMiddleware::run_times ] = 0;
            $GLOBALS['test'][ WebMiddleware::run_times ]    = 0;

            $this->kernel->alwaysWithGlobalMiddleware();
            $this->kernel->setMiddlewareGroups( [

                'global' => [ GlobalMiddleware::class ],
                'web'    => [ WebMiddleware::class ],

            ] );

            $this->router->get( '/foo', function () {

                return 'FOO';

            } )->middleware( 'web' );

            $this->router->loadRoutes();


            ob_start();
            $this->kernel->run($this->createIncomingWebRequest( 'GET', 'foo' ));
            $this->assertSame('FOO', ob_get_clean());


            $this->assertMiddlewareRunTimes( 1, GlobalMiddleware::class );
            $this->assertMiddlewareRunTimes( 1, WebMiddleware::class );

        }



	}