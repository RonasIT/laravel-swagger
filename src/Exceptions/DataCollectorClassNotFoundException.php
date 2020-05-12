<?php

namespace KWXS\Support\AutoDoc\Exceptions;

use Exception;

class DataCollectorClassNotFoundException extends Exception
{
    public function __construct($message = "", $code = 0, Exception $previous = null)
    {
        if (empty($message)) {
            $message = "DataCollectorClass was not found. Please check configuration file";
        }

        parent::__construct($message, $code, $previous);
    }
}
