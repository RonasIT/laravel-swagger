<?php

namespace RonasIT\AutoDoc\Tests\Support\Mock;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @deprecated
 * @summary Request class to validate input data
 * @description Description of the request class
 * @_200 The operation was completed successfully
 */
class TestRequestWithAnnotations extends FormRequest
{
    public function rules(): array
    {
        return [
            'query' => 'string|required'
        ];
    }
}
