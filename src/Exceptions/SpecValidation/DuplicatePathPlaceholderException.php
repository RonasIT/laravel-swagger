<?php

namespace RonasIT\Support\AutoDoc\Exceptions\SpecValidation;

class DuplicatePathPlaceholderException extends InvalidSwaggerSpecException
{
    public function __construct(array $placeholders, string $path)
    {
        $placeholdersString = implode(', ', $placeholders);

        parent::__construct("Path '{$path}' has multiple path placeholders with name: {$placeholdersString}.");
    }
}
