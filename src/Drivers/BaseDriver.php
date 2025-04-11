<?php

namespace RonasIT\AutoDoc\Drivers;

use Illuminate\Support\Facades\ParallelTesting;
use RonasIT\AutoDoc\Contracts\SwaggerDriverContract;
use RonasIT\AutoDoc\Support\Mutex;

abstract class BaseDriver implements SwaggerDriverContract
{
    protected string $tempFilePath;
    protected string $processTempFilePath;
    protected Mutex $mutex;

    public function __construct()
    {
        $this->tempFilePath = storage_path('temp_documentation.json');

        $this->processTempFilePath = ($token = ParallelTesting::token())
            ? storage_path("temp_documentation_{$token}.json")
            : $this->tempFilePath;

        $this->mutex = app(Mutex::class);
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

    public function appendProcessDataToTmpFile(callable $callback): void
    {
        $this->mutex->writeFileWithLock($this->tempFilePath, $callback);
    }

    public function getTmpData(): ?array
    {
        if (file_exists($this->tempFilePath)) {
            $data = $this->mutex->readFileWithLock($this->tempFilePath);

            return (empty($data)) ? null : json_decode($data, true);
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
