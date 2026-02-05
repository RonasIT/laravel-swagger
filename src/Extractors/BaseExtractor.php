<?php

namespace RonasIT\AutoDoc\Extractors;

use Illuminate\Support\Str;
use ReflectionFunctionAbstract;

abstract class BaseExtractor
{
    private array $fileContent;

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

    protected function extractClassName(string $namespace): string
    {
        return Str::afterLast($namespace, '\\');
    }
}
