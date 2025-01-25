<?php

namespace RonasIT\AutoDoc\Drivers;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use RonasIT\AutoDoc\Exceptions\MissedProductionFilePathException;

class LocalDriver extends BaseDriver
{
    protected ?string $mainFilePath;
    private ?array $config;

    public function __construct()
    {
        parent::__construct();

        $this->config = config('auto-doc.drivers.local');

        $directory = str_ends_with($this->config['directory'], DIRECTORY_SEPARATOR)
            ? $this->config['directory']
            : $this->config['directory'] . DIRECTORY_SEPARATOR;

        $this->mainFilePath = storage_path("$directory{$this->config['base_file_name']}.json");

        if (empty($this->config['base_file_name']) || !str_ends_with($this->mainFilePath, '.json')) {
            throw new MissedProductionFilePathException();
        }
    }

    public function saveData(): void
    {
        $documentationDirectory = storage_path($this->config['directory']);
        if (!is_dir($documentationDirectory)) {
            mkdir($documentationDirectory);
        }

        file_put_contents($this->mainFilePath, json_encode($this->getTmpData()));

        $this->clearTmpData();
    }

    public function getDocumentation(): array
    {
        if (!file_exists($this->mainFilePath)) {
            throw new FileNotFoundException();
        }

        $fileContent = file_get_contents($this->mainFilePath);

        return json_decode($fileContent, true);
    }
}
