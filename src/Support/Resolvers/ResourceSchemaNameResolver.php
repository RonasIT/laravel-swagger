<?php

namespace RonasIT\AutoDoc\Support\Resolvers;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ReflectionFunctionAbstract;
use ReflectionNamedType;
use ReflectionUnionType;

class ResourceSchemaNameResolver
{
    public function resolve(ReflectionFunctionAbstract $reflection): ?string
    {
        $returnType = $reflection->getReturnType();

        $result = (!empty($returnType))
            ? $this->resolveFromReturnType($returnType)
            : null;

        return $result ?? $this->resolveFromSource($reflection);
    }

    protected function resolveFromReturnType(object $returnType): ?string
    {
        $types = match (get_class($returnType)) {
            ReflectionNamedType::class => [$returnType],
            ReflectionUnionType::class => $returnType->getTypes(),
            default => [],
        };

        foreach ($types as $type) {
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin() && $this->isResourceClass($type->getName())) {
                return $this->toSchemaName($type->getName());
            }
        }

        return null;
    }

    protected function resolveFromSource(ReflectionFunctionAbstract $reflection): ?string
    {
        $fileContent = $this->getFileContent($reflection);
        $code = $this->getFunctionCode($reflection, $fileContent);

        $patterns = [
            'make' => '/(?:return\s+|=>\s+)([^\s(]+)::make/',
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
                $isCollection = ($type === 'collection');

                return $this->toSchemaName($resourceName, $isCollection);
            }
        }

        return null;
    }

    protected function toSchemaName(string $className, bool $isCollection = false): string
    {
        $baseName = Str::replaceLast('Resource', '', class_basename($className));

        return ($isCollection && !Str::endsWith($baseName, 'Collection'))
            ? $baseName . 'Collection'
            : $baseName;
    }

    protected function isResourceClass(string $className): bool
    {
        return is_subclass_of($className, JsonResource::class)
            && $className !== AnonymousResourceCollection::class;
    }

    protected function getFileContent(ReflectionFunctionAbstract $reflection): array
    {
        $fileName = $reflection->getFileName();

        return (empty($fileName) || !is_readable($fileName)) ? [] : file($fileName) ?? [];
    }

    protected function getFunctionCode(ReflectionFunctionAbstract $reflection, array $fileContent): string
    {
        $startLineIndex = $reflection->getStartLine() - 1;
        $methodSlice = array_slice($fileContent, $startLineIndex, $reflection->getEndLine() - $startLineIndex);

        return implode('', $methodSlice);
    }

    protected function getClassNameFromImports(string $resourceName, array $fileContent): string
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
