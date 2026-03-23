<?php

namespace RonasIT\AutoDoc\Extractors;

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

    protected function getResourceFromCode(ReflectionMethod $reflectionMethod): ?string
    {
        $code = $this->getFunctionCode($reflectionMethod);

        $resourceName = $this->getResourceNameFromCode($code);

        return (empty($resourceName)) ? null : $this->getClassNameFromImports($reflectionMethod, $resourceName);
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
