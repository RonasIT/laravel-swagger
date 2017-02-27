<?php
/**
 * Created by PhpStorm.
 * User: artem
 * Date: 26.02.17
 * Time: 14:08
 */

namespace RonasIT\Support\AutoDoc\Exceptions;

use Exception;

class CurlRequestErrorException extends Exception
{
    public function __construct($message = "", $code = 0, Exception $previous = null)
    {
        parent::__construct("Curl error is occured. Is remote host available now?", $code, $previous);
    }
}