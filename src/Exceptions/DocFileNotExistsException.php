<?php

namespace RonasIT\Support\AutoDoc\Exceptions;

use Exception;

class DocFileNotExistsException extends Exception
{
    public function __construct(string $filename)
    {
        parent::__construct("Doc file '{$filename}' doesn't exist.");
    }
}
