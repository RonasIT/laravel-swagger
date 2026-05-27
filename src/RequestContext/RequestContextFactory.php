<?php

namespace RonasIT\AutoDoc\RequestContext;

use Illuminate\Http\Request;
use RonasIT\AutoDoc\RequestContext\Extractors\ClassControllerExtractor;
use RonasIT\AutoDoc\RequestContext\Extractors\ClosureControllerExtractor;
use RonasIT\AutoDoc\RequestContext\Extractors\RouteExtractor;
use RonasIT\AutoDoc\RequestContext\Resolvers\SecurityTokenResolver;

class RequestContextFactory
{
    public static function make(Request $request): RequestContext
    {
        $routeExtractor = new RouteExtractor($request->route());

        $controllerExtractor = ($routeExtractor->isClosureAction)
            ? new ClosureControllerExtractor($routeExtractor->getClosure())
            : new ClassControllerExtractor($routeExtractor->controllerClass, $routeExtractor->controllerMethod);

        return new RequestContext(
            httpMethod: strtolower($request->method()),
            uri: $routeExtractor->getUri(),
            payload: $request->all(),
            requestClassName: method_exists($controllerExtractor, 'getRequestClassName')
                ? $controllerExtractor->getRequestClassName()
                : null,
            resourceName: $controllerExtractor->resource,
            userResolver: $request->getUserResolver(),
            routeResolver: $request->getRouteResolver(),
            headers: $request->headers->all(),
            routeWheres: $routeExtractor->wheres,
            hasSecurityToken: app(SecurityTokenResolver::class)->hasSecurityToken($request),
        );
    }
}
