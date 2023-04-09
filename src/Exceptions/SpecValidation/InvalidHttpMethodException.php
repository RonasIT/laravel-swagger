<?php

namespace RonasIT\Support\AutoDoc\Exceptions\SpecValidation;

use Exception;

class InvalidHttpMethodException extends Exception
{
    public function __construct(string $method, string $path)
    {
        parent::__construct("Validation failed. Invalid http method '{$method}' provided for path '{$path}'.");
    }
}
