<?php

namespace RonasIT\Support\AutoDoc\Exceptions;

use Exception;

class MissedProductionFilePathException extends Exception
{
    public function __construct($message = null, $code = 0, Exception $previous = null)
    {
        $message = $message ?? 'Production file path missed in config';

        parent::__construct($message, $code, $previous);
    }
}
