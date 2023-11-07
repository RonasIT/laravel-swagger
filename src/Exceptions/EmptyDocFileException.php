<?php

namespace RonasIT\Support\AutoDoc\Exceptions;

use Exception;

class EmptyDocFileException extends Exception
{
    public function __construct(string $filename)
    {
        parent::__construct("Doc file '{$filename}' is empty.");
    }
}
