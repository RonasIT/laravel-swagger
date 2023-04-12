<?php

namespace RonasIT\Support\AutoDoc\Exceptions\SpecValidation;

use Exception;

class PathParamMissingException extends Exception
{
    public function __construct(string $path, array $placeholders)
    {
        $placeholdersString = implode(', ', $placeholders);

        parent::__construct("Validation failed. Path '{$path}' has no parameters for placeholders ({$placeholdersString}).");
    }
}
