<?php

namespace RonasIT\AutoDoc\Tests\Support\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'id' => 1,
            'name' => 'Michel Smith',
            'email' => 'smith@exmaple.com',
            'created_at' => Carbon::parse('2020-01-01'),
            'updated_at' => Carbon::parse('2020-01-01'),
        ];
    }
}

