<?php

namespace RonasIT\Tests;

use Orchestra\Testbench\TestCase as BaseTest;
use RonasIT\Support\AutoDoc\AutoDocServiceProvider;

abstract class TestCase extends BaseTest
{
    protected $loadEnvironmentVariables = true;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            AutoDocServiceProvider::class,
        ];
    }
}
