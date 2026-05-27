<?php

namespace RonasIT\AutoDoc\RequestContext\Extractors;

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

    protected function extractClassName(string $namespace): string
    {
        return Str::afterLast($namespace, '\\');
    }

    protected function getResourceFromCode(ReflectionFunctionAbstract $reflectionMethod): ?string
    {
        $code = $this->getFunctionCode($reflectionMethod);

        $patterns = [
            '/(?:return\s+|=>\s+)([^\s(]+)::make/',
            '/(?:return\s+|=>\s+)([^\s(]+)::collection/',
            '/(?:return\s+|=>\s+)new\s+([^\s(]+)/',
        ];

        foreach ($patterns as $pattern) {
            preg_match($pattern, $code, $matches);

            if (empty($matches[1])) {
                continue;
            }

            $resourceName = $matches[1];

            $resourceName = (class_exists($resourceName))
                ? $resourceName
                : $this->getClassNameFromImports($reflectionMethod, $resourceName);

            if ($this->isResourceClass($resourceName)) {
                return $resourceName;
            }
        }

        return null;
    }

    protected function isResourceClass(string $className): bool
    {
        return is_subclass_of($className, JsonResource::class);
    }

    protected function getFunctionCode(ReflectionFunctionAbstract $reflectionFunction): string
    {
        $fileContent = $this->getFileContent($reflectionFunction);

        $startLineIndex = $reflectionFunction->getStartLine() - 1;

        $methodSlice = array_slice($fileContent, $startLineIndex, $reflectionFunction->getEndLine() - $startLineIndex);

        return implode('', $methodSlice);
    }

    protected function getClassNameFromImports(ReflectionFunctionAbstract $reflectionMethod, string $resourceName): string
    {
        $resourceImport = Arr::first(
            array: $this->getFileContent($reflectionMethod),
            callback: fn (string $line) => (Str::startsWith($line, 'use') && Str::contains($line, $resourceName)),
            default: '',
        );

        preg_match('/^use\s+([^;]+?)(?:\s+as\s+\w+)?;$/', trim($resourceImport), $matches);

        return $matches[1] ?? '';
    }

    protected function getFileContent(ReflectionFunctionAbstract $reflectionFunction): array
    {
        if (!isset($this->fileContent)) {
            $fileName = $reflectionFunction->getFileName();

            $this->fileContent = (empty($fileName) || !is_readable($fileName)) ? [] : file($fileName) ?? [];
        }

        return $this->fileContent;
    }
}
