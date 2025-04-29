<?php

namespace RonasIT\AutoDoc\Tests;

use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\ParallelTesting;
use PHPUnit\Framework\Attributes\DataProvider;
use RonasIT\AutoDoc\Exceptions\EmptyContactEmailException;
use RonasIT\AutoDoc\Exceptions\InvalidDriverClassException;
use RonasIT\AutoDoc\Exceptions\LegacyConfigException;
use RonasIT\AutoDoc\Exceptions\SpecValidation\DuplicateFieldException;
use RonasIT\AutoDoc\Exceptions\SpecValidation\DuplicateParamException;
use RonasIT\AutoDoc\Exceptions\SpecValidation\DuplicatePathPlaceholderException;
use RonasIT\AutoDoc\Exceptions\SpecValidation\InvalidFieldValueException;
use RonasIT\AutoDoc\Exceptions\SpecValidation\InvalidPathException;
use RonasIT\AutoDoc\Exceptions\SpecValidation\InvalidStatusCodeException;
use RonasIT\AutoDoc\Exceptions\SpecValidation\InvalidSwaggerSpecException;
use RonasIT\AutoDoc\Exceptions\SpecValidation\InvalidSwaggerVersionException;
use RonasIT\AutoDoc\Exceptions\SpecValidation\MissingExternalRefException;
use RonasIT\AutoDoc\Exceptions\SpecValidation\MissingFieldException;
use RonasIT\AutoDoc\Exceptions\SpecValidation\MissingLocalRefException;
use RonasIT\AutoDoc\Exceptions\SpecValidation\MissingPathParamException;
use RonasIT\AutoDoc\Exceptions\SpecValidation\MissingPathPlaceholderException;
use RonasIT\AutoDoc\Exceptions\SpecValidation\MissingRefFileException;
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
                'exception' => InvalidSwaggerVersionException::class,
                'exceptionMessage' => "Unrecognized Swagger version '1.0'. Expected 3.1.0.",
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__array_parameter__no_items',
                'exception' => InvalidSwaggerSpecException::class,
                'exceptionMessage' => "Validation failed. paths./users.post.parameters.0 is an "
                    . "array, so it must include an 'items' field.",
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__array_response_body__no_items',
                'exception' => InvalidSwaggerSpecException::class,
                'exceptionMessage' => "Validation failed. paths./users.get.responses.200.schema is an array, "
                    . "so it must include an 'items' field.",
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__array_response_header__no_items',
                'exception' => InvalidSwaggerSpecException::class,
                'exceptionMessage' => "Validation failed. paths./users.get.responses.default.headers."
                    . "Last-Modified is an array, so it must include an 'items' field.",
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__body_and_form_params',
                'exception' => InvalidSwaggerSpecException::class,
                'exceptionMessage' => "Validation failed. Operation 'paths./users/{username}.post' "
                    . "has body and formData parameters. Only one or the other is allowed.",
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__duplicate_header_params',
                'exception' => DuplicateParamException::class,
                'exceptionMessage' => "Validation failed. Operation 'paths./users/{username}.get' "
                    . "has multiple in:header parameters with name:foo.",
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__duplicate_path_params',
                'exception' => DuplicateParamException::class,
                'exceptionMessage' => "Validation failed. Operation 'paths./users/{username}.get' has "
                    . "multiple in:path parameters with name:username",
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__duplicate_path_placeholders',
                'exception' => DuplicatePathPlaceholderException::class,
                'exceptionMessage' => "Validation failed. Path '/users/{username}/profile/{username}/image/{img_id}' "
                    . "has multiple path placeholders with name: username.",
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__duplicate_operation_id',
                'exception' => DuplicateFieldException::class,
                'exceptionMessage' => "Validation failed. Found multiple fields 'paths.*.*.operationId' "
                    . "with values: addPet.",
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__duplicate_tag',
                'exception' => DuplicateFieldException::class,
                'exceptionMessage' => "Validation failed. Found multiple fields 'tags.*.name' with values: user.",
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__file_invalid_consumes',
                'exception' => InvalidSwaggerSpecException::class,
                'exceptionMessage' => "Validation failed. Operation 'paths./users/{username}/profile/image.post' "
                    . "has body and formData parameters. Only one or the other is allowed.",
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__file_no_consumes',
                'exception' => InvalidSwaggerSpecException::class,
                'exceptionMessage' => "Validation failed. Operation 'paths./users/{username}/profile/image.post' "
                    . "has body and formData parameters. Only one or the other is allowed.",
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__multiple_body_params',
                'exception' => InvalidSwaggerSpecException::class,
                'exceptionMessage' => "Validation failed. Operation 'paths./users/{username}.get' has 2 body "
                    . "parameters. Only one is allowed.",
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__no_path_params',
                'exception' => MissingPathParamException::class,
                'exceptionMessage' => "Validation failed. Operation 'paths./users/{username}/{foo}.get' has "
                    . "no params for placeholders: username, foo.",
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__path_param_no_placeholder',
                'exception' => MissingPathPlaceholderException::class,
                'exceptionMessage' => "Validation failed. Operation 'paths./users/{username}.post' has no "
                    . "placeholders for params: foo.",
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__invalid_value__path',
                'exception' => InvalidPathException::class,
                'exceptionMessage' => "Validation failed. Incorrect 'paths.users'. Paths should only have path "
                    . "names that starts with `/`.",
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__invalid_value__status_code',
                'exception' => InvalidStatusCodeException::class,
                'exceptionMessage' => "Validation failed. Operation at 'paths./users.get.responses.8888' should "
                    . "only have three-digit status codes, `default`, and vendor extensions (`x-*`) as properties.",
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__invalid_value__parameter_in',
                'exception' => InvalidFieldValueException::class,
                'exceptionMessage' => "Validation failed. Field 'paths./auth/login.post.parameters.0.in' "
                    . "has an invalid value: invalid_in. Allowed values: body, formData, query, path, header.",
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__missing_field__paths',
                'exception' => MissingFieldException::class,
                'exceptionMessage' => "Validation failed. '' should have required fields: paths.",
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__missing_field__operation_responses',
                'exception' => MissingFieldException::class,
                'exceptionMessage' => "Validation failed. 'paths./auth/login.post' should have required "
                    . "fields: responses.",
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__missing_field__parameter_in',
                'exception' => MissingFieldException::class,
                'exceptionMessage' => "Validation failed. 'paths./auth/login.post.parameters.0' should "
                    . "have required fields: in.",
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__missing_field__response_description',
                'exception' => MissingFieldException::class,
                'exceptionMessage' => "Validation failed. 'paths./auth/login.post.responses.200' should "
                    . "have required fields: description.",
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__missing_field__definition_type',
                'exception' => MissingFieldException::class,
                'exceptionMessage' => "Validation failed. 'components.schemas.authloginObject' should have "
                    . "required fields: type.",
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__missing_field__info_version',
                'exception' => MissingFieldException::class,
                'exceptionMessage' => "Validation failed. 'info' should have required fields: version.",
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__missing_field__items_type',
                'exception' => MissingFieldException::class,
                'exceptionMessage' => "Validation failed. 'paths./pet/findByStatus.get.parameters.0.schema.items' "
                    . "should have required fields: type.",
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__missing_field__header_type',
                'exception' => MissingFieldException::class,
                'exceptionMessage' => "Validation failed. 'paths./user/login.get.responses.200.headers.X-Rate-Limit' "
                    . "should have required fields: type.",
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__missing_field__tag_name',
                'exception' => MissingFieldException::class,
                'exceptionMessage' => "Validation failed. 'tags.0' should have required fields: name.",
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__missing_local_ref',
                'exception' => MissingLocalRefException::class,
                'exceptionMessage' => "Validation failed. Ref 'loginObject' is used in \$ref but not defined "
                    . "in 'definitions' field.",
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__missing_external_ref',
                'exception' => MissingExternalRefException::class,
                'exceptionMessage' => "Validation failed. Ref 'authloginObject' is used in \$ref but not defined "
                    . "in 'tests/fixtures/SwaggerServiceTest/documentation/with_definitions.json' file.",
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__missing_ref_file',
                'exception' => MissingRefFileException::class,
                'exceptionMessage' => "Validation failed. Filename 'invalid-filename.json' is used in \$ref but "
                    . "file doesn't exist.",
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__invalid_schema_type',
                'exception' => InvalidFieldValueException::class,
                'exceptionMessage' => "Validation failed. Field 'paths./users.get.responses.200.schema.type' "
                    . "has an invalid value: something. Allowed values: array, boolean, integer, number, "
                    . "string, object, null, undefined, file.",
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__missing_path_parameter',
                'exception' => InvalidSwaggerSpecException::class,
                'exceptionMessage' => "Validation failed. Path parameters cannot be optional. "
                    . "Set required=true for the 'username' parameters at operation 'paths./users.get'.",
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__security_definition__type',
                'exception' => InvalidSwaggerSpecException::class,
                'exceptionMessage' => "Validation failed. Field 'securityDefinitions.0.type' has an invalid value: invalid. Allowed values: basic, apiKey, oauth2.",
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__security_definition__flow',
                'exception' => InvalidSwaggerSpecException::class,
                'exceptionMessage' => "Validation failed. Field 'securityDefinitions.0.flow' has an invalid value: invalid. Allowed values: implicit, password, application, accessCode.",
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__security_definition__in',
                'exception' => InvalidSwaggerSpecException::class,
                'exceptionMessage' => "Validation failed. Field 'securityDefinitions.0.in' has an invalid value: invalid. Allowed values: query, header.",
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__request_body__invalid_content',
                'exception' => InvalidSwaggerSpecException::class,
                'exceptionMessage' => "Validation failed. Operation 'paths./users/{id}.post' has invalid content types: image/png.",
            ],
            [
                'tmpDoc' => 'documentation/invalid_format__response__invalid_items',
                'exception' => InvalidSwaggerSpecException::class,
                'exceptionMessage' => "Validation failed. 'paths./users/{id}.post.responses.200.schema.items' should have required fields: type.",
            ],
        ];
    }

    #[DataProvider('getConstructorInvalidTmpData')]
    public function testGetDocFileContentInvalidTmpData(
        string $tmpDoc,
        string $exception,
        string $exceptionMessage,
    ) {
        $this->mockDriverGetDocumentation($this->getJsonFixture($tmpDoc));

        $this->expectException($exception);
        $this->expectExceptionMessage($exceptionMessage);

        app(SwaggerService::class)->getDocFileContent();
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
        ParallelTesting::resolveTokenUsing(fn () => 'testWorkerID');

        $this->mockNativeFunction(
            namespace: 'RonasIT\AutoDoc\Support',
            callChain: [
                $this->functionCall(
                    name: 'stream_get_contents',
                    result: $this->getJsonFixture('tmp_data_post_user_request')
                ),
                $this->functionCall(
                    name: 'stream_get_contents',
                    result: $this->getJsonFixture('tmp_data_search_users_empty_request')
                ),
            ]
        );

        $this->mockDriverGetTmpData($this->getJsonFixture('tmp_data_search_users_empty_request'));

        $service = app(SwaggerService::class);

        $service->saveProductionData();

        $this->assertFileExists(storage_path('temp_documentation.json'));
        $this->assertFileEquals($this->generateFixturePath('tmp_data_merged.json'), storage_path('temp_documentation.json'));
    }
}
