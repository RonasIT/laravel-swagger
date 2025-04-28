<?php

namespace RonasIT\AutoDoc\Support;

use RuntimeException;

class Mutex
{
    protected array $config;

    protected const string MODE_FILE_READ_WRITE = 'c+';
    protected const string MODE_FILE_READ = 'r';

    public function __construct(
        protected int $maxRetries,
        protected int $waitTime,
    ) {
    }

    public function readFileWithLock(string $filePath): string
    {
        $handle = fopen($filePath, self::MODE_FILE_READ);

        try {
            $this->acquireLock($handle, LOCK_SH);

            return (string) stream_get_contents($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    public function writeFileWithLock(string $filePath, callable $callback): void
    {
        $fileResource = fopen($filePath, self::MODE_FILE_READ_WRITE);

        try {
            $this->acquireLock($fileResource, LOCK_EX | LOCK_NB);

            $data = $callback((string) stream_get_contents($fileResource));

            ftruncate($fileResource, 0);
            rewind($fileResource);
            fwrite($fileResource, $data);
            fflush($fileResource);
        } finally {
            flock($fileResource, LOCK_UN);
            fclose($fileResource);
        }
    }

    /**
     * @codeCoverageIgnore
     */
    protected function acquireLock($handle, int $operation): void
    {
        $retryCounter = 0;

        $maxRetries = $this->maxRetries;
        $waitTime = $this->waitTime;

        while (!flock($handle, $operation)) {
            if ($retryCounter >= $maxRetries) {
                throw new RuntimeException('Unable to lock file');
            }

            usleep($waitTime);

            $retryCounter++;
        }
    }
}
