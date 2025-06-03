<?php

namespace RonasIT\AutoDoc\Exceptions;

use Illuminate\Contracts\Filesystem\FileNotFoundException as BaseException;

class FileNotFoundException extends BaseException
{
    public function __construct(string $filePath = null)
    {
        parent::__construct("Documentation file not found {$filePath}");
    }
}