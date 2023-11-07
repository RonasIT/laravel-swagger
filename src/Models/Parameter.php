<?php

namespace RonasIT\Support\AutoDoc\Models;

use Illuminate\Support\Arr;

/**
 * @property array $data
 * @property string $in
 * @property string $name
 */
class Parameter
{
    private $data;
    private $in;
    private $name;
    private $schema = null;

    public function __construct(array $data)
    {
        $this->data = $data;

        $this->name = $data['name'];
        $this->in = $data['in'];
    }

    public function getSchema(): Model
    {
        return $this->schema;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getIn(): string
    {
        return $this->in;
    }

    public function getData(): array
    {
        return $this->data;
    }
}