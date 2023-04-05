<?php

namespace RonasIT\Support\AutoDoc\Exceptions;

use Exception;

class MissingDocumentationFieldException extends Exception
{
    public function __construct(array $fields, $parent = null)
    {
        $fields = array_map(function ($field) use ($parent) {
            return $parent ? $parent . '.' . $field : $field;
        }, $fields);

        $fieldsString = implode(', ', $fields);

        parent::__construct("Validation failed. Fields {$fieldsString} listed as required but does not exist in documentation");
    }
}
