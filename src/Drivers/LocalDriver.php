<?php

namespace RonasIT\Support\AutoDoc\Drivers;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use RonasIT\Support\AutoDoc\Interfaces\SwaggerDriverInterface;
use RonasIT\Support\AutoDoc\Exceptions\MissedProductionFilePathException;

class LocalDriver implements SwaggerDriverInterface
{
    public $prodFilePath;

    protected static $data;

    public function __construct()
    {
        $this->prodFilePath = config('auto-doc.drivers.local.production_path');

        if (empty($this->prodFilePath)) {
            throw new MissedProductionFilePathException();
        }
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

        file_put_contents($this->prodFilePath, $content);

        self::$data = [];
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
