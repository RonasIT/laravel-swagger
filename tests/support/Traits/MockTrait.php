<?php

namespace RonasIT\Tests\Support\Traits;

trait MockTrait
{
    protected function mockClass($className, $methods = [])
    {
        return $this
            ->getMockBuilder($className)
            ->onlyMethods($methods)
            ->getMock();
    }
}
