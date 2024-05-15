<?php

namespace RonasIT\Support\AutoDoc\Drivers;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use RonasIT\Support\AutoDoc\Exceptions\MissedProductionFilePathException;

class LocalDriver extends BaseDriver
{
    protected $prodFilePath;

    public function __construct()
    {
        parent::__construct();

        $this->prodFilePath = storage_path(config('auto-doc.drivers.local.file_path').config('auto-doc.drivers.local.file_name'));

        if (empty(config('auto-doc.drivers.local.file_path'))
            ||
            empty(config('auto-doc.drivers.local.file_name'))
            ||
            empty($this->prodFilePath)
        ) {
            throw new MissedProductionFilePathException();
        }

        if (!is_dir(dirname($this->prodFilePath))) {
            mkdir(dirname($this->prodFilePath), 0777, true);
        }
    }

    public function saveData(): void
    {
        $currentDocumentation = [];
        $newData = $this->getTmpData();

        if (file_exists($this->prodFilePath)) {
            $currentDocumentation = $this->getDocumentation();
        }

        if (!empty($currentDocumentation) && $currentDocumentation !== $newData) {
            $version = $this->getNextVersion();
            $newFileName = str_replace('documentation.json', "documentation_$version.json", $this->prodFilePath);

            copy($this->prodFilePath, $newFileName);
        }

        file_put_contents($this->prodFilePath, json_encode($newData));

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

    protected function getNextVersion(): string
    {
        $currentVersion = config('auto-doc.config_version', '0.1');

        [$major, $minor] = explode('.', $currentVersion);

        return "{$major}_{$minor}";
    }
}
