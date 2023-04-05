<?php

namespace RonasIT\Support\AutoDoc\Exceptions;

use Exception;

class InvalidDocumentationFieldValueException extends Exception
{
    /**
     * @param string $field
     * @param mixed $value
     */
    public function __construct(string $field, $value)
    {
        $valueString = (is_array($value)) ? implode(',', $value) : (string)$value;

        parent::__construct("Validation failed. {$field} has an invalid value: {$valueString}");
    }
}
