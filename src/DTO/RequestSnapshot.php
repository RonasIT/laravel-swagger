<?php

namespace RonasIT\AutoDoc\DTO;

final readonly class RequestSnapshot
{
    public function __construct(
        public RouteSnapshot $route,
        public HttpActionMeta $action,
        public RequestData $requestData,
        public bool $hasSecurityToken,
    ) {
    }
}
