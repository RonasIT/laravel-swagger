<?php

namespace RonasIT\AutoDoc\Tests;

use RonasIT\AutoDoc\Tests\Support\Traits\SwaggerServiceMockTrait;

class PushDocumentationCommandTest extends TestCase
{
    use SwaggerServiceMockTrait;

    public function testHandle()
    {
        $this->mockDriverSaveData();

        $this->artisan('swagger:push-documentation')->assertExitCode(0);
    }

    public function testHandleTargetEnvironmentNotDisplayed()
    {
        config(['auto-doc.target_environments' => ['production']]);

        $this->artisan('swagger:push-documentation')->assertExitCode(0);

        $this->assertFileDoesNotExist(storage_path('documentation.json'));
        $this->assertFileDoesNotExist(storage_path('temp_documentation.json'));
    }
}
