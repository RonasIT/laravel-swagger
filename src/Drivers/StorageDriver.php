<?php

namespace RonasIT\Support\AutoDoc\Drivers;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Storage;
use RonasIT\Support\AutoDoc\Interfaces\SwaggerDriverInterface;

class StorageDriver implements SwaggerDriverInterface
{
    protected $disk;
    protected $prodFilePath;
    protected $tempFilePath;

    public function __construct()
    {
        $this->disk = config('auto-doc.drivers.storage.disk');
        $this->prodFilePath = config('auto-doc.drivers.storage.production_path');
        $this->tempFilePath = 'temp_documentation.json';
    }

    public function saveTmpData($data)
    {
        Storage::disk($this->disk)->put($this->tempFilePath, json_encode($data));
    }

    public function getTmpData()
    {
        if (Storage::disk($this->disk)->exists($this->tempFilePath)) {
            $content = Storage::disk($this->disk)->get($this->tempFilePath);

            return json_decode($content, true);
        }

        return null;
    }

    public function saveData()
    {
        Storage::disk($this->disk)->put($this->prodFilePath, json_encode($this->getTmpData()));
    }

    public function getDocumentation(): array
    {
        if (!Storage::disk($this->disk)->exists($this->prodFilePath)) {
            throw new FileNotFoundException();
        }

        $fileContent = Storage::disk($this->disk)->get($this->prodFilePath);

        return json_decode($fileContent, true);
    }
}
