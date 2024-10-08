<?php

namespace RonasIT\AutoDoc\Exceptions\SpecValidation;

use Exception;

class InvalidSwaggerSpecException extends Exception
{
    public function __construct($message = '')
    {
        parent::__construct('Validation failed. '. $message);
    }
}
