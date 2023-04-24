<?php

namespace RonasIT\Support\AutoDoc\Exceptions\SpecValidation;

class MissingRefFileException extends InvalidSwaggerSpecException
{
    public function __construct(string $filename)
    {
        parent::__construct("Validation failed. Filename '{$filename}' is used in \$ref but file doesn't exist.");
    }
}
