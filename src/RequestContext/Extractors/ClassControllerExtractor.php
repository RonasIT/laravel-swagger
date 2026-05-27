<?php

namespace RonasIT\AutoDoc\RequestContext\Extractors;

use Illuminate\Support\Arr;
use ReflectionException;
use ReflectionMethod;
use RonasIT\AutoDoc\RequestContext\Resolvers\MethodDependencyResolver;

class ClassControllerExtractor extends BaseControllerExtractor
{
    protected MethodDependencyResolver $methodDependencyResolver;

    public function __construct(
        protected string $class,
        protected string $method,
    ) {
        parent::__construct();

        $this->methodDependencyResolver = app(MethodDependencyResolver::class);
    }

    public function getRequestClassName(): ?string
    {
        if (!method_exists($this->class, $this->method)) {
            return null;
        }

        $parameters = $this
            ->methodDependencyResolver
            ->resolveClassMethodDependencies(
                instance: app($this->class),
                method: $this->method,
            );

        return Arr::first($parameters, fn ($className) => is_string($className) && preg_match('/Request/', $className));
    }

    protected function getResourceClass(): ?string
    {
        try {
            $reflectionMethod = ReflectionMethod::createFromMethodName("{$this->class}::{$this->method}");
        } catch (ReflectionException) {
            return null;
        }

        $resourceClass = $reflectionMethod->getReturnType()?->getName();

        return (is_null($resourceClass)) ? $this->getResourceFromCode($reflectionMethod) : $resourceClass;
    }
}
