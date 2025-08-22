<?php

namespace RonasIT\AutoDoc\Tests;

use Illuminate\Http\Testing\File;
use PHPUnit\Framework\Attributes\DataProvider;
use RonasIT\AutoDoc\Exceptions\EmptyContactEmailException;
use RonasIT\AutoDoc\Exceptions\InvalidDriverClassException;
use RonasIT\AutoDoc\Exceptions\LegacyConfigException;
use RonasIT\AutoDoc\Exceptions\SwaggerDriverClassNotFoundException;
use RonasIT\AutoDoc\Exceptions\UnsupportedDocumentationViewerException;
use RonasIT\AutoDoc\Exceptions\WrongSecurityConfigException;
use RonasIT\AutoDoc\Services\SwaggerService;
use RonasIT\AutoDoc\Tests\Support\Mock\TestContract;
use RonasIT\AutoDoc\Tests\Support\Mock\TestNotificationSetting;
use RonasIT\AutoDoc\Tests\Support\Mock\TestRequest;
use RonasIT\AutoDoc\Tests\Support\Traits\SwaggerServiceMockTrait;
use stdClass;

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
        config(['auto-doc.drivers.local.class' => stdClass::class]);

        $this->expectException(InvalidDriverClassException::class);

        app(SwaggerService::class);
    }

    public function testResponseHeaderWithItems()
    {
        $this->mockDriverGetTmpData($this->getJsonFixture('documentation/array_response_header_with_items'));

        app(SwaggerService::class);
    }

    public function testFormData()
    {
        $this->mockDriverGetTmpData($this->getJsonFixture('documentation/formdata_request'));

        app(SwaggerService::class);
    }

    public static function getConstructorInvalidTmpData(): array
    {
        return [
            [
                'tmpDoc' => 'documentation/invalid_version',
                'fixture' => 'invalid_version.html',
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__array_parameter__no_items',
                'fixture' => 'invalid_format_array_parameter_no_items.html',
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__array_response_body__no_items',
                'fixture' => 'invalid_format_array_response_body_no_items.html',
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__array_response_header__no_items',
                'fixture' => 'invalid_format_array_response_header_no_items.html',
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__body_and_form_params',
                'fixture' => 'invalid_format_body_and_form_params.html',
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__duplicate_header_params',
                'fixture' => 'invalid_format_duplicate_header_params.html',
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__duplicate_path_params',
                'fixture' => 'invalid_format_duplicate_path_params.html',
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__duplicate_path_placeholders',
                'fixture' => 'invalid_format_duplicate_path_placeholders.html',
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__duplicate_operation_id',
                'fixture' => 'invalid_format_duplicate_operation_id.html',
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__duplicate_tag',
                'fixture' => 'invalid_format_duplicate_tag.html',
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__file_invalid_consumes',
                'fixture' => 'invalid_format_file_invalid_consumes.html',
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__file_no_consumes',
                'fixture' => 'invalid_format_file_no_consumes.html',
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__multiple_body_params',
                'fixture' => 'invalid_format_multiple_body_params.html',
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__no_path_params',
                'fixture' => 'invalid_format_no_path_params.html',
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__path_param_no_placeholder',
                'fixture' => 'invalid_format_path_param_no_placeholder.html',
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__invalid_value__path',
                'fixture' => 'invalid_format_invalid_value_path.html',
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__invalid_value__status_code',
                'fixture' => 'invalid_format_invalid_value_status_code.html',
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__invalid_value__parameter_in',
                'fixture' => 'invalid_format_invalid_value_parameter_in.html',
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__missing_field__paths',
                'fixture' => 'invalid_format_missing_field_paths.html',
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__missing_field__operation_responses',
                'fixture' => 'invalid_format_missing_field_operation_responses.html',
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__missing_field__parameter_in',
                'fixture' => 'invalid_format_missing_field_parameter_in.html',
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__missing_field__response_description',
                'fixture' => 'invalid_format_missing_field_response_description.html',
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__missing_field__definition_type',
                'fixture' => 'invalid_format_missing_field_definition_type.html',
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__missing_field__info_version',
                'fixture' => 'invalid_format_missing_field_info_version.html',
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__missing_field__items_type',
                'fixture' => 'invalid_format_missing_field_items_type.html',
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__missing_field__header_type',
                'fixture' => 'invalid_format_missing_field_header_type.html',
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__missing_field__tag_name',
                'fixture' => 'invalid_format_missing_field_tag_name.html',
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__missing_local_ref',
                'fixture' => 'invalid_format_missing_local_ref.html',
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__missing_external_ref',
                'fixture' => 'invalid_format_missing_external_ref.html',
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__missing_ref_file',
                'fixture' => 'invalid_format_missing_ref_file.html',
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__invalid_schema_type',
                'fixture' => 'invalid_format_invalid_schema_type.html',
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__missing_path_parameter',
                'fixture' => 'invalid_format_missing_path_parameter.html',
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__security_definition__type',
                'fixture' => 'invalid_format_security_definition_type.html',
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__security_definition__flow',
                'fixture' => 'invalid_format_security_definition_flow.html',
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__security_definition__in',
                'fixture' => 'invalid_format_security_definition_in.html',
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__request_body__invalid_content',
                'fixture' => 'invalid_format_request_body_invalid_content.html',
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__response__invalid_items',
                'fixture' => 'invalid_format_response_invalid_items.html',
            ],
        ];
    }

    #[DataProvider('getConstructorInvalidTmpData')]
    public function testGetDocFileContentInvalidTmpData(
        string $tmpDoc,
        string $fixture,
    ) {
        $this->mockDriverGetDocumentation($this->getJsonFixture($tmpDoc));

        $content = app(SwaggerService::class)->getDocFileContent();

        $this->assertEqualsFixture($fixture, $content['info']['description']);
    }

    public function testEmptyContactEmail()
    {
        config(['auto-doc.info.contact.email' => null]);

        $this->expectException(EmptyContactEmailException::class);

        app(SwaggerService::class);
    }

    public static function getAddEmptyData(): array
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
            [
                'security' => 'query',
                'savedTmpDataFixture' => 'tmp_data_request_with_empty_data_query',
            ],
        ];
    }

    #[DataProvider('getAddEmptyData')]
    public function testAddDataRequestWithEmptyDataLaravel(string $security, string $savedTmpDataFixture)
    {
        config([
            'auto-doc.security' => $security,
            'auto-doc.security_drivers' => [
                'laravel' => [
                    'name' => 'laravel',
                    'in' => 'cookie',
                    'type' => 'apiKey',
                ],
                'jwt' => [
                    'name' => 'Authorization',
                    'in' => 'header',
                    'type' => 'apiKey',
                ],
                'query' => [
                    'name' => 'api_key',
                    'in' => 'query',
                    'type' => 'apiKey',
                ],
            ]
        ]);

        $this->mockDriverGetEmptyAndSaveProcessTmpData([], $this->getJsonFixture($savedTmpDataFixture));

        app(SwaggerService::class);
    }

    public function testAddDataRequestWithEmptyDataAndInfo()
    {
        config(['auto-doc.info' => []]);

        $this->mockDriverGetEmptyAndSaveProcessTmpData(
            processTmpData: [],
            savedProcessTmpData: $this->getJsonFixture('tmp_data_request_with_empty_data_and_info'),
        );

        app(SwaggerService::class);
    }

    public static function getAddData(): array
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

    #[DataProvider('getAddData')]
    public function testAddData(?string $contentType, string $requestFixture, string $responseFixture)
    {
        $this->mockDriverGetEmptyAndSaveProcessTmpData($this->getJsonFixture($requestFixture));

        $service = app(SwaggerService::class);

        $request = $this->generateGetRolesRequest();

        $response = $this->generateResponse($responseFixture, 200, [
            'Content-type' => $contentType,
            'authorization' => 'Bearer some_token',
        ]);

        $service->addData($request, $response);
    }

    public function testAddDataRequestWithoutRuleType()
    {
        $this->mockDriverGetEmptyAndSaveProcessTmpData(
            $this->getJsonFixture('tmp_data_search_roles_request_without_rule_type')
        );

        $service = app(SwaggerService::class);

        $request = $this->generateGetRolesRequest('testRequestWithoutRuleType');

        $response = $this->generateResponse('example_success_roles_response.json');

        $service->addData($request, $response);
    }

    public function testAddDataRequestWithAnnotations()
    {
        $this->mockDriverGetEmptyAndSaveProcessTmpData(
            $this->getJsonFixture('tmp_data_search_roles_request_with_annotations')
        );

        $service = app(SwaggerService::class);

        $request = $this->generateGetRolesRequest('testRequestWithAnnotations');

        $response = $this->generateResponse('example_success_roles_response.json');

        $service->addData($request, $response);
    }

    public static function addDataWithSecurity(): array
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
            [
                'security' => 'query',
                'requestFixture' => 'tmp_data_search_roles_request_query_security',
            ],
        ];
    }

    #[DataProvider('addDataWithSecurity')]
    public function testAddDataWithSecurity(string $security, string $requestFixture)
    {
        config([
            'auto-doc.security' => $security,
            'auto-doc.security_drivers' => [
                'laravel' => [
                    'name' => 'laravel',
                    'in' => 'cookie',
                    'type' => 'apiKey',
                ],
                'jwt' => [
                    'name' => 'Authorization',
                    'in' => 'header',
                    'type' => 'apiKey',
                ],
                'query' => [
                    'name' => 'api_key',
                    'in' => 'query',
                    'type' => 'apiKey',
                ]
            ]
        ]);

        $this->mockDriverGetEmptyAndSaveProcessTmpData($this->getJsonFixture($requestFixture));

        $service = app(SwaggerService::class);

        $request = $this->generateGetRolesRequest();

        $response = $this->generateResponse('example_success_roles_response.json');

        $service->addData($request, $response);
    }

    public function testAddDataWithInvalidSecurity()
    {
        config(['auto-doc.security' => 'invalid']);

        $this->expectException(WrongSecurityConfigException::class);

        app(SwaggerService::class);
    }

    public function testAddDataWithPathParameters()
    {
        $this->mockDriverGetEmptyAndSaveProcessTmpData($this->getJsonFixture('tmp_data_get_user_request'));

        $service = app(SwaggerService::class);

        $request = $this->generateRequest(
            type: 'get',
            uri: 'users/{id}/assign-role/{role-id}',
            data: [
                'with' => ['role'],
                'with_likes_count' => true,
            ],
            pathParams: [
                'id' => 1,
                'role-id' => 5,
            ],
        );

        $response = $this->generateResponse('example_success_user_response.json', 200, [
            'Content-type' => 'application/json',
        ]);

        $service->addData($request, $response);
    }

    public function testAddDataWithTypeNameInResponse()
    {
        $this->mockDriverGetTmpData($this->getJsonFixture('tmp_data_get_user_request'));

        $service = app(SwaggerService::class);

        $request = $this->generateRequest(
            type: 'get',
            uri: 'users/{id}/assign-role/{role-id}',
            data: [
                'with' => ['role'],
                'with_likes_count' => true,
            ],
            pathParams: [
                'id' => 1,
                'role-id' => 5,
            ],
        );

        $response = $this->generateResponse('example_success_user_response.json', 200, [
            'Content-type' => 'application/json',
        ]);

        $service->addData($request, $response);
    }

    public function testAddDataClosureRequest()
    {
        config(['auto-doc.security' => 'jwt']);

        $this->mockDriverGetEmptyAndSaveProcessTmpData($this->getJsonFixture('tmp_data_search_roles_closure_request'));

        $service = app(SwaggerService::class);

        $request = $this->generateClosureRequest('get', 'users/roles', [
            'with' => ['users'],
        ]);

        $response = $this->generateResponse('example_success_roles_closure_response.json');

        $service->addData($request, $response);
    }

    public function testAddDataPostRequest()
    {
        config(['auto-doc.security' => 'jwt']);

        $this->mockDriverGetEmptyAndSaveProcessTmpData($this->getJsonFixture('tmp_data_post_user_request'));

        $service = app(SwaggerService::class);

        $request = $this->generateRequest(
            type: 'post',
            uri: 'users',
            data: [
                'users' => [1, 2],
                'query' => null,
            ],
            headers: [
                'authorization' => 'Bearer some_token',
            ],
        );

        $response = $this->generateResponse('example_success_users_post_response.json');

        $service->addData($request, $response);
    }

    public function testAddDataGlobalPostRequest()
    {
        $this->addGlobalPrefix();

        config(['auto-doc.security' => 'jwt']);

        $this->mockDriverGetEmptyAndSaveProcessTmpData($this->getJsonFixture('tmp_data_global_post_user_request'));

        $service = app(SwaggerService::class);

        $request = $this->generateRequest(
            type: 'post',
            uri: '/global/users',
            data: [
                'users' => [1, 2],
                'query' => null,
            ],
            headers: ['authorization' => 'Bearer some_token'],
        );

        $response = $this->generateResponse('example_success_users_post_response.json');

        $service->addData($request, $response);
    }

    public function testAddDataGlobalPostGlobalURIRequest()
    {
        $this->addGlobalPrefix();

        config(['auto-doc.security' => 'jwt']);

        $this->mockDriverGetEmptyAndSaveProcessTmpData($this->getJsonFixture('tmp_data_global_post_global_uri_request'));

        $service = app(SwaggerService::class);

        $request = $this->generateRequest(
            type: 'post',
            uri: '/global/global/',
            data: [
                'users' => [1, 2],
                'query' => null,
            ],
            headers: [
                'authorization' => 'Bearer some_token',
            ]);

        $response = $this->generateResponse('example_success_users_post_response.json');

        $service->addData($request, $response);
    }

    public function testAddDataToEarlyGeneratedDoc()
    {
        config(['auto-doc.security' => 'jwt']);

        $this->mockDriverGetPreparedAndSaveTmpData(
            getTmpData: $this->getJsonFixture('tmp_data_put_user_request'),
            saveTmpData: $this->getJsonFixture('tmp_data_put_user_request_with_early_generated_doc'),
        );

        $service = app(SwaggerService::class);

        $request = $this->generateRequest(
            type: 'patch',
            uri: 'users/{id}',
            data: [
                'name' => 'Ryan',
                'query' => null,
            ],
            pathParams: [
                'id' => 1,
            ],
            headers: [
                'authorization' => 'Bearer some_token',
            ],
        );

        $response = $this->generateResponse(null, 204);

        $service->addData($request, $response);
    }

    public function testAddDataPostRequestWithObjectParams()
    {
        config(['auto-doc.security' => 'jwt']);

        $this->mockDriverGetEmptyAndSaveProcessTmpData(
            $this->getJsonFixture('tmp_data_post_user_request_with_object_params')
        );

        $service = app(SwaggerService::class);

        $request = $this->generateRequest(
            type: 'post',
            uri: 'users',
            data: [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'license' => File::create('license.pdf'),
                'notification_settings' => new TestNotificationSetting([
                    'is_push_enabled' => true,
                    'is_email_enabled' => true,
                    'is_sms_enabled' => true,
                ]),
                'query' => null,
            ],
            headers: [
                'authorization' => 'Bearer some_token',
            ],
        );

        $response = $this->generateResponse('example_success_users_post_response.json');

        $service->addData($request, $response);
    }

    public function testAddDataWithNotExistsMethodOnController()
    {
        $this->mockDriverGetEmptyAndSaveProcessTmpData($this->getJsonFixture('tmp_data_get_user_request_without_request_class'));

        $service = app(SwaggerService::class);

        $request = $this->generateRequest(
            type: 'get',
            uri: 'users/{id}/assign-role/{role-id}',
            data: [
                'with' => ['role'],
                'with_likes_count' => true,
            ],
            pathParams: [
                'id' => 1,
                'role-id' => 5,
            ],
            controllerMethod: 'notExists'
        );

        $response = $this->generateResponse('example_success_user_response.json', 200, [
            'Content-type' => 'application/json',
        ]);

        $service->addData($request, $response);
    }

    public function testAddDataWithBindingInterface()
    {
        $this->app->bind(TestContract::class, TestRequest::class);
        $this->mockDriverGetEmptyAndSaveProcessTmpData($this->getJsonFixture('tmp_data_get_user_request'));

        $service = app(SwaggerService::class);

        $request = $this->generateRequest(
            type: 'get',
            uri: 'users/{id}/assign-role/{role-id}',
            data: [
                'with' => ['role'],
                'with_likes_count' => true,
            ],
            pathParams: [
                'id' => 1,
                'role-id' => 5,
            ],
            controllerMethod: 'testRequestWithContract'
        );

        $response = $this->generateResponse('example_success_user_response.json', 200, [
            'Content-type' => 'application/json',
        ]);

        $service->addData($request, $response);
    }

    public function testCutExceptions()
    {
        $this->mockDriverGetEmptyAndSaveProcessTmpData($this->getJsonFixture('tmp_data_create_user_request'));

        $service = app(SwaggerService::class);

        $request = $this->generateRequest('post', '/api/users', [
            'first_name' => 'andrey',
            'last_name' => 'voronin',
        ]);

        $response = $this->generateResponse('example_forbidden_user_response.json', 403, [
            'Content-type' => 'application/json',
        ]);

        $service->addData($request, $response);
    }

    public function testLimitResponseData()
    {
        config(['auto-doc.response_example_limit_count' => 1]);

        $this->mockDriverGetEmptyAndSaveProcessTmpData($this->getJsonFixture('tmp_data_search_users_request'));

        $service = app(SwaggerService::class);

        $request = $this->generateRequest('get', '/api/users');

        $response = $this->generateResponse('example_success_users_response.json', 200, [
            'Content-type' => 'application/json',
        ]);

        $service->addData($request, $response);
    }

    public function testAddDataWithoutBoundContract()
    {
        config(['auto-doc.response_example_limit_count' => 1]);

        $this->mockDriverGetEmptyAndSaveProcessTmpData($this->getJsonFixture('tmp_data_search_users_empty_request'));

        $service = app(SwaggerService::class);

        $request = $this->generateRequest(
            type: 'get',
            uri: '/api/users',
            controllerMethod: 'testRequestWithContract',
        );

        $response = $this->generateResponse('example_success_users_response.json', 200, [
            'Content-type' => 'application/json',
        ]);

        $service->addData($request, $response);
    }

    public function testSetInvalidDocumentationViewer()
    {
        config(['auto-doc.documentation_viewer' => 'invalid']);

        $this->expectException(UnsupportedDocumentationViewerException::class);
        $this->expectExceptionMessage(
            "The documentation viewer 'invalid' does not exists."
            . " Please check that the 'documentation_viewer' key of your auto-doc.php config has one of valid values."
        );

        app(SwaggerService::class);
    }

    public function testSetNullableDocumentationViewer()
    {
        config(['auto-doc.documentation_viewer' => null]);

        $this->expectException(UnsupportedDocumentationViewerException::class);
        $this->expectExceptionMessage(
            "The documentation viewer '' does not exists."
            . " Please check that the 'documentation_viewer' key of your auto-doc.php config has one of valid values."
        );

        app(SwaggerService::class);
    }

    public function testSaveProductionData()
    {
        $this->mockDriverSaveData();

        app(SwaggerService::class)->saveProductionData();
    }

    public function testAddDataDescriptionForRouteConditionals()
    {
        $this->mockDriverGetEmptyAndSaveProcessTmpData(
            $this->getJsonFixture('tmp_data_get_route_parameters_description')
        );

        $request = $this->generateRequest(
            type: 'get',
            uri: 'v{versions}/users/{id}/{some_string}/{uuid}/{withoutConditional}',
            routeConditions: [
                [
                    'method' => 'whereNumber',
                    'pathParam' => 'id',
                ],
                [
                    'method' => 'whereIn',
                    'pathParam' => 'some_string',
                    'values' => [
                        'first|second|last',
                    ],
                ],
                [
                    'method' => 'whereUuid',
                    'pathParam' => 'uuid',
                ],
                [
                    'method' => 'whereIn',
                    'pathParam' => 'versions',
                    'values' => [
                        '0.2|1|3.1',
                    ],
                ],
            ],
        );

        $response = $this->generateResponse('example_success_user_response.json');

        app(SwaggerService::class)->addData($request, $response);
    }

    public function testMergeTempDocumentation()
    {
        $this->mockParallelTestingToken();

        $this->fillTempFile($this->getFixture('tmp_data_post_user_request.json'));

        $this->mockDriverGetTmpData($this->getJsonFixture('tmp_data_search_users_empty_request'));

        $service = app(SwaggerService::class);

        $service->saveProductionData();

        $this->assertTempFileEqualsFixture('tmp_data_merged');
    }

    public function testMergeToEmptyTempDocumentation()
    {
        $this->mockParallelTestingToken();

        $this->fillTempFile('');

        $this->mockDriverGetTmpData($this->getJsonFixture('tmp_data_search_users_empty_request'));

        app(SwaggerService::class)->saveProductionData();

        $this->assertTempFileEqualsFixture('tmp_data_merged_to_empty_temp_documentation');
    }

    public function testAddDataWhenInvokableClass()
    {
        $this->mockDriverGetEmptyAndSaveProcessTmpData($this->getJsonFixture('tmp_data_get_user_request_invoke'));

        $request = $this->generateRequest(
            type: 'get',
            uri: 'users',
            controllerMethod: '__invoke',
        );

        $response = $this->generateResponse('example_success_user_response.json', 200, [
            'Content-type' => 'application/json',
        ]);

        app(SwaggerService::class)->addData($request, $response);
    }
}
