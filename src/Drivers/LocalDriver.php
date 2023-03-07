<?php

namespace RonasIT\Support\AutoDoc\Drivers;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use RonasIT\Support\AutoDoc\Exceptions\MissedProductionFilePathException;
use RonasIT\Support\AutoDoc\Interfaces\SwaggerDriverInterface;

class LocalDriver implements SwaggerDriverInterface
{
    protected $prodFilePath;
    protected $tempFilePath;

    public function __construct()
    {
        $this->prodFilePath = config('auto-doc.drivers.local.production_path');
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
        file_put_contents($this->prodFilePath, json_encode($this->getTmpData()));
    }

    public function getDocumentation(): array
    {
        if (!file_exists($this->prodFilePath)) {
            throw new FileNotFoundException();
        }

        $fileContent = file_get_contents($this->prodFilePath);

        return json_decode($fileContent, true);
    }
}
