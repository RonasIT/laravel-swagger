<?php

namespace RonasIT\Support\Tests\Support\Mock;

use Illuminate\Foundation\Http\FormRequest;

class TestRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'query' => 'string|required',
            'user_id' => ['integer', new TestRuleWithToStringRule()],
            'is_email_enabled' => new TestRuleWithoutToStringRule()
        ];
    }
}
