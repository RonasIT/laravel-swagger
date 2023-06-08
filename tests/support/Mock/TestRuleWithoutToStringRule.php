<?php

namespace RonasIT\Support\Tests\Support\Mock;

use Closure;

class TestRuleWithoutToStringRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
    }
}
