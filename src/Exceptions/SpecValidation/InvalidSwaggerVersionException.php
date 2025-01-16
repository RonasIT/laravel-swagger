<?php

namespace RonasIT\AutoDoc\Exceptions\SpecValidation;

use RonasIT\AutoDoc\Services\SwaggerService;

class InvalidSwaggerVersionException extends InvalidSwaggerSpecException
{
    public function __construct(string $version)
    {
        $expectedVersion = SwaggerService::OPEN_API_VERSION;

        parent::__construct("Unrecognized Swagger version '{$version}'. Expected {$expectedVersion}.");
    }
}
