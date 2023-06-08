<?php

namespace RonasIT\Support\Tests\Support\Mock;

use Closure;

class TestRuleWithoutToArrayRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
    }
}
