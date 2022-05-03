<?php

namespace RonasIT\Support\AutoDoc\Exceptions;

use Exception;

class LegacyConfigException extends Exception
{
    public function __construct()
    {
        parent::__construct('Your local auto-doc.php config file version is out of date, please update it using php artisan vendor:publish or manually.');
    }
}
