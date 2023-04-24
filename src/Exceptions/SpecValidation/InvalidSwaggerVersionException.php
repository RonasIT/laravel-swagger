<?php

namespace RonasIT\Support\AutoDoc\Exceptions\SpecValidation;

class InvalidSwaggerVersionException extends InvalidSwaggerSpecException
{
    public function __construct(string $version)
    {
        parent::__construct("Unrecognized Swagger version '{$version}'. Expected 2.0.");
    }
}
