<?php

namespace RonasIT\Support\Tests\Support\Mock;

use Closure;

/**
 * A test request class to test for getting the rule name.
 */
class TestRuleWithoutToStringRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // May be implemented in the future if rule validation is needed.
    }
}
