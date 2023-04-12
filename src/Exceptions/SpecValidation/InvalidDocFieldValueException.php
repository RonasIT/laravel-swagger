<?php

namespace RonasIT\Support\AutoDoc\Exceptions\SpecValidation;

use Exception;

class InvalidDocFieldValueException extends Exception
{
    /**
     * @param string $fieldName
     * @param mixed $value
     */
    public function __construct(string $fieldName, $value)
    {
        $valueString = (is_array($value)) ? implode(', ', $value) : (string)$value;

        parent::__construct("Validation failed. Field '{$fieldName}' has an invalid values ({$valueString}).");
    }
}
