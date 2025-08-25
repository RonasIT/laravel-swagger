<?php

namespace RonasIT\AutoDoc\Tests\Support\Traits;

use RonasIT\Support\Traits\MockTrait;

trait SwaggerServiceTestingTrait
{
    use MockTrait;

    protected function fillTempFile(string $content): void
    {
        file_put_contents(getcwd() . '/storage/temp_documentation.json', $content);
    }

    protected function assertTempFileEqualsFixture(string $fixture): void
    {
        $fixture = $this->prepareFixtureName($fixture);

        $path = $this->generateFixturePath($fixture);

        $this->assertFileEquals($path, getcwd() . '/storage/temp_documentation.json');
    }
}