<?php

namespace RonasIT\Support\AutoDoc\Exceptions;

use Exception;

class EmptyContactEmailException extends Exception
{
    public function __construct()
    {
        parent::__construct('Please fill the `info.contact.email` field in the app-doc.php config file.');
    }
}
