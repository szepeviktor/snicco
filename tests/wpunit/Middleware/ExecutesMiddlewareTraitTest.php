<?php


	namespace Tests\wpunit\Middleware;

	use GuzzleHttp\Psr7;
	use GuzzleHttp\Psr7\Response as Psr7Response;
	use Mockery;
	use PHPUnit\Framework\TestCase;
	use WPEmerge\Application\GenericFactory;
	use WPEmerge\Middleware\ExecutesMiddlewareTrait;
	use WPEmerge\Contracts\RequestInterface;
	use Tests\wpunit\Routing\HttpKernelTestMiddlewareStub1;
	use Tests\wpunit\Routing\HttpKernelTestMiddlewareStub2;
	use Tests\wpunit\Routing\HttpKernelTestMiddlewareStub3;
	use Tests\wpunit\Routing\HttpKernelTestMiddlewareStubWithParameters;

	/**
	 * @coversDefaultClass \WPEmerge\Middleware\ExecutesMiddlewareTrait
	 */
	class ExecutesMiddlewareTraitTest extends TestCase {

		/**
		 * @var RequestInterface
		 */
		private $request;

		/**
		 * @var GenericFactory
		 */
		private $factory;

		/**
		 * @var ExecutesMiddlewareTraitImplementation
		 */
		private $subject;

		public function setUp() : void {

			parent::setUp();

			$this->request = Mockery::mock( RequestInterface::class );
			$this->factory = Mockery::mock( GenericFactory::class );
			$this->subject = new ExecutesMiddlewareTraitImplementation( $this->factory );
		}

		public function tearDown() : void {

			parent::tearDown();
			Mockery::close();

			unset( $this->request );
			unset( $this->factory );
			unset( $this->subject );
		}

		/**
		 * @covers ::executeMiddleware
		 */
		public function testExecuteMiddleware_EmptyList_CallsClosure() {

			$response = $this->subject->publicExecuteMiddleware(
				[],
				$this->request,
				function () {

					return ( new Psr7Response() )->withBody( Psr7\stream_for( 'Handler' ) );
				}
			);

			$this->assertEquals( 'Handler', $response->getBody()->read( 999 ) );
		}

		// /**
		//  * @covers ::executeMiddleware
		//  */
		// public function testExecuteMiddleware_ClassNames_CallsClassNamesFirstThenClosure() {
		//
		// 	$this->factory->shouldReceive( 'make' )
		// 	              ->andReturnUsing( function ( $class ) {
		//
		// 		              return new $class();
		// 	              } );
		//
		// 	$response = $this->subject->publicExecuteMiddleware(
		// 		[
		// 			[ HttpKernelTestMiddlewareStub1::class ],
		// 			[ HttpKernelTestMiddlewareStub2::class ],
		// 			[ HttpKernelTestMiddlewareStub3::class ],
		// 		],
		// 		$this->request,
		// 		function () {
		//
		// 			return ( new Psr7Response() )->withBody( Psr7\stream_for( 'Handler' ) );
		// 		}
		// 	);
		//
		// 	$this->assertEquals( 'FooBarBazHandler', $response->getBody()->read( 999 ) );
		// }
		//
		// /**
		//  * @covers ::executeMiddleware
		//  */
		// public function testExecuteMiddleware_ClassNameWithParameters_PassParameters() {
		//
		// 	$this->factory->shouldReceive( 'make' )
		// 	              ->andReturnUsing( function ( $class ) {
		//
		// 		              return new $class();
		// 	              } );
		//
		// 	$response = $this->subject->publicExecuteMiddleware(
		// 		[
		// 			[ HttpKernelTestMiddlewareStubWithParameters::class, 'Arg1', 'Arg2' ],
		// 		],
		// 		$this->request,
		// 		function () {
		//
		// 			return ( new Psr7Response() )->withBody( Psr7\stream_for( 'Handler' ) );
		// 		}
		// 	);
		//
		// 	$this->assertEquals( 'Arg1Arg2Handler', $response->getBody()->read( 999 ) );
		// }

	}


	class ExecutesMiddlewareTraitImplementation {

		use ExecutesMiddlewareTrait;

		protected $factory = null;

		public function __construct( $factory ) {

			$this->factory = $factory;
		}

		protected function makeMiddleware( $class ) {

			return $this->factory->make( $class );
		}

		public function publicExecuteMiddleware() {

			return call_user_func_array( [ $this, 'executeMiddleware' ], func_get_args() );
		}

	}
