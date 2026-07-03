<?php

namespace RonasIT\AutoDoc\DTO;

final readonly class RequestSnapshot
{
    public function __construct(
        public RouteSnapshot $route,
        public RequestData $requestData,
        public ?string $requestClass,
        public ?ResourceSchema $resourceSchema,
        public bool $hasSecurityToken,
    ) {
    }
}
