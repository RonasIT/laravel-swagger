<?php
/**
 * Created by PhpStorm.
 * User: artem
 * Date: 25.02.17
 * Time: 15:00
 */

namespace RonasIT\Support\AutoDoc\Exceptions;

use Exception;

class EmptyDataCollectorClassException extends Exception
{
    public function __construct($message = "", $code = 0, Exception $previous = null)
    {
        $message = $message ?? "dataCollectorClass field is empty. Please check configuration file";
        parent::__construct($message, $code, $previous);
    }
}