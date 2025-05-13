<?php

namespace RonasIT\AutoDoc\Exceptions;

use \Illuminate\Contracts\Filesystem\FileNotFoundException as BaseException;

class FileNotFoundException extends BaseException
{
    public function __construct(string $filePath = null)
    {
        $message = empty($filePath)
            ? 'Documentation file not found'
            : "Documentation file not found: {$filePath}";

        parent::__construct($message);
    }
}