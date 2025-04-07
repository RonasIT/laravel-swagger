<?php

namespace RonasIT\AutoDoc\Drivers;

use Illuminate\Support\Facades\ParallelTesting;
use RonasIT\AutoDoc\Contracts\SwaggerDriverContract;

abstract class BaseDriver implements SwaggerDriverContract
{
    public string $tempFilePath;
    public string $processTempFilePath;

    public function __construct()
    {
        $this->tempFilePath = storage_path('temp_documentation.json');

        $this->processTempFilePath = ($token = ParallelTesting::token())
            ? storage_path("temp_documentation_{$token}.json")
            : $this->tempFilePath;
    }

    public function saveProcessTmpData(array $data): void
    {
        file_put_contents($this->processTempFilePath, json_encode($data));
    }

    public function getProcessTmpData(): ?array
    {
        if (file_exists($this->processTempFilePath)) {
            $content = file_get_contents($this->processTempFilePath);

            return json_decode($content, true);
        }

        return null;
    }

    protected function clearProcessTmpData(): void
    {
        if (file_exists($this->processTempFilePath)) {
            unlink($this->processTempFilePath);
        }
    }
}
