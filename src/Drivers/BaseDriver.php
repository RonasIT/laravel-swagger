<?php

namespace RonasIT\AutoDoc\Drivers;

use Illuminate\Support\Facades\ParallelTesting;
use RonasIT\AutoDoc\Contracts\SwaggerDriverContract;
use RuntimeException;

abstract class BaseDriver implements SwaggerDriverContract
{
    protected string $tempFilePath;
    protected string $sharedTempFilePath;

    public function __construct()
    {
        $this->sharedTempFilePath = storage_path('temp_documentation.json');

        $this->tempFilePath = ($token = ParallelTesting::token())
            ? storage_path("temp_documentation_{$token}.json")
            : $this->sharedTempFilePath;
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
        $this->writeFileWithLock($this->sharedTempFilePath, $callback);
    }

    public function getSharedTmpData(): ?array
    {
        if (file_exists($this->sharedTempFilePath)) {
            return $this->readFileWithLock($this->sharedTempFilePath);
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

    protected function readFileWithLock(string $filePath): array
    {
        $handle = fopen($filePath, 'r');

        try {
            $this->acquireLock($handle, LOCK_SH);

            return $this->readJsonFromStream($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    protected function writeFileWithLock(string $filePath, callable $callback): void
    {
        $handle = fopen($filePath, 'c+');

        try {
            $this->acquireLock($handle, LOCK_EX | LOCK_NB);

            $data = $callback($this->readJsonFromStream($handle));

            $this->writeJsonToStream($handle, $data);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    protected function writeJsonToStream($handle, array $data): void
    {
        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, json_encode($data));
        fflush($handle);
    }

    protected function readJsonFromStream($handle): ?array
    {
        $content = stream_get_contents($handle);

        return ($content === false) ? null : json_decode($content, true);
    }

    /**
     * @codeCoverageIgnore
     */
    protected function acquireLock(
        $handle,
        int $operation,
        int $maxRetries = 20,
        int $minWaitTime = 100,
        int $maxWaitTime = 1000,
    ): void {
        $retryCounter = 0;

        while (!flock($handle, $operation)) {
            if ($retryCounter >= $maxRetries) {
                throw new RuntimeException('Unable to lock file');
            }

            usleep(rand($minWaitTime, $maxWaitTime));

            $retryCounter++;
        }
    }
}
