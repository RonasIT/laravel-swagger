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
}
