<?php

namespace RonasIT\AutoDoc\Extractors;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ReflectionException;
use ReflectionMethod;

class ClassControllerExtractor extends BaseControllerExtractor
{
    public function __construct(
        protected string $class,
        protected string $method,
    ) {
        parent::__construct();
    }

    protected function setResource(): ?string
    {
        try {
            $reflectionMethod = ReflectionMethod::createFromMethodName("{$this->class}::{$this->method}");
        } catch (ReflectionException) {
            return null;
        }

        $returnType = $reflectionMethod->getReturnType()?->getName();

        if (is_null($returnType)) {
            return $this->getResourceFromCode($reflectionMethod);
        }

        return ($this->isResourceClass($returnType)) ? $this->extractClassName($returnType) : null;
    }

    protected function getResourceFromCode(ReflectionMethod $reflectionMethod): ?string
    {
        $code = $this->getFunctionCode($reflectionMethod);

        $resourceName = $this->getResourceNameFromCode($code);

        if (empty($resourceName)) {
            return null;
        }

        $className = $this->getClassNameFromImports($reflectionMethod, $resourceName);

        return ($this->isResourceClass($className)) ? $this->extractClassName($className) : null;
    }

    protected function isResourceClass(string $className): bool
    {
        return is_subclass_of($className, JsonResource::class);
    }

    protected function getClassNameFromImports(ReflectionMethod $reflectionMethod, string $resourceName): string
    {
        $resourceImport = Arr::first(
            array: $this->getFileContent($reflectionMethod),
            callback: fn (string $line) => (Str::startsWith($line, 'use') && Str::contains($line, $resourceName)),
            default: '',
        );

        return Str::replace(['use', "as {$resourceName}", ' ', "\n", ';'], '', $resourceImport);
    }
}
