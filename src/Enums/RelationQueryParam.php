<?php

namespace RonasIT\AutoDoc\Enums;

use RonasIT\Support\Traits\EnumTrait;

enum RelationQueryParam: string
{
    use EnumTrait;

    case With = 'with';
    case WithCount = 'with_count';

    public static function arrayParamNames(): array
    {
        return array_map(fn (self $case) => "{$case->value}[]", self::cases());
    }
}
