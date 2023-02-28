<?php

namespace RonasIT\Support\Tests;

class PushDocumentationCommandTest extends TestCase
{
    public function testHandle()
    {
        $this->artisan('swagger:push-documentation')->assertExitCode(0);

        $this->assertFileExists(storage_path('documentation.json'));

        $docContent = file_get_contents(storage_path('documentation.json'));

        $this->assertEqualsJsonFixture('documentation', json_decode($docContent, true));
    }
}
