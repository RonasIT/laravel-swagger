<?php

namespace RonasIT\Support\AutoDoc\Exceptions;

use Exception;

class InvalidDriverClassException extends Exception
{
    public function __construct(string $driver)
    {
        parent::__construct("The driver '{$driver}' does not extend the BaseDriver class.");
    }
}
