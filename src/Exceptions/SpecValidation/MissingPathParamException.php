<?php

namespace RonasIT\Support\AutoDoc\Exceptions\SpecValidation;

class MissingPathParamException extends InvalidSwaggerSpecException
{
    public function __construct(string $operationId, array $placeholders)
    {
        $placeholdersString = implode(', ', $placeholders);

        parent::__construct("Operation '{$operationId}' has no params for placeholders: {$placeholdersString}.");
    }
}
