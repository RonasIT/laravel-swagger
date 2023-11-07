<?php

namespace RonasIT\Support\AutoDoc\Models;

use Illuminate\Support\Arr;

/**
 * @property array $data
 * @property array<string, Path> $paths
 * @property array<string, Definition> $definitions
 */
class Specification
{
    private $data;
    private $paths;
    private $definitions;

    public function __construct(array $data)
    {
        $this->data = $data;

        foreach (Arr::get($data, 'paths', []) as $pathName => $pathData) {
            $this->paths[$pathName] = new Path($pathData);
        }

        foreach (Arr::get($data, 'definitions', []) as $defName => $defData) {
            $this->definitions[$defName] = new Definition($defData);
        }
    }

    public function getPaths()
    {
        return $this->paths;
    }

    public function getDefinitions()
    {
        return $this->definitions;
    }
}