<?php

namespace RonasIT\AutoDoc\Inspectors;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;
use RonasIT\AutoDoc\Contracts\ControllerInspectorContract;
use RonasIT\AutoDoc\DTO\ResolvedResource;
use RonasIT\AutoDoc\Support\Resolvers\MethodDependencyResolver;
use RonasIT\AutoDoc\Support\Resolvers\ResourceClassResolver;

class ClassControllerInspector implements ControllerInspectorContract
{
    public function __construct(
        private string $class,
        private string $method,
        private MethodDependencyResolver $dependencyResolver,
        private ResourceClassResolver $resourceClassResolver,
    ) {
    }

    public function getResourceClass(): ?ResolvedResource
    {
        $reflectionMethod = $this->getReflectionMethod();

        return $reflectionMethod
            ? $this->resolveFromReturnType($reflectionMethod->getReturnType()) ?? $this->resourceClassResolver->resolve($reflectionMethod)
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

    private function resolveFromReturnType(mixed $returnType): ?ResolvedResource
    {
        if ($returnType instanceof ReflectionNamedType && $this->isResourceClass($returnType->getName())) {
            return new ResolvedResource($returnType->getName());
        }

        if ($returnType instanceof ReflectionUnionType) {
            foreach ($returnType->getTypes() as $type) {
                if ($this->isConcreteResourceType($type)) {
                    return new ResolvedResource($type->getName());
                }
            }
        }

        return null;
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

    private function isConcreteResourceType(mixed $type): bool
    {
        return $type instanceof ReflectionNamedType
            && !$type->isBuiltin()
            && $this->isResourceClass($type->getName());
    }

    private function isResourceClass(string $className): bool
    {
        return is_subclass_of($className, JsonResource::class)
            && $className !== AnonymousResourceCollection::class;
    }
}
