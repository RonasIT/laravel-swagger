<?php

namespace RonasIT\Support\Tests;

use Illuminate\Http\Testing\File;
use RonasIT\Support\AutoDoc\Exceptions\EmptyContactEmailException;
use RonasIT\Support\AutoDoc\Exceptions\InvalidDriverClassException;
use RonasIT\Support\AutoDoc\Exceptions\LegacyConfigException;
use RonasIT\Support\AutoDoc\Exceptions\SwaggerDriverClassNotFoundException;
use RonasIT\Support\AutoDoc\Exceptions\WrongSecurityConfigException;
use RonasIT\Support\AutoDoc\Services\SwaggerService;
use RonasIT\Support\Tests\Support\Mock\TestNotificationSetting;
use RonasIT\Support\Tests\Support\Traits\SwaggerServiceMockTrait;

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

    public function testEmptyContactEmail()
    {
        config(['auto-doc.contact.email' => null]);

        $this->expectException(EmptyContactEmailException::class);

        app(SwaggerService::class);
    }

    public function getAddEmptyData(): array
    {
        return [
            [
                'security' => 'laravel',
                'savedTmpDataFixture' => 'tmp_data_request_with_empty_data_laravel',
            ],
            [
                'security' => 'jwt',
                'savedTmpDataFixture' => 'tmp_data_request_with_empty_data_jwt',
            ],
        ];
    }

    /**
     * @dataProvider getAddEmptyData
     *
     * @param string $security
     * @param string $savedTmpDataFixture
     */
    public function testAddDataRequestWithEmptyDataLaravel(string $security, string $savedTmpDataFixture)
    {
        config([
            'auto-doc.security' => $security,
        ]);

        $this->mockDriverGetEmptyAndSaveTpmData([], $this->getJsonFixture($savedTmpDataFixture));

        app(SwaggerService::class);
    }

    public function testAddDataRequestWithEmptyDataAndInfo()
    {
        config(['auto-doc.info' => []]);

        $this->mockDriverGetEmptyAndSaveTpmData([], $this->getJsonFixture('tmp_data_request_with_empty_data_and_info'));

        app(SwaggerService::class);
    }

    public function getAddData(): array
    {
        return [
            [
                'contentType' => 'application/json',
                'requestFixture' => 'tmp_data_search_roles_request',
                'responseFixture' => 'example_success_roles_response.json',
            ],
            [
                'contentType' => 'application/pdf',
                'requestFixture' => 'tmp_data_search_roles_request_pdf',
                'responseFixture' => 'example_success_pdf_type_response.json',
            ],
            [
                'contentType' => 'text/plain',
                'requestFixture' => 'tmp_data_search_roles_request_plain_text',
                'responseFixture' => 'example_success_plain_text_type_response.json',
            ],
            [
                'contentType' => null,
                'requestFixture' => 'tmp_data_search_roles_request_plain_text',
                'responseFixture' => 'example_success_plain_text_type_response.json',
            ],
            [
                'contentType' => 'invalid/content-type',
                'requestFixture' => 'tmp_data_search_roles_request_invalid_content_type',
                'responseFixture' => 'example_success_roles_response_invalid_content_type.json',
            ],
        ];
    }

    /**
     * @dataProvider getAddData
     *
     * @param ?string $contentType
     * @param string $requestFixture
     * @param string $responseFixture
     */
    public function testAddData(?string $contentType, string $requestFixture, string $responseFixture)
    {
        $this->mockDriverGetEmptyAndSaveTpmData($this->getJsonFixture($requestFixture));

        $service = app(SwaggerService::class);

        $request = $this->generateGetRolesRequest();

        $response = $this->generateResponse($responseFixture, 200, [
            'Content-type' => $contentType,
            'authorization' => 'Bearer some_token'
        ]);

        $service->addData($request, $response);
    }

    public function testAddDataRequestWithoutRuleType()
    {
        $this->mockDriverGetEmptyAndSaveTpmData($this->getJsonFixture('tmp_data_search_roles_request_without_rule_type'));

        $service = app(SwaggerService::class);

        $request = $this->generateGetRolesRequest('testRequestWithoutRuleType');

        $response = $this->generateResponse('example_success_roles_response.json');

        $service->addData($request, $response);
    }

    public function testAddDataRequestWithAnnotations()
    {
        $this->mockDriverGetEmptyAndSaveTpmData($this->getJsonFixture('tmp_data_search_roles_request_with_annotations'));

        $service = app(SwaggerService::class);

        $request = $this->generateGetRolesRequest('testRequestWithAnnotations');

        $response = $this->generateResponse('example_success_roles_response.json');

        $service->addData($request, $response);
    }

    public function addDataWithSecurity(): array
    {
        return [
            [
                'security' => 'laravel',
                'requestFixture' => 'tmp_data_search_roles_request_laravel_security',
            ],
            [
                'security' => 'jwt',
                'requestFixture' => 'tmp_data_search_roles_request_jwt_security',
            ],
        ];
    }

    /**
     * @dataProvider addDataWithSecurity
     *
     * @param string $security
     * @param string $requestFixture
     */
    public function testAddDataWithJWTSecurity(string $security, string $requestFixture)
    {
        config(['auto-doc.security' => $security]);

        $this->mockDriverGetEmptyAndSaveTpmData($this->getJsonFixture($requestFixture));

        $service = app(SwaggerService::class);

        $request = $this->generateGetRolesRequest();

        $response = $this->generateResponse('example_success_roles_response.json');

        $service->addData($request, $response);
    }

    public function testAddDataWithoutInfo()
    {
        config(['auto-doc.info' => []]);

        $this->mockDriverGetEmptyAndSaveTpmData($this->getJsonFixture('tmp_data_search_roles_request_without_info'));

        $service = app(SwaggerService::class);

        $request = $this->generateGetRolesRequest();

        $response = $this->generateResponse('example_success_roles_response.json');

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
        $this->mockDriverGetEmptyAndSaveTpmData($this->getJsonFixture('tmp_data_get_user_request'));

        $service = app(SwaggerService::class);

        $request = $this->generateRequest('get', 'users/{id}/assign-role/{role-id}', [
            'with' => ['role'],
            'with_likes_count' => true
        ], [
            'id' => 1,
            'role-id' => 5
        ]);

        $response = $this->generateResponse('example_success_user_response.json', 200, [
            'Content-type' => 'application/json'
        ]);

        $service->addData($request, $response);
    }

    public function testAddDataClosureRequest()
    {
        config(['auto-doc.security' => 'jwt']);

        $this->mockDriverGetEmptyAndSaveTpmData($this->getJsonFixture('tmp_data_search_roles_closure_request'));

        $service = app(SwaggerService::class);

        $request = $this->generateClosureRequest('get', 'users/roles', [
            'with' => ['users']
        ]);

        $response = $this->generateResponse('example_success_roles_closure_response.json');

        $service->addData($request, $response);
    }

    public function testAddDataPostRequest()
    {
        config(['auto-doc.security' => 'jwt']);

        $this->mockDriverGetEmptyAndSaveTpmData($this->getJsonFixture('tmp_data_post_user_request'));

        $service = app(SwaggerService::class);

        $request = $this->generateRequest('post', 'users', [
            'users' => [1,2],
            'query' => null
        ], [], [
            'authorization' => 'Bearer some_token'
        ]);

        $response = $this->generateResponse('example_success_users_post_response.json');

        $service->addData($request, $response);
    }

    public function testAddDataToEarlyGeneratedDoc()
    {
        config(['auto-doc.security' => 'jwt']);

        $this->mockDriverGetPreparedAndSaveTpmData(
            $this->getJsonFixture('tmp_data_put_user_request'),
            $this->getJsonFixture('tmp_data_put_user_request_with_early_generated_doc')
        );

        $service = app(SwaggerService::class);

        $request = $this->generateRequest('patch', 'users/{id}', [
            'name' => 'Ryan',
            'query' => null
        ], [
            'id' => 1
        ], [
            'authorization' => 'Bearer some_token'
        ]);

        $response = $this->generateResponse(null, 204);

        $service->addData($request, $response);
    }

    public function testAddDataPostRequestWithObjectParams()
    {
        config(['auto-doc.security' => 'jwt']);

        $this->mockDriverGetEmptyAndSaveTpmData($this->getJsonFixture('tmp_data_post_user_request_with_object_params'));

        $service = app(SwaggerService::class);

        $request = $this->generateRequest('post', 'users', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'license' => File::create('license.pdf'),
            'notification_settings' => new TestNotificationSetting([
                'is_push_enabled' => true,
                'is_email_enabled' => true,
                'is_sms_enabled' => true
            ]),
            'query' => null
        ], [], [
            'authorization' => 'Bearer some_token'
        ]);

        $response = $this->generateResponse('example_success_users_post_response.json');

        $service->addData($request, $response);
    }

    public function testCutExceptions()
    {
        $this->mockDriverGetEmptyAndSaveTpmData($this->getJsonFixture('tmp_data_create_user_request'));

        $service = app(SwaggerService::class);

        $request = $this->generateRequest('post', '/api/users', [
            'first_name' => 'andrey',
            'last_name' => 'voronin'
        ]);

        $response = $this->generateResponse('example_forbidden_user_response.json', 403, [
            'Content-type' => 'application/json'
        ]);

        $service->addData($request, $response);
    }

    public function testLimitResponseData()
    {
        config(['auto-doc.response_example_limit_count' => 1]);

        $this->mockDriverGetEmptyAndSaveTpmData($this->getJsonFixture('tmp_data_search_users_request'));

        $service = app(SwaggerService::class);

        $request = $this->generateRequest('get', '/api/users');

        $response = $this->generateResponse('example_success_users_response.json', 200, [
            'Content-type' => 'application/json'
        ]);

        $service->addData($request, $response);
    }

    public function testAddDataWithoutBoundContract()
    {
        config(['auto-doc.response_example_limit_count' => 1]);

        $this->mockDriverGetEmptyAndSaveTpmData($this->getJsonFixture('tmp_data_search_users_empty_request'));

        $service = app(SwaggerService::class);

        $request = $this->generateRequest('get', '/api/users', [], [], [], 'testRequestWithContract');

        $response = $this->generateResponse('example_success_users_response.json', 200, [
            'Content-type' => 'application/json'
        ]);

        $service->addData($request, $response);
    }
}
