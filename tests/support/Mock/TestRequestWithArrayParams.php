<?php

namespace RonasIT\AutoDoc\Tests\Support\Mock;

use Illuminate\Foundation\Http\FormRequest;

class TestRequestWithArrayParams extends FormRequest
{
    public function rules(): array
    {
        $availableRelations = implode(',', $this->getAvailableRelations());

        return [
            'query' => 'string|required',
            'with' => 'array',
            'with.*' => "required|string|in:{$availableRelations}",
            'with_count' => 'array',
            'with_count.*' => 'required|string|in:team',
        ];
    }

    protected function getAvailableRelations(): array
    {
        return [
            'team.sport',
            'team.logo',
            'team.logo.preview',
        ];
    }
}
