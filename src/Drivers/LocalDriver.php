<?php

namespace RonasIT\Support\AutoDoc\Drivers;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\ParallelTesting;
use RonasIT\Support\AutoDoc\Exceptions\MissedProductionFilePathException;
use RonasIT\Support\AutoDoc\Services\SwaggerService;

class LocalDriver extends BaseDriver
{
    protected $prodFilePath;

    public function __construct()
    {
        parent::__construct();

        $this->prodFilePath = config('auto-doc.drivers.local.production_path');

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
            if (!file_exists($this->prodFilePath)) {
                $emptyData = app(SwaggerService::class)->generateEmptyData();

                file_put_contents($this->prodFilePath, json_encode($emptyData));
            }

            $prodFileJsonContent = file_get_contents($this->prodFilePath);
            $prodFileArrayContent = json_decode($prodFileJsonContent, true);

            $this->addPaths($prodFileArrayContent);
            $this->addDefinitions($prodFileArrayContent);

            file_put_contents($this->prodFilePath, json_encode($prodFileArrayContent));
        } else {
            file_put_contents($this->prodFilePath, json_encode($this->getTmpData()));
        }

        $this->clearTmpData();
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
