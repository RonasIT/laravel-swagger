<?php

namespace RonasIT\AutoDoc\RequestContext;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use RonasIT\AutoDoc\RequestContext\Extractors\ClassControllerExtractor;
use RonasIT\AutoDoc\RequestContext\Extractors\ClosureControllerExtractor;
use RonasIT\AutoDoc\RequestContext\Extractors\RouteExtractor;
use RonasIT\AutoDoc\RequestContext\Resolvers\MethodDependencyResolver;
use RonasIT\AutoDoc\RequestContext\Resolvers\SecurityTokenResolver;

class RequestContextFactory
{
    public function __construct(
        protected SecurityTokenResolver $securityTokenResolver,
        protected MethodDependencyResolver $methodDependencyResolver,
    ) {
    }

    public function make(Request $request): RequestContext
    {
        $routeExtractor = new RouteExtractor($request->route());

        return new RequestContext(
            httpMethod: strtolower($request->method()),
            uri: $this->getUri($request),
            payload: $request->all(),
            requestClassName: $this->usesRequestClass($routeExtractor)
                ? $this->getRequestClassName($routeExtractor)
                : null,
            resourceName: $this->getResourceName($routeExtractor),
            userResolver: $request->getUserResolver(),
            routeResolver: $request->getRouteResolver(),
            headers: $request->headers->all(),
            routeWheres: $routeExtractor->wheres,
            usesAuth: $this->securityTokenResolver->usesAuth($request),
        );
    }

    protected function getUri(Request $request): string
    {
        $uri = strtolower($request->route()->uri());

        $basePath = preg_replace("/^\//", '', config('auto-doc.basePath'));

        $uriWithoutBasePath = preg_replace("/^{$basePath}/", '', $uri);

        $preparedUri = preg_replace("/^\//", '', $uriWithoutBasePath);

        return "/{$preparedUri}";
    }

    protected function getRequestClassName(RouteExtractor $routeExtractor): ?string
    {
        $parameters = $this
            ->methodDependencyResolver
            ->resolveClassMethodDependencies(
                instance: app($routeExtractor->controllerClass),
                method: $routeExtractor->controllerMethod,
            );

        return Arr::first($parameters, fn (string $className) => preg_match('/Request/', $className));
    }

    protected function getResourceName(RouteExtractor $routeExtractor): ?string
    {
        $extractor = ($routeExtractor->isClosureAction)
            ? new ClosureControllerExtractor($routeExtractor->getClosure())
            : new ClassControllerExtractor(
                $routeExtractor->controllerClass,
                $routeExtractor->controllerMethod,
            );

        return $extractor->resource;
    }

    protected function usesRequestClass(RouteExtractor $routeExtractor): bool
    {
        return !$routeExtractor->isClosureAction
            && method_exists($routeExtractor->controllerClass, $routeExtractor->controllerMethod);
    }
}
