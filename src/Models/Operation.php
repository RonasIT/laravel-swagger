<?php

namespace RonasIT\Support\AutoDoc\Models;

use Illuminate\Support\Arr;

/**
 * @property string[] $consumes
 * @property string[] $produces
 * @property
 */
class Operation
{
    private $consumes;

    public function __construct(array $data)
    {
        $this->get = Arr::get($data, 'get');
        $this->put = Arr::get($data, 'put');
    }
}