<?php

namespace RonasIT\Support\Tests;

use Illuminate\Http\Request;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase as BaseTest;
use RonasIT\Support\AutoDoc\AutoDocServiceProvider;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class TestCase extends BaseTest
{
    public function tearDown(): void
    {
        parent::tearDown();

        $this->clearDirectory(__DIR__ . '/../storage', ['.gitignore']);
    }

    protected function getPackageProviders($app): array
    {
        return [
            AutoDocServiceProvider::class
        ];
    }

    protected function defineEnvironment($app)
    {
        $app->setBasePath(__DIR__ . '/..');
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

    protected function generateRequest($type, $uri, $data = [], $pathParams = [], $headers = []): Request
    {
        $realUri = $uri;

        foreach ($pathParams as $pathParam => $value) {
            $realUri = str_replace($pathParam, $value, $uri);
        }

        $symfonyRequest = SymfonyRequest::create(
            $this->prepareUrlForRequest($realUri),
            strtoupper($type),
            $data,
            [],
            [],
            $this->transformHeadersToServerVars($headers)
        );

        $request = Request::createFromBase($symfonyRequest);

        $request->setRouteResolver(function () use ($uri) {
            return Route::get($uri);
        });

        return $request;
    }

    protected function addGlobalPrefix($prefix = '/global')
    {
        parent::tearDown();

        putenv("SWAGGER_GLOBAL_PREFIX={$prefix}");

        $this->setUp();
    }
}
