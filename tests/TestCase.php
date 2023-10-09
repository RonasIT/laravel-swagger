<?php

namespace RonasIT\Support\Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Orchestra\Testbench\TestCase as BaseTest;
use RonasIT\Support\AutoDoc\AutoDocServiceProvider;
use RonasIT\Support\Tests\Support\Mock\TestController;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response;

class TestCase extends BaseTest
{
    protected $globalExportMode = false;

    public function setUp(): void
    {
        parent::setUp();

        config(['auto-doc.info.contact.email' => 'your@mail.com']);
    }

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

    public function exportJson(string $fixtureName, $data): void
    {
        if ($data instanceof TestResponse) {
            $data = $data->json();
        }

        $this->exportContent(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), "{$fixtureName}.json");
    }

    protected function exportContent($content, string $fixtureName): void
    {
        file_put_contents($this->getFixturePath($fixtureName), $content);
    }

    public function getFixturePath(string $fixtureName): string
    {
        $class = get_class($this);
        $explodedClass = explode('\\', $class);
        $className = Arr::last($explodedClass);

        return base_path("tests/fixtures/{$className}/{$fixtureName}");
    }

    protected function getJsonFixture(string $name)
    {
        return json_decode($this->getFixture("{$name}.json"), true);
    }

    public function assertEqualsJsonFixture(string $fixtureName, $data, bool $exportMode = false): void
    {
        if ($exportMode || $this->globalExportMode) {
            $this->exportJson($fixtureName, $data);
        }

        $this->assertEquals($this->getJsonFixture($fixtureName), $data);
    }

    public function assertEqualsFixture(string $fixtureName, $data, bool $exportMode = false): void
    {
        if ($exportMode || $this->globalExportMode) {
            $this->exportContent($fixtureName, $data);
        }

        $this->assertEquals($this->getFixture($fixtureName), $data);
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

    protected function generateRequest($type, $uri, $data = [], $pathParams = [], $headers = [], $method = 'test'): Request
    {
        $request = $this->getBaseRequest($type, $uri, $data, $pathParams, $headers);

        return $request->setRouteResolver(function () use ($uri, $request, $method) {
            return Route::get($uri)
                ->setAction(['controller' =>  TestController::class . '@' . $method])
                ->bind($request);
        });
    }

    protected function generateGetRolesRequest($method = 'test'): Request
    {
        return $this->generateRequest('get', 'users/roles', [
            'with' => ['users']
        ], [], [
            'Content-type' => 'application/json'
        ], $method);
    }

    protected function generateResponse($fixture, int $status = 200, array $headers = []): Response
    {
        if (empty($headers)) {
            $headers = [
                'Content-type' => 'application/json',
                'authorization' => 'Bearer some_token'
            ];
        }

        return new Response(($fixture) ? $this->getFixture($fixture) : null, $status, $headers);
    }

    protected function generateClosureRequest($type, $uri, $data = [], $pathParams = [], $headers = []): Request
    {
        $request = $this->getBaseRequest($type, $uri, $data, $pathParams, $headers);

        return $request->setRouteResolver(function () use ($uri) {
            return Route::get($uri);
        });
    }

    protected function getBaseRequest($type, $uri, $data = [], $pathParams = [], $headers = []): Request
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

        return Request::createFromBase($symfonyRequest);
    }

    protected function addGlobalPrefix($prefix = '/global')
    {
        parent::tearDown();

        putenv("SWAGGER_GLOBAL_PREFIX={$prefix}");

        $this->setUp();
    }
}
