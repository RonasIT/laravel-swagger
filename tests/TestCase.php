<?php

namespace RonasIT\Support\Tests;

use Illuminate\Filesystem\Filesystem;
use Orchestra\Testbench\TestCase as BaseTest;
use RonasIT\Support\AutoDoc\AutoDocServiceProvider;

class TestCase extends BaseTest
{
    public function tearDown(): void
    {
        parent::tearDown();

        $this->clearDirectory(__DIR__ . '/storage', ['.gitignore']);
    }

    protected function getPackageProviders($app): array
    {
        return [
            AutoDocServiceProvider::class
        ];
    }

    protected function defineEnvironment($app)
    {
        $app->useStoragePath(__DIR__ . '/storage');
    }

    protected function mockCLass($className, $methods = [])
    {
        return $this
            ->getMockBuilder($className)
            ->onlyMethods($methods)
            ->getMock();
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

    protected function clearDirectory($dirPath, $exceptPaths = [])
    {
        $fileSystem = new Filesystem();

        $files = $fileSystem->allFiles($dirPath);

        foreach ($files as $file) {
            if (!in_array($file->getFilename(), $exceptPaths)) {
                $fileSystem->delete($file->getRealPath());
            }
        }
    }
}
