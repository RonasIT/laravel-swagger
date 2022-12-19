<?php

namespace RonasIT\Support\Tests\Support\Traits;

trait MockTrait
{
    protected function mockCLass($className, $methods = [])
    {
        return $this
            ->getMockBuilder($className)
            ->onlyMethods($methods)
            ->getMock();
    }
}
