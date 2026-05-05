<?php

namespace RonasIT\AutoDoc\RequestContext;

use Illuminate\Http\Request;
use RonasIT\AutoDoc\RequestContext\Extractors\RouteExtractor;
use RonasIT\AutoDoc\RequestContext\Resolvers\SecurityTokenResolver;

class RequestContextFactory
{
    public static function make(Request $request): RequestContext
    {
        $routeExtractor = new RouteExtractor($request->route());

        return new RequestContext(
            httpMethod: strtolower($request->method()),
            uri: $routeExtractor->getUri(),
            payload: $request->all(),
            requestClassName: $routeExtractor->getRequestClassName(),
            resourceName: $routeExtractor->getResourceName(),
            userResolver: $request->getUserResolver(),
            routeResolver: $request->getRouteResolver(),
            headers: $request->headers->all(),
            routeWheres: $routeExtractor->wheres,
            hasSecurityToken: app(SecurityTokenResolver::class)->hasSecurityToken($request),
        );
    }
}
