<?php

namespace RonasIT\Support\AutoDoc\Exceptions\SpecValidation;

class InvalidPathException extends InvalidSwaggerSpecException
{
    public function __construct(string $path)
    {
        parent::__construct("Incorrect '{$path}'. Paths should only have path names that starts with `/`.");
    }
}
