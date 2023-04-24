<?php

namespace RonasIT\Support\AutoDoc\Exceptions\SpecValidation;

class InvalidFieldValueException extends InvalidSwaggerSpecException
{
    public function __construct(string $fieldId, array $allowedValues)
    {
        $allowedValuesString = implode(', ', $allowedValues);

        parent::__construct("Validation failed. Field '{$fieldId}' has an invalid value. Allowed values: {$allowedValuesString}.");
    }
}
