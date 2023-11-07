<?php

namespace RonasIT\Support\AutoDoc\Exceptions\SpecValidation;

class MissingExternalRefException extends InvalidSwaggerSpecException
{
    public function __construct(string $ref, string $filename)
    {
        parent::__construct("Ref '{$ref}' is used in \$ref but not defined in '{$filename}' file.");
    }
}
