<?php

namespace RonasIT\Support\AutoDoc\Exceptions;

use Exception;

class SwaggerDriverClassNotFoundException extends Exception
{
    public function __construct($className)
    {
        if (empty($message)) {
            $message = "Driver class '{$className}' was not found. Please check configuration file.";
        }

        parent::__construct($message);
    }
}
