<?php

namespace RonasIT\Support\Tests;

use Orchestra\Testbench\TestCase as BaseTest;
use RonasIT\Support\AutoDoc\AutoDocServiceProvider;

class TestCase extends BaseTest
{
    protected function getPackageProviders($app): array
    {
        return [
            AutoDocServiceProvider::class
        ];
    }

    protected function getJsonFixture($name)
    {
        return json_decode($this->getFixture("{$name}.json"), true);
    }

    protected function getFixture($name)
    {
        return file_get_contents($this->generateFixturePath($name));
    }

    protected function generateFixturePath($name): string
    {
        $testClass = last(explode('\\', get_class($this)));

        return __DIR__ . "/fixtures/{$testClass}/{$name}";
    }
}
