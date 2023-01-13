<?php

namespace RonasIT\Support\AutoDoc\Drivers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use RonasIT\Support\AutoDoc\Interfaces\SwaggerDriverInterface;

class StorageDriver implements SwaggerDriverInterface
{
    protected $disk;
    protected $filePath;

    protected static $data;

    public function __construct()
    {
        $this->disk = config('auto-doc.drivers.storage.disk');
        $this->filePath = config('auto-doc.drivers.storage.production_path');
    }

    public function saveTmpData($tempData)
    {
        self::$data = $tempData;
    }

    public function getTmpData()
    {
        return self::$data;
    }

    public function saveData()
    {
        $content = json_encode(self::$data);

        Storage::disk($this->disk)->put($this->filePath, $content);

        self::$data = [];
    }

    public function getDocumentation(): array
    {
        if (!Storage::disk($this->disk)->exists($this->filePath)) {
            throw new FileNotFoundException();
        }

        $fileContent = Storage::disk($this->disk)->get($this->filePath);

        return json_decode($fileContent, true);
    }
}
