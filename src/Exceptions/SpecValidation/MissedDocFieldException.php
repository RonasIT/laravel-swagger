<?php

namespace RonasIT\Support\AutoDoc\Exceptions\SpecValidation;

use Exception;

class MissedDocFieldException extends Exception
{
    public function __construct(array $fields, string $parent = null)
    {
        $fieldsString = implode(', ', $fields);

        parent::__construct("Validation failed. Fields ({$fieldsString}) nested in '{$parent}' are listed as required but don't exist in documentation.");
    }
}
