<?php

namespace RonasIT\Support\AutoDoc\Exceptions\SpecValidation;

use Exception;

class PathParamMissingException extends Exception
{
    public function __construct(string $operationId, array $placeholders)
    {
        $placeholdersString = implode(', ', $placeholders);

        parent::__construct("Validation failed. Operation '{$operationId}' has no parameters for placeholders ({$placeholdersString}).");
    }
}
