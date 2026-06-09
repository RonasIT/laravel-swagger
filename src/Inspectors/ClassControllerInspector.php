<?php

namespace RonasIT\AutoDoc\Inspectors;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;
use RonasIT\AutoDoc\Contracts\ControllerInspectorContract;
use RonasIT\AutoDoc\Support\MethodDependencyResolver;
use RonasIT\AutoDoc\Support\ResourceClassResolver;

class ClassControllerInspector implements ControllerInspectorContract
{
    public function __construct(
        private string $class,
        private string $method,
        private MethodDependencyResolver $dependencyResolver,
        private ResourceClassResolver $resourceClassResolver,
    ) {
    }

    public function getResourceClass(): ?string
    {
        try {
            $reflectionMethod = ReflectionMethod::createFromMethodName("{$this->class}::{$this->method}");
        } catch (ReflectionException) {
            return null;
        }

        $returnType = $reflectionMethod->getReturnType();

        if ($returnType instanceof ReflectionNamedType) {
            $className = $returnType->getName();

            if ($this->isResourceClass($className)) {
                return $className;
            }
        } elseif ($returnType instanceof ReflectionUnionType) {
            foreach ($returnType->getTypes() as $type) {
                if ($type instanceof ReflectionNamedType
                    && !$type->isBuiltin()
                    && $this->isResourceClass($type->getName())) {
                    return $type->getName();
                }
            }
        }

        return $this->resourceClassResolver->resolve($reflectionMethod);
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

        return Arr::first($parameters, fn ($className) => is_subclass_of($className, FormRequest::class) && preg_match('/Request/', $className));
    }

    private function isResourceClass(string $className): bool
    {
        return is_subclass_of($className, JsonResource::class);
    }
}
