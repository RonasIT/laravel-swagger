<?php

namespace RonasIT\Support\AutoDoc\Exceptions\SpecValidation;

use Exception;

class InvalidSwaggerVersionException extends Exception
{
    public function __construct(string $version)
    {
        parent::__construct("Unrecognized Swagger version: {$version}. Expected 2.0");
    }
}