<?php

namespace RonasIT\Support\AutoDoc\Exceptions\SpecValidation;

class MissingLocalRefException extends InvalidSwaggerSpecException
{
    public function __construct(string $ref, string $parentField)
    {
        parent::__construct("Ref '{$ref}' is used in \$ref but not defined in '{$parentField}' field.");
    }
}
