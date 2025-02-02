<?php

namespace RonasIT\AutoDoc\Tests;

use Illuminate\Http\Response;
use phpmock\phpunit\PHPMock;
use RonasIT\AutoDoc\Tests\Support\Traits\MockTrait;

class AutoDocControllerTest extends TestCase
{
    use MockTrait;
    use PHPMock;

    protected static array $documentation;

    public function setUp(): void
    {
        parent::setUp();

        self::$documentation ??= $this->getJsonFixture('tmp_data');

        if (!is_dir(storage_path('documentations'))) {
            mkdir(storage_path('documentations'));
        }

        file_put_contents(storage_path('documentations/documentation.json'), json_encode(self::$documentation));

        config(['auto-doc.drivers.local.base_file_name' => 'documentation']);
    }

    public function tearDown(): void
    {
        putenv('SWAGGER_GLOBAL_PREFIX=');

        parent::tearDown();
    }

    public function testGetJSONDocumentation()
    {
        $response = $this->json('get', '/auto-doc/documentation');

        $response->assertStatus(Response::HTTP_OK);

        $response->assertJson(self::$documentation);
    }

    public function testGetJSONDocumentationWithAdditionalPaths()
    {
        config([
            'auto-doc.additional_paths' => ['tests/fixtures/AutoDocControllerTest/tmp_data_with_additional_paths.json'],
        ]);

        $response = $this->json('get', '/auto-doc/documentation');

        $response->assertStatus(Response::HTTP_OK);

        $this->assertEqualsJsonFixture('tmp_data_with_additional_paths', $response->json());
    }

    public function testGetJSONDocumentationDoesntExist()
    {
        $mock = $this->getFunctionMock('RonasIT\AutoDoc\Services', 'report');
        $mock->expects($this->once());

        config([
            'auto-doc.additional_paths' => ['invalid_path/non_existent_file.json'],
        ]);

        $response = $this->json('get', '/auto-doc/documentation');

        $response->assertStatus(Response::HTTP_OK);

        $response->assertJson(self::$documentation);
    }

    public function testGetJSONDocumentationIsEmpty()
    {
        $mock = $this->getFunctionMock('RonasIT\AutoDoc\Services', 'report');
        $mock->expects($this->once());

        config([
            'auto-doc.additional_paths' => ['tests/fixtures/AutoDocControllerTest/documentation__non_json.txt'],
        ]);

        $response = $this->json('get', '/auto-doc/documentation');

        $response->assertStatus(Response::HTTP_OK);

        $response->assertJson(self::$documentation);
    }

    public function testGetJSONDocumentationInvalidAdditionalDoc()
    {
        config([
            'auto-doc.additional_paths' => [
                'tests/fixtures/AutoDocControllerTest/documentation__invalid_format__missing_field__paths.json',
            ],
        ]);

        $response = $this->json('get', '/auto-doc/documentation');

        $response->assertStatus(Response::HTTP_OK);

        $response->assertJson(self::$documentation);
    }

    public function testGetJSONDocumentationWithGlobalPrefix()
    {
        $this->addGlobalPrefix();

        $response = $this->json('get', '/global/auto-doc/documentation');

        $response->assertStatus(Response::HTTP_OK);

        $response->assertJson(self::$documentation);
    }

    public function testGetViewDocumentation()
    {
        config(['auto-doc.display_environments' => ['testing']]);

        $response = $this->get('/');

        $response->assertStatus(Response::HTTP_OK);

        $this->assertEqualsFixture('rendered_documentation.html', $response->getContent());
    }

    public function testGetViewElementsDocumentation()
    {
        config([
            'auto-doc.display_environments' => ['testing'],
            'auto-doc.documentation_viewer' => 'elements',
        ]);

        $response = $this->get('/');

        $response->assertStatus(Response::HTTP_OK);

        $this->assertEqualsFixture('rendered_elements_documentation.html', $response->getContent());
    }

    public function testGetViewRapidocDocumentation()
    {
        config([
            'auto-doc.display_environments' => ['testing'],
            'auto-doc.documentation_viewer' => 'rapidoc',
        ]);

        $response = $this->get('/');

        $response->assertStatus(Response::HTTP_OK);

        $this->assertEqualsFixture('rendered_rapidoc_documentation.html', $response->getContent());
    }

    public function testGetViewDocumentationEnvironmentDisable()
    {
        $response = $this->get('/');

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $response->assertSeeText('Forbidden.');
    }

    public function testGetViewDocumentationWithGlobalPrefix()
    {
        $this->addGlobalPrefix();

        config(['auto-doc.display_environments' => ['testing']]);

        $response = $this->get('/global');

        $response->assertStatus(Response::HTTP_OK);

        $this->assertEqualsFixture('rendered_documentation_with_global_path.html', $response->getContent());
    }

    public function testGetSwaggerAssetFile()
    {
        $response = $this->get('/auto-doc/swagger-ui.js');

        $response->assertStatus(Response::HTTP_OK);

        $this->assertEquals($response->getContent(), file_get_contents(resource_path('/assets/swagger/swagger-ui.js')));

        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    public function testGetElementsAssetFile()
    {
        config(['auto-doc.documentation_viewer' => 'elements']);

        $response = $this->get('/auto-doc/web-components.min.js');

        $response->assertStatus(Response::HTTP_OK);

        $this->assertEquals(
            expected: $response->getContent(),
            actual: file_get_contents(resource_path('/assets/elements/web-components.min.js')),
        );

        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    public function testGetFileNotExists()
    {
        $response = $this->get('/auto-doc/non-existent-file.js');

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function testGetSwaggerAssetFileWithGlobalPrefix()
    {
        $this->addGlobalPrefix();

        $response = $this->get('/global/auto-doc/swagger-ui.js');

        $response->assertStatus(Response::HTTP_OK);

        $this->assertEquals($response->getContent(), file_get_contents(resource_path('/assets/swagger/swagger-ui.js')));
    }

    public function testGetSwaggerAssetFileNotExists()
    {
        $response = $this->get('/global/auto-doc/invalid');

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }
}
