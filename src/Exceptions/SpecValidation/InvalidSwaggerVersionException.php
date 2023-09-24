<?php

namespace RonasIT\Support\AutoDoc\Exceptions\SpecValidation;

use RonasIT\Support\AutoDoc\Services\SwaggerService;

class InvalidSwaggerVersionException extends InvalidSwaggerSpecException
{
    public function __construct(string $version)
    {
        $expectedVersion = SwaggerService::SWAGGER_VERSION;

        parent::__construct("Unrecognized Swagger version '{$version}'. Expected {$expectedVersion}.");
    }
}
