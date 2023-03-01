<?php

namespace RonasIT\Support\Tests\Support\Mock;

use Illuminate\Foundation\Http\FormRequest;

/**
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
