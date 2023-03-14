<?php

namespace RonasIT\Support\AutoDoc\Exceptions;

use Exception;

class MissedRemoteDocumentationUrlException extends Exception
{
    public function __construct($message = null, $code = 0, Exception $previous = null)
    {
        $message = $message ?? 'Remote documentation url missed in config';

        parent::__construct($message, $code, $previous);
    }
}
