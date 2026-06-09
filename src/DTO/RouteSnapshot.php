<?php

namespace RonasIT\AutoDoc\DTO;

final readonly class RouteSnapshot
{
    public function __construct(
        public string $uri,
        public string $httpMethod,
        public array $routeWheres,
    ) {
    }
}
