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
    protected $implementationNote;

    protected $codeDescriptions = [
        '200' => 'Operation successfully done',
        '204' => 'Operation successfully done',
        '404' => 'This entity not found'
    ];

    static public function getRules() {
        return (new static)->rules();
    }

    static public function getDescription() {
        return (new static)->implementationNote;
    }

    static public function getDescriptionOfResponse($code) {
        $codeDescriptions = (new static)->codeDescriptions;

        return array_get($codeDescriptions, $code);
    }
}