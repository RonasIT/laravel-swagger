<?php

namespace RonasIT\AutoDoc\Extractors;

use Illuminate\Http\Resources\Json\JsonResource;
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

    protected function getResourceNameFromCode(string $methodCode): ?string
    {
        preg_match('/(?:return\s+|=>\s+)([^\s(]+)::make/', $methodCode, $matches);

        return $matches[1] ?? null;
    }

    protected function getFileContent(ReflectionFunctionAbstract $reflectionFunction): array
    {
        if (!isset($this->fileContent)) {
            $this->fileContent = file($reflectionFunction->getFileName());
        }

        return $this->fileContent;
    }
}
