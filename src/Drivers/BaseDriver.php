<?php

namespace RonasIT\Support\AutoDoc\Drivers;

use RonasIT\Support\AutoDoc\Interfaces\SwaggerDriverInterface;

abstract class BaseDriver implements SwaggerDriverInterface
{
    protected $tempFilePath;

    public function __construct()
    {
        $this->tempFilePath = storage_path('temp_documentation.json');
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
