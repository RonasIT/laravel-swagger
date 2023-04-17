<?php

namespace RonasIT\Support\AutoDoc\Exceptions\SpecValidation;

use Exception;

class InvalidFieldNameException extends Exception
{
    public function __construct(string $fieldName)
    {
        parent::__construct("Validation failed. Invalid field name '{$fieldName}' found.");
    }
}
