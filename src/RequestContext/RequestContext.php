<?php

namespace RonasIT\AutoDoc\RequestContext;

use Closure;
use Illuminate\Support\Arr;

final readonly class RequestContext
{
    public function __construct(
        public string $httpMethod,
        public string $uri,
        public array $payload,
        public ?string $requestClassName,
        public ?string $resourceName,
        public Closure $userResolver,
        public Closure $routeResolver,
        public array $headers,
        public array $routeWheres,
        public bool $hasSecurityToken,
    ) {
    }

    public function header(string $name, ?string $default = null): ?string
    {
        $result = Arr::get($this->headers, strtolower($name), $default);

        return (is_array($result)) ? Arr::first($result) : $result;
    }
}
