<?php

namespace RonasIT\AutoDoc\Drivers;

use Illuminate\Support\Facades\ParallelTesting;
use RonasIT\AutoDoc\Contracts\SwaggerDriverContract;
use RonasIT\AutoDoc\Support\Mutex;

abstract class BaseDriver implements SwaggerDriverContract
{
    protected string $tempFilePath;
    protected string $sharedTempFilePath;
    protected Mutex $mutex;

    public function __construct()
    {
        $this->sharedTempFilePath = storage_path('temp_documentation.json');

        $this->tempFilePath = ($token = ParallelTesting::token())
            ? storage_path("temp_documentation_{$token}.json")
            : $this->sharedTempFilePath;

        $this->mutex = app(Mutex::class);
    }

    public function saveTmpData(array $data): void
    {
        $this->saveJsonToFile($this->tempFilePath, $data);
    }

    public function getTmpData(): ?array
    {
        return $this->getJsonFromFile($this->tempFilePath);
    }

    public function saveSharedTmpData(callable $callback): void
    {
        $this->mutex->writeFileWithLock($this->sharedTempFilePath, $callback);
    }

    public function getSharedTmpData(): ?array
    {
        if (file_exists($this->sharedTempFilePath)) {
            return $this->mutex->readFileWithLock($this->sharedTempFilePath);
        }

        return null;
    }

    protected function clearTmpData(): void
    {
        if (file_exists($this->tempFilePath)) {
            unlink($this->tempFilePath);
        }
    }

    protected function saveJsonToFile(string $filePath, array $data): void
    {
        file_put_contents($filePath, json_encode($data));
    }

    protected function getJsonFromFile(string $filePath): ?array
    {
        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);

            return json_decode($content, true);
        }

        return null;
    }
}
