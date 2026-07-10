<?php

namespace RonasIT\AutoDoc\Inspectors;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use ReflectionException;
use ReflectionMethod;
use RonasIT\AutoDoc\Contracts\ControllerInspectorContract;
use RonasIT\AutoDoc\DTO\ResourceSchema;
use RonasIT\AutoDoc\Support\Resolvers\MethodDependencyResolver;
use RonasIT\AutoDoc\Support\Resolvers\ResourceSchemaResolver;

class ClassControllerInspector implements ControllerInspectorContract
{
    public function __construct(
        private string $class,
        private string $method,
        private MethodDependencyResolver $dependencyResolver,
        private ResourceSchemaResolver $resourceSchemaResolver,
    ) {
    }

    public function getResource(): ?ResourceSchema
    {
        $reflectionMethod = $this->getReflectionMethod();

        return (!empty($reflectionMethod))
            ? $this->resourceSchemaResolver->resolve($reflectionMethod)
            : null;
    }

    private function getReflectionMethod(): ?ReflectionMethod
    {
        try {
            return ReflectionMethod::createFromMethodName("{$this->class}::{$this->method}");
        } catch (ReflectionException) {
            return null;
        }
    }

    public function getRequestClass(): ?string
    {
        if (!method_exists($this->class, $this->method)) {
            return null;
        }

        $parameters = $this->dependencyResolver->resolveClassMethodDependencies(
            instance: app($this->class),
            method: $this->method,
        );

        return Arr::first($parameters, fn ($className) => is_string($className) && is_subclass_of($className, FormRequest::class));
    }
}
