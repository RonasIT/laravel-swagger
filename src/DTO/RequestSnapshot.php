<?php

namespace RonasIT\AutoDoc\DTO;

final readonly class RequestSnapshot
{
    public function __construct(
        public RouteSnapshot $route,
        public RequestData $requestData,
        public ?string $requestClass,
        public ?string $resourceSchemaName,
        public bool $hasSecurityToken,
    ) {
    }
}
