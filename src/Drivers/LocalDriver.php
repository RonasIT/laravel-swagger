<?php

namespace RonasIT\Support\AutoDoc\Drivers;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use RonasIT\Support\AutoDoc\Exceptions\MissedProductionFilePathException;

class LocalDriver extends BaseDriver
{
    protected $prodFilePath;

    public function __construct()
    {
        $this->prodFilePath = config('auto-doc.drivers.local.production_path');
        $this->tempFilePath = storage_path('temp_documentation.json');

        if (empty($this->prodFilePath)) {
            throw new MissedProductionFilePathException();
        }
    }

    public function saveData(): void
    {
        file_put_contents($this->prodFilePath, json_encode($this->getTmpData()));

        if (file_exists($this->tempFilePath)) {
            unlink($this->tempFilePath);
        }
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
