<?php

namespace RonasIT\Support\AutoDoc\Drivers;

use RonasIT\Support\AutoDoc\Interfaces\SwaggerDriverInterface;

abstract class BaseDriver implements SwaggerDriverInterface
{
    protected string $tempFilePath;

    public function __construct()
    {
        $this->tempFilePath = storage_path('temp_documentation.json');
    }

    public function saveTmpData($data): void
    {
        $handle = fopen($this->tempFilePath, 'c+');

        flock($handle, LOCK_EX);

        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, json_encode($data, JSON_THROW_ON_ERROR));

        flock($handle, LOCK_UN);
    }

    public function getTmpData(): ?array
    {
        $handle = @fopen($this->tempFilePath, 'r');

        if ($handle === false) {
            return null;
        }

        flock($handle, LOCK_SH);

        $content = stream_get_contents($handle);

        return json_decode($content, true);
    }

    protected function clearTmpData(): void
    {
        if (file_exists($this->tempFilePath)) {
            unlink($this->tempFilePath);
        }
    }
}
