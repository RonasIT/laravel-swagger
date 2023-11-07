<?php

namespace RonasIT\Support\AutoDoc\Models\Refs;

abstract class RefType
{
    const TYPE_DEFINITION = '#/definitions/';
    const TYPE_PARAMETER = '#/parameters/';
    const TYPE_PATH = '#/paths/';
    const TYPE_RESPONSE = '#/responses/';
}