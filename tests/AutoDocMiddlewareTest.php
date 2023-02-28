<?php

namespace RonasIT\Support\Tests;

use RonasIT\Support\AutoDoc\Http\Middleware\AutoDocMiddleware;
use RonasIT\Support\Tests\Support\Traits\SwaggerServiceMockTrait;

class AutoDocMiddlewareTest extends TestCase
{
    use SwaggerServiceMockTrait;

    public function testHandle()
    {
        config(['auto-doc.security' => 'laravel']);

        $this->mockDriverGetEmptyAndSaveTpmData($this->getJsonFixture('tmp_data_search_roles_request'));

        $request = $this->generateGetRolesRequest();

        $middleware = new AutoDocMiddleware();

        $middleware->handle($request, function () {
            return $this->generateResponse('example_success_search_roles_response.json');
        });
    }
}
