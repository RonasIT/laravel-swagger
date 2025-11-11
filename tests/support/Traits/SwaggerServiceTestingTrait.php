<?php

namespace RonasIT\AutoDoc\Tests\Support\Traits;

use RonasIT\Support\Traits\MockTrait;
use Illuminate\Support\Str;

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

    protected function assertExceptionHTMLEqualsFixture(string $fixture, string $content, bool $exportMode = false): void
    {
        $content = Str::replaceMatches('/line=\d+,/', 'line=999,', $content);

        $content = Str::replaceMatches('/file=.*?(src\/[^\s]+)/', 'file=/$1', $content);

        $content = Str::replaceMatches('/:\d+/', ':1', $content);

        $this->assertEqualsFixture($fixture, $content, $exportMode);
    }
}
