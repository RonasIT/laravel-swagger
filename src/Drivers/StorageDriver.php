<?php

namespace RonasIT\AutoDoc\Drivers;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use RonasIT\AutoDoc\Exceptions\MissedProductionFilePathException;

class StorageDriver extends BaseDriver
{
    protected Filesystem $disk;
    protected ?string $prodFilePath;

    public function __construct()
    {
        parent::__construct();

        $this->disk = Storage::disk(config('auto-doc.drivers.storage.disk'));
        $this->prodFilePath = config('auto-doc.drivers.storage.production_path');

        if (empty($this->prodFilePath)) {
            throw new MissedProductionFilePathException();
        }
    }

    public function saveData(): void
    {
        $this->disk->put($this->prodFilePath, json_encode($this->getTmpData()));

        $this->clearTmpData();
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
