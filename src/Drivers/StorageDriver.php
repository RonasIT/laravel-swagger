<?php

namespace RonasIT\Support\AutoDoc\Drivers;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\Facades\Storage;
use RonasIT\Support\AutoDoc\Exceptions\MissedProductionFilePathException;
use RonasIT\Support\AutoDoc\Services\SwaggerService;

class StorageDriver extends BaseDriver
{
    protected $disk;
    protected $prodFilePath;

    public function __construct()
    {
        parent::__construct();

        $this->disk = Storage::disk(config('auto-doc.drivers.storage.disk'));
        $this->prodFilePath = config('auto-doc.drivers.storage.production_path');

        if (empty($this->prodFilePath)) {
            throw new MissedProductionFilePathException();
        }
    }

    private function addPaths(array &$data): void
    {
        foreach ($this->getTmpData()['paths'] as $tmpPathKey => $tmpPathValue) {
            $data['paths'][$tmpPathKey] = array_key_exists($tmpPathKey, $data['paths'])
                ? array_merge($data['paths'][$tmpPathKey], $tmpPathValue)
                : $tmpPathValue;
        }
    }

    private function addDefinitions(array &$data): void
    {
        foreach ($this->getTmpData()['definitions'] as $tmpDefKey => $tmpDefValue) {
            $data['definitions'][$tmpDefKey] = $tmpDefValue;
        }
    }

    public function saveData(): void
    {
        if (ParallelTesting::token()) {
            if (!$this->disk->exists($this->prodFilePath)) {
                $emptyData = app(SwaggerService::class)->generateEmptyData();

                $this->disk->put($this->prodFilePath, json_encode($emptyData));
            }

            $prodFileJsonContent = $this->disk->get($this->prodFilePath);
            $prodFileArrayContent = json_decode($prodFileJsonContent, true);

            $this->addPaths($prodFileArrayContent);
            $this->addDefinitions($prodFileArrayContent);

            $this->disk->put($this->prodFilePath, json_encode($prodFileArrayContent));
        } else {
            $this->disk->put($this->prodFilePath, json_encode($this->getTmpData()));
        }

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
