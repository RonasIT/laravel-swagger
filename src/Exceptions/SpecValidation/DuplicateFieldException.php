<?php

namespace RonasIT\Support\AutoDoc\Exceptions\SpecValidation;

class DuplicateFieldException extends InvalidSwaggerSpecException
{
    public function __construct(string $fieldName, array $fieldValue)
    {
        $fieldValueString = implode(', ', $fieldValue);

        parent::__construct("Found multiple fields '{$fieldName}' with values: {$fieldValueString}.");
    }
}
