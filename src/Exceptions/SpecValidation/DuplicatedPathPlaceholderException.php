<?php

namespace RonasIT\Support\AutoDoc\Exceptions\SpecValidation;

use Exception;

class DuplicatedPathPlaceholderException extends Exception
{
    public function __construct(array $placeholders, string $path)
    {
        $placeholdersString = implode(',', $placeholders);

        parent::__construct("Validation failed. Path '{$path}' has multiple path placeholders named {$placeholdersString}");
    }
}
