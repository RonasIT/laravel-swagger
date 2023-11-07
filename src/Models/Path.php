<?php

namespace RonasIT\Support\AutoDoc\Models;

use Illuminate\Support\Arr;

class Path
{
    private $get;
    private $put;
    private $post;
    private $head;
    private $delete;
    private $patch;
    private $options;

    public function __construct(array $data)
    {
        $this->get = Arr::get($data, 'get');
        $this->put = Arr::get($data, 'put');
    }
}