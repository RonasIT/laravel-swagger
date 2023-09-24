<?php

namespace RonasIT\Support\AutoDoc\Exceptions\SpecValidation;

class DuplicateParamException extends InvalidSwaggerSpecException
{
    public function __construct(string $in, string $name, string $operationId)
    {
        parent::__construct("Operation '{$operationId}' has multiple in:{$in} parameters with name:{$name}.");
    }
}
