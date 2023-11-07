<?php

namespace RonasIT\Support\AutoDoc\Exceptions\SpecValidation;

class InvalidStatusCodeException extends InvalidSwaggerSpecException
{
    public function __construct(string $responseId)
    {
        parent::__construct(
            "Operation at '{$responseId}' should only have three-digit status codes, `default`, "
            . "and vendor extensions (`x-*`) as properties."
        );
    }
}
