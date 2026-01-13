<?php

namespace RonasIT\AutoDoc\Exceptions;

use Exception;

class NonJSONDocFileException extends Exception
{
    public function __construct(string $filename)
    {
        parent::__construct("Doc file '{$filename}' is not a json doc file.");
    }
}
