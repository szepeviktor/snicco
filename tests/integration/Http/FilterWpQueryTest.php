<?php


    declare(strict_types = 1);


    namespace Tests\integration\Http;

    use Psr\Http\Message\ServerRequestInterface;
    use Tests\integration\IntegrationTest;
    use Tests\stubs\TestApp;
    use Tests\stubs\TestRequest;
    use WPEmerge\Events\IncomingWebRequest;
    use WPEmerge\Events\WpQueryFilterable;
    use WPEmerge\Http\HttpKernel;
    use WPEmerge\Http\Psr7\Request;

    class FilterWpQueryTest extends IntegrationTest
    {

        protected function setUp() : void
        {
            parent::setUp();
            $this->newTestApp(TEST_CONFIG);

        }

        /** @test */
        public function WP_QUERY_vars_can_be_filtered_by_a_route()
        {

            $query_vars = ['foo' => 'bar'];

            $request = TestRequest::from('GET', '/wpquery/foo');
            TestApp::container()->instance(ServerRequestInterface::class, $request);
            $after = apply_filters('request', $query_vars);


            $this->assertSame(['foo' => 'baz'], $after);

            $this->seeKernelOutput('FOO_QUERY', $request);

        }

        /** @test */
        public function the_query_can_ONLY_get_filtered_for_read_verbs () {

            $query_vars = ['foo' => 'bar'];

            // The route response to post but the event wont get dispatched.
            $request = TestRequest::from('POST', '/wpquery/post');
            TestApp::container()->instance(ServerRequestInterface::class, $request);
            $after = apply_filters('request', $query_vars);


            $this->assertSame(['foo' => 'bar'], $after);
            $this->seeKernelOutput('FOO_QUERY', $request);

        }

        /** @test */
        public function WP_QUERY_remains_unchanged_when_no_route_matches () {

            $query_vars = ['foo' => 'bar'];

            $request = TestRequest::from('GET', '/wpquery/bogus');
            TestApp::container()->instance(ServerRequestInterface::class, $request);
            $after = apply_filters('request', $query_vars);


            $this->assertSame(['foo' => 'bar'], $after);

        }

        /** @test */
        public function captured_route_params_get_passed_to_the_query_filter () {

            $query_vars = ['spain' => 'barcelona'];
            $request = TestRequest::from('GET', 'wpquery/teams/germany/dortmund');

            TestApp::container()->instance(ServerRequestInterface::class, $request);
            $after = apply_filters('request', $query_vars);

            $this->assertSame(['spain' => 'barcelona', 'germany' => 'dortmund'], $after);

        }

        /** @test */
        public function the_route_handler_does_not_get_run_when_filtering_WP_QUERY () {

            $query_vars = ['foo' => 'bar'];
            $request = TestRequest::from('GET', '/wpquery/assert-no-handler-run');

            TestApp::container()->instance(ServerRequestInterface::class, $request);
            $after = apply_filters('request', $query_vars);

            $this->assertSame(['foo' => 'baz'], $after);
            $this->expectOutputString('');

        }

        /** @test */
        public function its_possible_to_create_routes_that_ONLY_CHANGE_WP_QUERY_but_dont_have_a_route_action () {

            $query_vars = ['foo' => 'bar'];
            $request = TestRequest::from('GET', '/wpquery/do-nothing');


            TestApp::container()->instance(ServerRequestInterface::class, $request);
            $filter_WP_QUERY = apply_filters('request', $query_vars);


            $this->assertSame(['foo' => 'baz'], $filter_WP_QUERY);

            /** @var HttpKernel $kernel */
            $kernel = TestApp::resolve(HttpKernel::class);

            $this->expectOutputString('');

            $kernel->run(new IncomingWebRequest('wp.php', $request));


        }



    }