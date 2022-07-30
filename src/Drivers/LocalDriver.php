<?php

namespace RonasIT\Support\AutoDoc\Drivers;

use stdClass;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use RonasIT\Support\AutoDoc\Interfaces\SwaggerDriverInterface;
use RonasIT\Support\AutoDoc\Exceptions\MissedProductionFilePathException;

class LocalDriver implements SwaggerDriverInterface
{
    public $prodFilePath;
    protected $additionalFilePaths;

    protected static $data;

    public function __construct()
    {
        $this->prodFilePath = config('auto-doc.drivers.local.production_path');
        $this->additionalFilePaths = config('auto-doc.additional_paths');

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

    protected function mergingDocs(): array
    {
        $content = self::$data;

        if (!empty($this->additionalFilePaths)) {
            foreach ($this->additionalFilePaths as $filePath) {
                $fileContent = json_decode(file_get_contents($filePath), true);

                $paths = array_keys($fileContent['paths']);

                foreach ($paths as $path) {
                    if (empty($content['paths'][$path])) {
                        $content['paths'][$path] = $fileContent['paths'][$path];
                    }
                }

                $definitions = array_keys($fileContent['definitions']);

                foreach ($definitions as $definition) {
                    if (empty($content['definitions'][$path])) {
                        $content['definitions'][$definition] = $fileContent['definitions'][$definition];
                    }
                }
            }
        }

        return $content;
    }

    public function saveData()
    {
        $content = $this->mergingDocs();

        file_put_contents($this->prodFilePath, json_encode($content));

        self::$data = [];
    }

    public function getDocumentation(): stdClass
    {
        if (!file_exists($this->prodFilePath)) {
            throw new FileNotFoundException();
        }

        $fileContent = file_get_contents($this->prodFilePath);

        return json_decode($fileContent);
    }
}
