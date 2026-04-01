<?php

namespace RonasIT\AutoDoc\Extractors;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use RonasIT\AutoDoc\Traits\GetDependenciesTrait;

readonly class RequestExtractor
{
    use GetDependenciesTrait;

    public ?string $resource;
    public ?string $controllerClass;
    public ?string $controllerMethod;
    public ?string $requestClassName;
    public bool $usesClosure;

    protected RouteExtractor $routeExtractor;

    public function __construct(
        public Request $request,
    ) {
        $this->routeExtractor = new RouteExtractor($this->request->route());
        $this->controllerClass = $this->routeExtractor->controllerClass;
        $this->controllerMethod = $this->routeExtractor->methodName;
        $this->usesClosure = $this->routeExtractor->usesClosure;
        $this->resource = $this->getResourceName();
        $this->requestClassName = $this->usesRequestClass() ? $this->getRequestClassName() : null;
    }

    protected function getResourceName(): ?string
    {
        $extractor = ($this->routeExtractor->usesClosure)
            ? new ClosureControllerExtractor($this->routeExtractor->getClosure())
            : new ClassControllerExtractor($this->controllerClass, $this->controllerMethod);

        return $extractor->resource;
    }

    protected function getRequestClassName(): ?string
    {
        $controller = app($this->controllerClass);

        $parameters = $this->resolveClassMethodDependencies($controller, $this->controllerMethod);

        return Arr::first($parameters, fn ($key) => preg_match('/Request/', $key));
    }

    protected function usesRequestClass(): bool
    {
        return !$this->usesClosure
            && method_exists($this->controllerClass, $this->controllerMethod);
    }
}
