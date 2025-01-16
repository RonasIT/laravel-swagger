<?php

namespace RonasIT\AutoDoc\Tests;

use RonasIT\AutoDoc\Http\Middleware\AutoDocMiddleware;
use RonasIT\AutoDoc\Tests\Support\Traits\SwaggerServiceMockTrait;

class AutoDocMiddlewareTest extends TestCase
{
    use SwaggerServiceMockTrait;

    public function testHandle()
    {
        $this->mockDriverGetEmptyAndSaveTmpData($this->getJsonFixture('tmp_data_search_roles_request'));

        $request = $this->generateGetRolesRequest();

        $middleware = new AutoDocMiddleware();

        $middleware->handle($request, function () {
            return $this->generateResponse('example_success_search_roles_response.json');
        });
    }
}
