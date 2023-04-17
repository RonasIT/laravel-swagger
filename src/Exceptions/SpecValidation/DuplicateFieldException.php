<?php

namespace RonasIT\Support\AutoDoc\Exceptions\SpecValidation;

use Exception;

class DuplicateFieldException extends Exception
{
    public function __construct(string $fieldName, array $fieldValue)
    {
        $fieldValueString = implode(', ', $fieldValue);

        parent::__construct("Validation failed. Found multiple '{$fieldName}' with value '{$fieldValueString}'.");
    }
}
