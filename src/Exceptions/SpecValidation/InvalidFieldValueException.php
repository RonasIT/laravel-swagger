<?php

namespace RonasIT\Support\AutoDoc\Exceptions\SpecValidation;

class InvalidFieldValueException extends InvalidSwaggerSpecException
{
    public function __construct(string $fieldName, array $allowedValues, array $invalidValues)
    {
        $allowedValuesString = implode(', ', $allowedValues);
        $invalidValuesString = implode(', ', $invalidValues);

        parent::__construct(
            "Field '{$fieldName}' has an invalid value: {$invalidValuesString}. Allowed values: {$allowedValuesString}."
        );
    }
}
