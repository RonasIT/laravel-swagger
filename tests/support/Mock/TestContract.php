<?php

namespace RonasIT\AutoDoc\Tests\Support\Mock;

interface TestContract
{
    public function find(string $resourceType, string $resourceId): ?object;
}
