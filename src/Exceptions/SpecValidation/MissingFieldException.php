<?php

namespace RonasIT\Support\AutoDoc\Exceptions\SpecValidation;

class MissingFieldException extends InvalidSwaggerSpecException
{
    public function __construct(array $missingFields, string $parentField = null)
    {
        $fieldsString = implode(', ', $missingFields);

        parent::__construct("'{$parentField}' should have required fields: {$fieldsString}.");
    }
}
