<?php

namespace RonasIT\AutoDoc\DTO;

final readonly class ResourceSchema
{
    public function __construct(
        public string $className,
        public bool $isCollection = false,
    ) {
    }
}
