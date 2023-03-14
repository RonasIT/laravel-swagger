<?php

namespace RonasIT\Support\Tests;

use RonasIT\Support\Tests\Support\Traits\SwaggerServiceMockTrait;

class PushDocumentationCommandTest extends TestCase
{
    use SwaggerServiceMockTrait;

    public function testHandle()
    {
        $this->mockDriverSaveData();

        $this->artisan('swagger:push-documentation')->assertExitCode(0);
    }
}
