<?php

namespace RonasIT\Support\Tests\Support\Mock;

use Closure;

class TestRuleWithToStringRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
    }

    public function __toString()
    {
        return 'with_to_array_rule_string_name';
    }
}
