<?php

namespace RonasIT\AutoDoc\Drivers;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use RonasIT\AutoDoc\Exceptions\MissedProductionFilePathException;

class StorageDriver extends BaseDriver
{
    protected Filesystem $disk;
    protected ?string $mainFilePath;
    protected array $config;

    public function __construct()
    {
        parent::__construct();

        $this->config = config('auto-doc.drivers.storage');
        $this->disk = Storage::disk($this->config['disk']);

        $directory = str_ends_with($this->config['directory'], DIRECTORY_SEPARATOR)
            ? $this->config['directory']
            : $this->config['directory'] . DIRECTORY_SEPARATOR;

        $this->mainFilePath = "$directory{$this->config['base_file_name']}.json";

        if (!preg_match('/\/[\w]+\.json/ms', $this->mainFilePath)) {
            throw new MissedProductionFilePathException();
        }
    }

    public function saveData(): void
    {
        $this->disk->put($this->mainFilePath, json_encode($this->getTmpData()));

        $this->clearTmpData();
    }

    public function getDocumentation(): array
    {
        if (!$this->disk->exists($this->mainFilePath)) {
            throw new FileNotFoundException();
        }

        $fileContent = $this->disk->get($this->mainFilePath);

        return json_decode($fileContent, true);
    }
}
