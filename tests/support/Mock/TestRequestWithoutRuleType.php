<?php

namespace RonasIT\AutoDoc\Tests\Support\Mock;

use Illuminate\Foundation\Http\FormRequest;

class TestRequestWithoutRuleType extends FormRequest
{
    public function rules(): array
    {
        return [
            'query' => 'required',
        ];
    }
}
