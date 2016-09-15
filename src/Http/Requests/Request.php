<?php

/**
 * Created by PhpStorm.
 * User: roman
 * Date: 27.08.16
 * Time: 14:16
 */

namespace RonasIT\Support\AutoDoc\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class Request extends FormRequest
{
    static public function getRules() {
        return (new static)->rules();
    }
}