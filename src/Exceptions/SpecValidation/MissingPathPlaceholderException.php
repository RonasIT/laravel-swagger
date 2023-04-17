<?php

namespace RonasIT\Support\AutoDoc\Exceptions\SpecValidation;

use Exception;

class MissingPathPlaceholderException extends Exception
{
    public function __construct(string $operationId, string $paramName)
    {
        parent::__construct("Validation failed. Operation {$operationId} has a path parameter named '{$paramName}', but there is no corresponding '{$paramName}' in the path string.");
    }
}
