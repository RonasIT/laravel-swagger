<?php

namespace RonasIT\AutoDoc\Enums;

use RonasIT\Support\Traits\EnumTrait;

enum RelationQueryParam: string
{
    use EnumTrait;

    case With = 'with';
    case WithCount = 'with_count';
}
