<?php

namespace RonasIT\AutoDoc\Support;

use Illuminate\Support\Arr;
use RuntimeException;

class Mutex
{
    protected array $config;

    protected const string MODE_FILE_READ_WRITE = 'c+';
    protected const string MODE_FILE_READ = 'r';

    public function __construct()
    {
        $this->config = config('auto-doc.paratests');
    }

    public function readFileWithLock(string $filePath): array
    {
        $handle = fopen($filePath, self::MODE_FILE_READ);

        try {
            $this->acquireLock($handle, LOCK_SH);

            return $this->readJsonFromStream($handle);
        } finally {
            $this->unlockFile($handle);
        }
    }

    public function lockFile(string $filePath)
    {
        $fileResource = fopen($filePath, self::MODE_FILE_READ_WRITE);

        $this->acquireLock($fileResource, LOCK_EX | LOCK_NB);

        return $fileResource;
    }

    public function unlockFile($fileResource): void
    {
        flock($fileResource, LOCK_UN);
        fclose($fileResource);
    }

    public function readJsonFromStream($handle): ?array
    {
        $content = stream_get_contents($handle);

        return ($content === false) ? null : json_decode($content, true);
    }

    public function writeJsonToStream($handle, array $data): void
    {
        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, json_encode($data));
        fflush($handle);
    }

    /**
     * @codeCoverageIgnore
     */
    protected function acquireLock(
        $handle,
        int $operation,
    ): void {
        $retryCounter = 0;

        $maxRetries = Arr::get($this->config, 'tmp_file_lock.max_retries');
        $waitTime = Arr::get($this->config, 'tmp_file_lock.wait_time');

        while (!flock($handle, $operation)) {
            if ($retryCounter >= $maxRetries) {
                throw new RuntimeException('Unable to lock file');
            }

            usleep($waitTime);

            $retryCounter++;
        }
    }
}
