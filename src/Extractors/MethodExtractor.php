<?php

namespace RonasIT\AutoDoc\Extractors;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ReflectionMethod;

class MethodExtractor extends BaseExtractor
{
    public function __construct(string $class, string $method)
    {
        $this->reflectionFunction = ReflectionMethod::createFromMethodName("{$class}::{$method}");
    }

    public function getResource(): ?string
    {
        $returnType = $this->reflectionFunction->getReturnType()?->getName();

        if (is_null($returnType)) {
            return $this->getResourceFromCode();
        }

        return ($this->isResourceClass($returnType))
            ? $this->extractClassName($returnType)
            : null;
    }

    protected function getResourceFromCode(): ?string
    {
        $code = $this->getFunctionCode();

        $resourceName = $this->getResourceNameFromCode($code);

        if (empty($resourceName)) {
            return null;
        }

        $className = $this->getClassNameFromImports($resourceName);

        return ($this->isResourceClass($className))
            ? $this->extractClassName($className)
            : null;
    }

    protected function isResourceClass(string $className): bool
    {
        return is_subclass_of($className, JsonResource::class);
    }

    protected function getClassNameFromImports(string $resourceName): string
    {
        $resourceImport = Arr::first(
            array: $this->getFileContent(),
            callback: fn (string $line) => (Str::startsWith($line, 'use') && Str::contains($line, $resourceName)),
            default: '',
        );

        return Str::replace(['use', "as {$resourceName}", ' ', "\n", ';'], '', $resourceImport);
    }
}
