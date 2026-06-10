<?php

namespace RonasIT\AutoDoc\DTO;

final readonly class ResolvedResource
{
    public function __construct(
        public string $class,
        public bool $isCollection = false,
    ) {
    }
}
