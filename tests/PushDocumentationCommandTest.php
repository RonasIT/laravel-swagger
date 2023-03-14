<?php

namespace RonasIT\Support\Tests;

use RonasIT\Support\Tests\Support\Traits\SwaggerServiceMockTrait;

class PushDocumentationCommandTest extends TestCase
{
    use SwaggerServiceMockTrait;

    public function testHandle()
    {
        config(['auto-doc.security' => 'laravel']);

        $this->mockDriverSaveData();

        $this->artisan('swagger:push-documentation')->assertExitCode(0);
    }
}
