<?php

namespace RonasIT\Support\AutoDoc\Exceptions\SpecValidation;

use Exception;

class MissingRefException extends Exception
{
    public function __construct(string $ref, string $parentField)
    {
        parent::__construct("Validation failed. Ref '{$ref}' are used in \$ref but not defined in '{$parentField}' field.");
    }
}
