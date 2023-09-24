<?php

namespace RonasIT\Support\AutoDoc\Exceptions\SpecValidation;

class MissingPathPlaceholderException extends InvalidSwaggerSpecException
{
    public function __construct(string $operationId, array $params)
    {
        $paramsString = implode(', ', $params);

        parent::__construct("Operation '{$operationId}' has no placeholders for params: {$paramsString}.");
    }
}
