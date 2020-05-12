<?php

namespace KWXS\Support\AutoDoc\Exceptions;

use Exception;

class MissedProductionFilePathException extends Exception
{
    public function __construct($message = 'Production file path missed in config',
                                $code = 0,
                                Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
