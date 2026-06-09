<?php

namespace RonasIT\AutoDoc\Support;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ReflectionFunctionAbstract;

class ResourceClassResolver
{
    public function resolve(ReflectionFunctionAbstract $reflection): ?string
    {
        $fileContent = $this->getFileContent($reflection);
        $code = $this->getFunctionCode($reflection, $fileContent);

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

            $resourceName = class_exists($resourceName)
                ? $resourceName
                : $this->getClassNameFromImports($resourceName, $fileContent);

            if (is_subclass_of($resourceName, JsonResource::class)) {
                return $resourceName;
            }
        }

        return null;
    }

    private function getFileContent(ReflectionFunctionAbstract $reflection): array
    {
        $fileName = $reflection->getFileName();

        return (empty($fileName) || !is_readable($fileName)) ? [] : file($fileName) ?? [];
    }

    private function getFunctionCode(ReflectionFunctionAbstract $reflection, array $fileContent): string
    {
        $startLineIndex = $reflection->getStartLine() - 1;
        $methodSlice = array_slice($fileContent, $startLineIndex, $reflection->getEndLine() - $startLineIndex);

        return implode('', $methodSlice);
    }

    private function getClassNameFromImports(string $resourceName, array $fileContent): string
    {
        $resourceImport = Arr::first(
            array: $fileContent,
            callback: fn (string $line) => Str::startsWith($line, 'use')
                && preg_match('/\b' . preg_quote($resourceName, '/') . '\b/', $line),
        );

        preg_match('/^use\s+([^;]+?)(?:\s+as\s+\w+)?;$/', trim($resourceImport), $matches);

        return $matches[1] ?? '';
    }
}
