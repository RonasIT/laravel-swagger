<?php

namespace RonasIT\AutoDoc\Drivers;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use RonasIT\AutoDoc\Exceptions\MissedProductionFilePathException;

class StorageDriver extends BaseDriver
{
    protected Filesystem $disk;
    protected ?string $baseFileName;
    protected array $config;

    public function __construct()
    {
        parent::__construct();

        $this->config = config('auto-doc.drivers.storage');
        $this->disk = Storage::disk($this->config['disk']);
        $directory = $this->config['directory'];
        if (!str_ends_with($directory, DIRECTORY_SEPARATOR)) {
            $directory .= DIRECTORY_SEPARATOR;
        }
        $this->baseFileName = $directory.$this->config['base_file_name'].'.json';

        if (!preg_match('/\/[\w]+\.json/ms', $this->baseFileName)) {
            throw new MissedProductionFilePathException();
        }
    }

    public function saveData(): void
    {
        $this->disk->put($this->baseFileName, json_encode($this->getTmpData()));

        $this->clearTmpData();
    }

    public function getDocumentation(): array
    {
        if (!$this->disk->exists($this->baseFileName)) {
            throw new FileNotFoundException();
        }

        $fileContent = $this->disk->get($this->baseFileName);

        return json_decode($fileContent, true);
    }
}
