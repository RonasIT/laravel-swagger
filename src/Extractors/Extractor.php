<?php

namespace RonasIT\AutoDoc\Extractors;

use Illuminate\Support\Str;
use ReflectionFunctionAbstract;

abstract class Extractor
{
    protected ReflectionFunctionAbstract $reflectionFunction;

    private const string RESOURCE_RETURN_PATTERN = '/(?:return\s+|=>\s+)([^\s(]+)::make/';
    private array $fileContent;

    protected function getFunctionCode(): string
    {
        $fileContent = $this->getFileContent();

        $startLineIndex = $this->reflectionFunction->getStartLine() - 1;

        $methodSlice = array_slice($fileContent, $startLineIndex, $this->reflectionFunction->getEndLine() - $startLineIndex);

        return implode('', $methodSlice);
    }

    protected function getResourceNameFromCode(string $methodCode): ?string
    {
        preg_match(self::RESOURCE_RETURN_PATTERN, $methodCode, $matches);

        return $matches[1] ?? null;
    }

    protected function getFileContent(): array
    {
        if (!isset($this->fileContent)) {
            $this->fileContent = file($this->reflectionFunction->getFileName());
        }

        return $this->fileContent;
    }

    protected function extractClassName(string $namespace): string
    {
        return Str::afterLast($namespace, '\\');
    }
}
