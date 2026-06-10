<?php

namespace RonasIT\AutoDoc\DTO;

final readonly class HttpActionMeta
{
    public function __construct(
        public ?string $requestClass,
        public ?string $resourceSchemaName,
    ) {
    }
}
