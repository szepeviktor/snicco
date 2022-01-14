<?php

declare(strict_types=1);

namespace Tests\Core\unit\Support;

use ReflectionFunctionAbstract;
use Snicco\Core\Utils\Reflection;
use Tests\Codeception\shared\UnitTest;

final class ReflectionTest extends UnitTest
{
    
    /** @test */
    public function test_getReflectionFunction_with_class()
    {
        $this->assertNull(Reflection::getReflectionFunction(NoConstructor::class));
        $reflection = Reflection::getReflectionFunction(ClassWithConstructor::class);
        $this->assertInstanceOf(ReflectionFunctionAbstract::class, $reflection);
        $this->assertSame('__construct', $reflection->getName());
        $this->assertSame('foo', $reflection->getParameters()[0]->getName());
    }
    
    /** @test */
    public function test_getReflectionFunction_with_closure()
    {
        $closure = function ($foo) {
        };
        
        $reflection = Reflection::getReflectionFunction($closure);
        $this->assertSame('foo', $reflection->getParameters()[0]->getName());
    }
    
    /** @test */
    public function test_getReflectionFunction_with_class_and_method()
    {
        $reflection =
            Reflection::getReflectionFunction([ClassWithConstructor::class, 'someMethod']);
        $this->assertSame('someMethod', $reflection->getName());
    }
    
}

class NoConstructor
{

}

class ClassWithConstructor
{
    
    public function __construct($foo)
    {
    }
    
    public function someMethod(string $foo, string $bar)
    {
    }
    
}