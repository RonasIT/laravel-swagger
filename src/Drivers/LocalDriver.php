<?php

namespace RonasIT\AutoDoc\Drivers;

use RonasIT\AutoDoc\Exceptions\FileNotFoundException;
use RonasIT\AutoDoc\Exceptions\MissedProductionFilePathException;
use RonasIT\AutoDoc\Exceptions\EmptyDocFileException;
use RonasIT\AutoDoc\Exceptions\NonJSONDocFileException;
use Symfony\Component\Filesystem\Path;

class LocalDriver extends BaseDriver
{
    protected ?string $prodFilePath;

    public function __construct()
    {
        parent::__construct();

        $this->prodFilePath = config('auto-doc.drivers.local.production_path');

        if (empty($this->prodFilePath)) {
            throw new MissedProductionFilePathException();
        }
    }

    public function saveData(): void
    {
        file_put_contents($this->prodFilePath, json_encode($this->getTmpData()));

        $this->clearProcessTmpData();
    }

    public function getDocumentation(): array
    {
        if (!file_exists($this->prodFilePath)) {
            throw new FileNotFoundException($this->prodFilePath);
        }

        $fileContent = file_get_contents($this->prodFilePath);

        if (empty($fileContent)) {
            throw new EmptyDocFileException(Path::makeRelative($this->prodFilePath, base_path()));
        }

        if (!json_validate($fileContent)) {
            throw new NonJSONDocFileException(Path::makeRelative($this->prodFilePath, base_path()));
        }

        return json_decode($fileContent, true);
    }
}
