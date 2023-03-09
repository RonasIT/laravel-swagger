<?php

namespace RonasIT\Support\AutoDoc\Drivers;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Storage;
use RonasIT\Support\AutoDoc\Exceptions\MissedProductionFilePathException;
use RonasIT\Support\AutoDoc\Interfaces\SwaggerDriverInterface;

class StorageDriver implements SwaggerDriverInterface
{
    protected $disk;
    protected $prodFilePath;
    protected $tempFilePath;

    public function __construct()
    {
        $this->disk = Storage::disk(config('auto-doc.drivers.storage.disk'));
        $this->prodFilePath = config('auto-doc.drivers.storage.production_path');
        $this->tempFilePath = storage_path('temp_documentation.json');

        if (empty($this->prodFilePath)) {
            throw new MissedProductionFilePathException();
        }
    }

    public function saveTmpData($data)
    {
        file_put_contents($this->tempFilePath, json_encode($data));
    }

    public function getTmpData()
    {
        if (file_exists($this->tempFilePath)) {
            $content = file_get_contents($this->tempFilePath);

            return json_decode($content, true);
        }

        return null;
    }

    public function saveData()
    {
        $this->disk->put($this->prodFilePath, json_encode($this->getTmpData()));

        if (file_exists($this->tempFilePath)) {
            unlink($this->tempFilePath);
        }
    }

    public function getDocumentation(): array
    {
        if (!$this->disk->exists($this->prodFilePath)) {
            throw new FileNotFoundException();
        }

        $fileContent = $this->disk->get($this->prodFilePath);

        return json_decode($fileContent, true);
    }
}
