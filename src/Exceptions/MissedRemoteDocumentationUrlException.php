<?php

namespace RonasIT\Support\AutoDoc\Exceptions;

use Exception;

class MissedRemoteDocumentationUrlException extends Exception
{
    public function __construct($message = null, $code = 0, Exception $previous = null)
    {
        $message = $message ?? 'Remote documentation url missed in config. Please set SWAGGER_REMOTE_DRIVER_URL env variable to define this one.';

        parent::__construct($message, $code, $previous);
    }
}
