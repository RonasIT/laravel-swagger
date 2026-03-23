<?php

namespace RonasIT\AutoDoc\Extractors;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ReflectionFunctionAbstract;

abstract class BaseControllerExtractor
{
    public readonly ?string $resource;

    private array $fileContent;

    public function __construct()
    {
        $class = $this->getResourceClass();

        $this->resource = (!empty($class) && $this->isResourceClass($class)) ? $this->extractClassName($class) : null;
    }

    abstract protected function getResourceClass(): ?string;

    protected function isResourceClass(string $className): bool
    {
        return is_subclass_of($className, JsonResource::class);
    }

    protected function extractClassName(string $namespace): string
    {
        return Str::afterLast($namespace, '\\');
    }

    protected function getFunctionCode(ReflectionFunctionAbstract $reflectionFunction): string
    {
        $fileContent = $this->getFileContent($reflectionFunction);

        $startLineIndex = $reflectionFunction->getStartLine() - 1;

        $methodSlice = array_slice($fileContent, $startLineIndex, $reflectionFunction->getEndLine() - $startLineIndex);

        return implode('', $methodSlice);
    }

    protected function getResourceFromCode(ReflectionFunctionAbstract $reflectionMethod): ?string
    {
        $code = $this->getFunctionCode($reflectionMethod);

        preg_match('/(?:return\s+|=>\s+)([^\s(]+)::make/', $code, $matches);

        $resourceName = $matches[1] ?? null;

        if (empty($resourceName)) {
            return null;
        }

        return (class_exists($resourceName))
            ? $resourceName
            : $this->getClassNameFromImports($reflectionMethod, $resourceName);
    }

    protected function getFileContent(ReflectionFunctionAbstract $reflectionFunction): array
    {
        if (!isset($this->fileContent)) {
            $this->fileContent = file($reflectionFunction->getFileName());
        }

        return $this->fileContent;
    }

    protected function getClassNameFromImports(ReflectionFunctionAbstract $reflectionMethod, string $resourceName): string
    {
        $resourceImport = Arr::first(
            array: $this->getFileContent($reflectionMethod),
            callback: fn (string $line) => (Str::startsWith($line, 'use') && Str::contains($line, $resourceName)),
            default: '',
        );

        return Str::replace(['use', "as {$resourceName}", ' ', "\n", ';'], '', $resourceImport);
    }
}
