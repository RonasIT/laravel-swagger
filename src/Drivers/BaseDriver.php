<?php

namespace RonasIT\AutoDoc\Drivers;

use RonasIT\AutoDoc\Contracts\SwaggerDriverContract;

abstract class BaseDriver implements SwaggerDriverContract
{
    protected string $tempFilePath;

    public function __construct()
    {
        $prodDir = config('auto-doc.documentation_directory');
        if (!is_dir($prodDir)) {
            mkdir($prodDir);
        }
        $this->tempFilePath = $prodDir.DIRECTORY_SEPARATOR.'temp_documentation.json';
    }

    public function saveTmpData($data): void
    {
        file_put_contents($this->tempFilePath, json_encode($data));
    }

    public function getTmpData(): ?array
    {
        if (file_exists($this->tempFilePath)) {
            $content = file_get_contents($this->tempFilePath);

            return json_decode($content, true);
        }

        return null;
    }

    protected function clearTmpData(): void
    {
        if (file_exists($this->tempFilePath)) {
            unlink($this->tempFilePath);
        }
    }
}
