<?php

namespace RonasIT\AutoDoc\Support\Resolvers;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ReflectionFunctionAbstract;
use ReflectionNamedType;
use ReflectionUnionType;

class ResourceClassResolver
{
    public function resolve(ReflectionFunctionAbstract $reflection): ?string
    {
        $returnType = $reflection->getReturnType();

        $isAnonymousCollection = false;

        if (!empty($returnType)) {
            $resource = $this->resolveFromReturnType($returnType);

            if (!empty($resource)) {
                return $resource;
            }

            $isAnonymousCollection = $returnType instanceof ReflectionNamedType
                && $returnType->getName() === AnonymousResourceCollection::class;
        }

        $resource = $this->resolveFromMethodReturn($reflection);

        if ($isAnonymousCollection) {
            $resource = $this->handleAnonymousResource($resource);
        }

        return $resource;
    }

    private function resolveFromReturnType(mixed $returnType): ?string
    {
        if ($returnType instanceof ReflectionNamedType && $this->isResourceClass($returnType->getName())) {
            return $returnType->getName();
        }

        if ($returnType instanceof ReflectionUnionType) {
            foreach ($returnType->getTypes() as $type) {
                if ($this->isConcreteResourceType($type)) {
                    return $type->getName();
                }
            }
        }

        return null;
    }

    private function isConcreteResourceType(mixed $type): bool
    {
        return $type instanceof ReflectionNamedType
            && !$type->isBuiltin()
            && $this->isResourceClass($type->getName());
    }

    private function isResourceClass(string $className): bool
    {
        return is_subclass_of($className, JsonResource::class)
            && $className !== AnonymousResourceCollection::class;
    }

    private function resolveFromMethodReturn(ReflectionFunctionAbstract $reflection): ?string
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

    private function handleAnonymousResource(string $resource): string
    {
        return (stripos($resource, 'Collection') === false)
            ? $resource . 'Collection'
            : $resource;
    }
}
