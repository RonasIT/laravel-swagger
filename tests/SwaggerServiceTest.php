<?php

namespace RonasIT\Support\Tests;

use Symfony\Component\HttpFoundation\Response;
use RonasIT\Support\AutoDoc\Services\SwaggerService;
use RonasIT\Support\AutoDoc\Exceptions\LegacyConfigException;
use RonasIT\Support\Tests\Support\Traits\SwaggerServiceMockTrait;
use RonasIT\Support\AutoDoc\Exceptions\InvalidDriverClassException;
use RonasIT\Support\AutoDoc\Exceptions\WrongSecurityConfigException;
use RonasIT\Support\AutoDoc\Exceptions\SwaggerDriverClassNotFoundException;

class SwaggerServiceTest extends TestCase
{
    use SwaggerServiceMockTrait;

    public function testConstructorInvalidConfigVersion()
    {
        config(['auto-doc.config_version' => '1.0']);

        $this->expectException(LegacyConfigException::class);

        app(SwaggerService::class);
    }

    public function testConstructorEmptyConfigVersion()
    {
        config(['auto-doc.config_version' => null]);

        $this->expectException(LegacyConfigException::class);

        app(SwaggerService::class);
    }

    public function testConstructorDriverClassNotExists()
    {
        config(['auto-doc.drivers.local.class' => 'NotExistsClass']);

        $this->expectException(SwaggerDriverClassNotFoundException::class);

        app(SwaggerService::class);
    }

    public function testConstructorDriverClassNotImplementsInterface()
    {
        config(['auto-doc.drivers.local.class' => TestCase::class]);

        $this->expectException(InvalidDriverClassException::class);

        app(SwaggerService::class);
    }

    public function testAddData()
    {
        $this->mockDriverSaveTmpData($this->getJsonFixture('tmp_data_search_roles_request'));

        $service = app(SwaggerService::class);

        $request = $this->generateRequest('get', 'users/roles', [
            'with' => ['users']
        ], [], [
            'Content-type' => 'application/json'
        ]);

        $response = new Response($this->getFixture('example_success_roles_response.json'), 200, [
            'Content-type' => 'application/json',
            'authorization' => 'Bearer some_token'
        ]);

        $service->addData($request, $response);
    }

    public function testAddDataWithJWTSecurity()
    {
        config(['auto-doc.security' => 'jwt']);

        $this->mockDriverSaveTmpData($this->getJsonFixture('tmp_data_search_roles_request_jwt_security'));

        $service = app(SwaggerService::class);

        $request = $this->generateRequest('get', 'users/roles', [
            'with' => ['users']
        ]);

        $response = new Response($this->getFixture('example_success_roles_response.json'), 200, [
            'Content-type' => 'application/json',
            'authorization' => 'Bearer some_token'
        ]);

        $service->addData($request, $response);
    }

    public function testAddDataWithLaravelSecurity()
    {
        config(['auto-doc.security' => 'laravel']);

        $this->mockDriverSaveTmpData($this->getJsonFixture('tmp_data_search_roles_request_laravel_security'));

        $service = app(SwaggerService::class);

        $request = $this->generateRequest('get', 'users/roles', [
            'with' => ['users']
        ]);

        $response = new Response($this->getFixture('example_success_roles_response.json'), 200, [
            'Content-type' => 'application/json',
            'authorization' => 'Bearer some_token'
        ]);

        $service->addData($request, $response);
    }

    public function testAddDataWithEmptySecurity()
    {
        config(['auto-doc.security' => 'invalid']);

        $this->expectException(WrongSecurityConfigException::class);

        app(SwaggerService::class);
    }

    public function testAddDataWithPathParameters()
    {
        $this->mockDriverSaveTmpData($this->getJsonFixture('tmp_data_get_user_request'));

        $service = app(SwaggerService::class);

        $request = $this->generateRequest('get', 'users/{id}/assign-role/{role-id}', [
            'with' => ['role'],
            'with_likes_count' => true
        ], [
            'id' => 1,
            'role-id' => 5
        ]);

        $response = new Response($this->getFixture('example_success_user_response.json'), 200, [
            'Content-type' => 'application/json'
        ]);

        $service->addData($request, $response);
    }
}
