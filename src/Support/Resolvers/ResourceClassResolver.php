<?php

namespace RonasIT\AutoDoc\Support\Resolvers;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ReflectionFunctionAbstract;
use ReflectionNamedType;
use ReflectionUnionType;
use RonasIT\AutoDoc\DTO\ResolvedResource;

class ResourceClassResolver
{
    public function resolve(ReflectionFunctionAbstract $reflection): ?ResolvedResource
    {
        return $this->resolveFromReturnType($reflection->getReturnType())
            ?? $this->resolveFromSource($reflection);
    }

    private function resolveFromReturnType(mixed $returnType): ?ResolvedResource
    {
        if ($returnType instanceof ReflectionNamedType && $this->isResourceClass($returnType->getName())) {
            return new ResolvedResource($returnType->getName());
        }

        if ($returnType instanceof ReflectionUnionType) {
            foreach ($returnType->getTypes() as $type) {
                if ($type instanceof ReflectionNamedType && !$type->isBuiltin() && $this->isResourceClass($type->getName())) {
                    return new ResolvedResource($type->getName());
                }
            }
        }

        return null;
    }

    private function resolveFromSource(ReflectionFunctionAbstract $reflection): ?ResolvedResource
    {
        $fileContent = $this->getFileContent($reflection);
        $code = $this->getFunctionCode($reflection, $fileContent);

        $patterns = [
            'single' => '/(?:return\s+|=>\s+)([^\s(]+)::make/',
            'collection' => '/(?:return\s+|=>\s+)([^\s(]+)::collection/',
            'class' => '/(?:return\s+|=>\s+)new\s+([^\s(]+)/',
        ];

        foreach ($patterns as $type => $pattern) {
            preg_match($pattern, $code, $matches);

            if (empty($matches[1])) {
                continue;
            }

            $resourceName = class_exists($matches[1])
                ? $matches[1]
                : $this->getClassNameFromImports($matches[1], $fileContent);

            if (is_subclass_of($resourceName, JsonResource::class)) {
                return new ResolvedResource($resourceName, $type === 'collection');
            }
        }

        return null;
    }

    private function isResourceClass(string $className): bool
    {
        return is_subclass_of($className, JsonResource::class)
            && $className !== AnonymousResourceCollection::class;
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
