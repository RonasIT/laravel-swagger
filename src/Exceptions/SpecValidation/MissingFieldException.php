<?php

namespace RonasIT\Support\AutoDoc\Exceptions\SpecValidation;

class MissingFieldException extends InvalidSwaggerSpecException
{
    public function __construct(array $missingFields, string $parentField = null)
    {
        $fieldsString = implode(', ', $missingFields);

        parent::__construct("Validation failed. '{$parentField}' should have required fields: {$fieldsString}.");
    }
}
