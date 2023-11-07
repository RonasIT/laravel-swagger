<?php

namespace RonasIT\Support\AutoDoc\Analyzers\Diff\Models;

interface Changed
{
    public function isDiff(): bool;
}