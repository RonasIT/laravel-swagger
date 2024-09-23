<?php

namespace RonasIT\Tests;

use RonasIT\Tests\Support\Traits\SwaggerServiceMockTrait;

class PushDocumentationCommandTest extends TestCase
{
    use SwaggerServiceMockTrait;

    public function testHandle()
    {
        $this->mockDriverSaveData();

        $this->artisan('swagger:push-documentation')->assertExitCode(0);
    }
}
