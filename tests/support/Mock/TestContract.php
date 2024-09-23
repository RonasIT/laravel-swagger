<?php

namespace RonasIT\Tests\Support\Mock;

interface TestContract
{
    public function find(string $resourceType, string $resourceId): ?object;
}
