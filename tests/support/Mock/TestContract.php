<?php

namespace RonasIT\Support\Tests\Support\Mock;

interface TestContract
{
    public function find(string $resourceType, string $resourceId): ?object;
}
