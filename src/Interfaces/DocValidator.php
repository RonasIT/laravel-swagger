<?php

namespace RonasIT\Support\AutoDoc\Interfaces;

interface DocValidator
{
    /**
     * Validate Swagger documentation
     */
    public function validate(array $doc): void;
}