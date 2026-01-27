<?php

namespace RonasIT\AutoDoc\Tests;

use RonasIT\AutoDoc\Http\Middleware\AutoDocMiddleware;
use RonasIT\AutoDoc\Tests\Support\Models\User;
use RonasIT\AutoDoc\Tests\Support\Resources\UserResource;
use RonasIT\AutoDoc\Tests\Support\Resources\UsersCollectionResource;
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

    public function testHandleResponseWithResource()
    {
        $this->mockDriverGetEmptyAndSaveProcessTmpData($this->getJsonFixture('response_with_resource'));

        $request = $this->generateGetRolesRequest();

        $resource = UserResource::make(User::factory()->make());

        (new AutoDocMiddleware())->handle($request, fn () => $resource->toResponse($request));
    }

    public function testHandleResponseWithResourceCollection()
    {
        $this->mockDriverGetEmptyAndSaveProcessTmpData($this->getJsonFixture('response_with_resource'));

        $request = $this->generateGetRolesRequest('users');

        $users = collect([User::factory()->make()]);

        $resource = UsersCollectionResource::make($users);

        (new AutoDocMiddleware())->handle($request, fn () => $resource->toResponse($request));
    }

}
