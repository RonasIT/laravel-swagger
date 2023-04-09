<?php

namespace RonasIT\Support\AutoDoc\Exceptions\SpecValidation;

use Exception;

class InvalidResponseCodeException extends Exception
{
    public function __construct(string $statusCode, string $responseId)
    {
        parent::__construct("Validation failed. Response {$responseId} has an invalid response code ({$statusCode})");
    }
}
