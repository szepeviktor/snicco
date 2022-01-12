<?php

declare(strict_types=1);

namespace Tests\Core\unit\Routing;

use stdClass;
use TypeError;
use InvalidArgumentException;
use Tests\Core\RoutingTestCase;
use Snicco\Core\Routing\Route\Route;
use Tests\Codeception\shared\UnitTest;
use Tests\Core\fixtures\Middleware\FooMiddleware;
use Snicco\Core\Routing\Condition\ConditionBlueprint;
use Tests\Core\fixtures\Conditions\TrueRouteCondition;
use Tests\Core\fixtures\Conditions\MaybeRouteCondition;
use Snicco\Core\Routing\Condition\AbstractRouteCondition;
use Tests\Core\fixtures\Controllers\Web\RoutingTestController;

final class RouteTest extends UnitTest
{
    
    /** @test */
    public function test_exception_if_path_does_not_start_with_forward_slash()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected route pattern to start with /.');
        Route::create('foobar', []);
    }
    
    /** @test */
    public function test_exception_for_bad_methods()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('bogus');
        
        $route = Route::create('/foo', Route::DELEGATE, 'foo_route', ['GET', 'bogus']);
    }
    
    /** @test */
    public function test_exception_if_controller_array_is_missing_method()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Controller class [foo] does not exist.');
        Route::create('/foo', ['foo']);
    }
    
    /** @test */
    public function test_exception_controller_class_is_not_a_string()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected controller class to be a string.');
        Route::create('/foo', [new stdClass(), 'foo']);
    }
    
    /** @test */
    public function test_exception_controller_method_is_not_a_string()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected controller method to be a string.');
        Route::create('/foo', [RoutingTestController::class, new stdClass()]);
    }
    
    /** @test */
    public function test_exception_if_controller_class_does_not_exist()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Controller class [Bogus] does not exist.');
        Route::create('/foo', ['Bogus', 'foo']);
    }
    
    /** @test */
    public function test_exception_if_controller_method_does_not_exist()
    {
        $this->expectException(InvalidArgumentException::class);
        $c = RoutingTestController::class;
        $this->expectExceptionMessage('The method ['.$c.'::bogus] is not callable.');
        Route::create('/foo', [RoutingTestController::class, 'bogus']);
    }
    
    /** @test */
    public function a_controller_shorthand_with_a_namespace_works()
    {
        $route = Route::create(
            '/foo',
            'RoutingTestController@static',
            null,
            ['GET'],
            RoutingTestCase::CONTROLLER_NAMESPACE
        );
        
        $this->assertSame([RoutingTestController::class, 'static'], $route->getController());
        
        $route = Route::create(
            '/foo',
            'RoutingTestController@static',
            null,
            ['GET'],
            RoutingTestCase::CONTROLLER_NAMESPACE.'\\'
        );
        
        $this->assertSame([RoutingTestController::class, 'static'], $route->getController());
    }
    
    /** @test */
    public function test_exception_if_name_starts_with_dot()
    {
        $this->expectException(InvalidArgumentException::class);
        
        Route::create('/foo', Route::DELEGATE, '.foo');
    }
    
    /** @test */
    public function an_invalid_route_shorthand_still_fails()
    {
        $this->expectException(InvalidArgumentException::class);
        $c = RoutingTestController::class;
        $this->expectExceptionMessage('The method ['.$c.'::bogus] is not callable.');
        
        $route = Route::create(
            '/foo',
            'RoutingTestController@bogus',
            null,
            ['GET'],
            RoutingTestCase::CONTROLLER_NAMESPACE
        );
    }
    
    /** @test */
    public function invokable_controllers_can_be_passed_with_only_the_class_name()
    {
        $route = Route::create('/foo', RoutingTestController::class);
        $this->assertSame([RoutingTestController::class, '__invoke'], $route->getController());
    }
    
    /** @test */
    public function a_route_name_will_be_generated_if_not_passed_explicitly()
    {
        $route = Route::create('/foo', $arr = [RoutingTestController::class, 'static']);
        
        $e = implode('@', $arr);
        
        $this->assertSame('/foo:'.$e, $route->getName());
        
        $route = Route::create('/foo', $arr, 'foo_route');
        $this->assertSame('foo_route', $route->getName());
    }
    
    /** @test */
    public function test_exception_if_duplicate_required_segment_names()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Route segment names have to be unique but 1 of them is duplicated."
        );
        
        $route = Route::create('/foo/{bar}/{bar}', Route::DELEGATE);
    }
    
    /** @test */
    public function test_exception_if_duplicate_optional_segment_names()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Route segment names have to be unique but 1 of them is duplicated."
        );
        
        $route = Route::create('/foo/{bar?}/{bar?}', Route::DELEGATE);
    }
    
    /** @test */
    public function test_exception_if_duplicate_required_and_optional_segment_names()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Route segment names have to be unique but 1 of them is duplicated."
        );
        
        $route = Route::create('/foo/{bar}/{bar?}', Route::DELEGATE);
    }
    
    /** @test */
    public function test_exception_if_requirements_are_added_for_missing_segment()
    {
        $route = $this->newRoute('/foo/{bar}');
        
        $route->requirements(['bar' => '\d+']);
        
        $this->expectExceptionMessage(
            'Expected one of the valid segment names: ["bar"]. Got: ["bogus"].'
        );
        $route->requirements(['bogus' => '\d+']);
    }
    
    /** @test */
    public function test_exception_if_requirements_are_overwritten()
    {
        $route = $this->newRoute('/foo/{bar}');
        
        $route->requirements(['bar' => '\d+']);
        $this->expectExceptionMessage(
            'Requirement for segment [bar] can not be overwritten.'
        );
        $route->requirements(['bar' => '\w+']);
    }
    
    /** @test */
    public function test_defaults_throws_exception_for_non_primitives()
    {
        $route = $this->newRoute();
        $route->defaults(['foo' => 'bar']);
        
        $this->expectExceptionMessage("A route default value has to be a primitive type.");
        
        $route->defaults(['foo' => new stdClass()]);
    }
    
    /** @test */
    public function test_conditions_throws_exceptions_for_bad_class()
    {
        $route = $this->newRoute();
        
        $route->condition(TrueRouteCondition::class);
        
        try {
            $route->condition(stdClass::class);
        } catch (InvalidArgumentException $e) {
            $this->assertStringStartsWith(
                sprintf(
                    'A condition has to be an instance of [%s].',
                    AbstractRouteCondition::class
                ),
                $e->getMessage()
            );
        }
    }
    
    /** @test */
    public function test_condition_throws_exception_for_duplicate_condition()
    {
        $route = $this->newRoute();
        
        $route->condition(TrueRouteCondition::class);
        
        try {
            $route->condition(TrueRouteCondition::class);
            $this->fail("Duplicate condition added.");
        } catch (InvalidArgumentException $e) {
            $this->assertStringStartsWith(
                sprintf(
                    "Condition [%s] was added twice to route [%s].",
                    TrueRouteCondition::class,
                    $route->getName()
                ),
                $e->getMessage()
            );
        }
    }
    
    /** @test */
    public function test_get_conditions()
    {
        $route = $this->newRoute();
        
        $route->condition(MaybeRouteCondition::class, true);
        
        $expected = new ConditionBlueprint(MaybeRouteCondition::class, [true]);
        
        $this->assertEquals([
            MaybeRouteCondition::class => $expected,
        ], $route->getConditions());
    }
    
    /** @test */
    public function test_middleware_throws_exceptions_for_non_strings()
    {
        $route = $this->newRoute();
        
        $this->expectException(TypeError::class);
        
        $route->middleware(['foo', new FooMiddleware()]);
    }
    
    /** @test */
    public function test_exception_if_duplicate_middleware_is_set()
    {
        $route = Route::create('/foo', Route::DELEGATE, 'foo_route');
        
        $route->middleware('foo');
        $route->middleware('bar');
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Middleware [foo] added twice to route [foo_route].");
        $route->middleware('foo');
    }
    
    /** @test */
    public function test_exception_if_duplicate_middleware_is_set_with_arguments()
    {
        $route = Route::create('/foo', Route::DELEGATE, 'foo_route');
        
        $route->middleware('foo:arg1');
        $route->middleware('bar');
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Middleware [foo] added twice to route [foo_route].');
        $route->middleware('foo:arg2');
    }
    
    /** @test */
    public function test_serialize()
    {
        $route = $this->newRoute();
        
        $array = serialize($route);
        
        $new_route = unserialize($array);
        
        $this->assertInstanceOf(Route::class, $new_route);
        
        $this->assertEquals($route, $new_route);
    }
    
    /** @test */
    public function test_matchesOnlyTrailing()
    {
        $route = $this->newRoute('/foo');
        $this->assertFalse($route->matchesOnlyWithTrailingSlash());
        
        $route = $this->newRoute('/foo/');
        $this->assertTrue($route->matchesOnlyWithTrailingSlash());
        
        $route = $this->newRoute('/foo/{bar}');
        $this->assertFalse($route->matchesOnlyWithTrailingSlash());
        
        $route = $this->newRoute('/foo/{bar}/');
        $this->assertTrue($route->matchesOnlyWithTrailingSlash());
        
        $route = $this->newRoute('/foo/{bar?}');
        $this->assertFalse($route->matchesOnlyWithTrailingSlash());
        
        $route = $this->newRoute('/foo/{bar?}/');
        $this->assertTrue($route->matchesOnlyWithTrailingSlash());
    }
    
    private function newRoute(string $path = '/foo') :Route
    {
        return Route::create($path, Route::DELEGATE);
    }
    
}