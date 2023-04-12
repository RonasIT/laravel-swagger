<?php

namespace RonasIT\Support\AutoDoc\Exceptions\SpecValidation;

use Exception;

class MissedDocDefinitionsException extends Exception
{
    public function __construct(array $definitions)
    {
        $definitionsString = implode(', ', $definitions);

        parent::__construct("Validation failed. Definitions ({$definitionsString}) are used in \$refs but not defined in 'definitions' section.");
    }
}
