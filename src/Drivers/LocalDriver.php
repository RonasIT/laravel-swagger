<?php

namespace RonasIT\AutoDoc\Drivers;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use RonasIT\AutoDoc\Exceptions\MissedProductionFilePathException;

class LocalDriver extends BaseDriver
{
    protected ?string $baseFileName;
    private ?array $config;

    public function __construct()
    {
        parent::__construct();
        $this->config = config('auto-doc.drivers.local');

        $directory = $this->config['directory'];
        if (!str_ends_with($directory, DIRECTORY_SEPARATOR)) {
            $directory .= DIRECTORY_SEPARATOR;
        }

        $this->baseFileName = storage_path($directory.$this->config['base_file_name'].'.json');

        if (!preg_match('/\/[\w]+\.json/ms', $this->baseFileName)) {
            throw new MissedProductionFilePathException();
        }
    }

    public function saveData(): void
    {
        $prodDir = storage_path($this->config['directory']);
        if (!is_dir($prodDir)) {
            mkdir($prodDir);
        }

        file_put_contents($this->baseFileName, json_encode($this->getTmpData()));

        $this->clearTmpData();
    }

    public function getDocumentation(): array
    {
        if (!file_exists($this->baseFileName)) {
            throw new FileNotFoundException();
        }

        $fileContent = file_get_contents($this->baseFileName);

        return json_decode($fileContent, true);
    }
}
