<?php

namespace RonasIT\AutoDoc\DTO;

final readonly class RequestData
{
    public function __construct(
        public array $payload,
        public ?string $contentType,
        public array $rules,
        public array $attributes,
        public array $annotations,
    ) {
    }
}
