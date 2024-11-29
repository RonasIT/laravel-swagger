<?php

namespace RonasIT\AutoDoc\Tests\Support\Mock;

class TestImplementation implements TestContract
{

    public function find(string $resourceType, string $resourceId): ?object
    {
        if (!class_exists($resourceType)) {
            return null;
        }

        if (method_exists($resourceType, 'find')) {
            return $resourceType::find($resourceId);
        }

        $instance = app($resourceType);
        if (method_exists($instance, 'find')) {
            return $resourceType->find($resourceId);
        }

        return null;
    }
}