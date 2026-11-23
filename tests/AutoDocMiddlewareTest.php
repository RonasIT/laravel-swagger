<?php

namespace RonasIT\AutoDoc\Tests;

use RonasIT\AutoDoc\Http\Middleware\AutoDocMiddleware;
use RonasIT\AutoDoc\Tests\Support\Traits\SwaggerServiceMockTrait;

class AutoDocMiddlewareTest extends TestCase
{
    use SwaggerServiceMockTrait;

    public function testHandle()
    {
        $this->mockDriverGetEmptyAndSaveProcessTmpData($this->getJsonFixture('tmp_data_search_roles_request'));

        $request = $this->generateGetRolesRequest();

        $middleware = new AutoDocMiddleware();

        $middleware->handle($request, function () {
            return $this->generateResponse('example_success_search_roles_response.json');
        });
    }

    public function testHandleTargetEnvironmentNotDisplayed()
    {
        config(['auto-doc.target_environments' => ['production']]);

        $request = $this->generateGetRolesRequest();

        $middleware = new AutoDocMiddleware();

        $middleware->handle($request, function () {
            return $this->generateResponse('example_success_search_roles_response.json');
        });

        $this->assertFileDoesNotExist(storage_path('temp_documentation.json'));
    }
}
