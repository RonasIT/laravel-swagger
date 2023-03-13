<?php

namespace RonasIT\Support\AutoDoc\Drivers;

abstract class BaseDriver
{
    protected $tempFilePath;

    public function saveTmpData($data): void
    {
        file_put_contents($this->tempFilePath, json_encode($data));
    }

    public function getTmpData(): ?array
    {
        if (file_exists($this->tempFilePath)) {
            $content = file_get_contents($this->tempFilePath);

            return json_decode($content, true);
        }

        return null;
    }

    abstract public function saveData(): void;

    abstract public function getDocumentation(): array;
}
