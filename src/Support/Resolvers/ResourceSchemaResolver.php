<?php

namespace RonasIT\AutoDoc\Support\Resolvers;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Str;
use ReflectionFunctionAbstract;
use ReflectionNamedType;
use ReflectionUnionType;
use RonasIT\AutoDoc\DTO\ResourceSchema;

class ResourceSchemaResolver
{
    public function resolve(ReflectionFunctionAbstract $reflection): ?ResourceSchema
    {
        $returnType = $reflection->getReturnType();

        $result = (!empty($returnType))
            ? $this->resolveFromReturnType($returnType)
            : null;

        return $result ?? $this->resolveFromSource($reflection);
    }

    protected function resolveFromReturnType(object $returnType): ?ResourceSchema
    {
        $types = match (get_class($returnType)) {
            ReflectionNamedType::class => [$returnType],
            ReflectionUnionType::class => $returnType->getTypes(),
            default => [],
        };

        foreach ($types as $type) {
            $isResourceClass = $type instanceof ReflectionNamedType
                && !$type->isBuiltin()
                && $this->isResourceClass($type->getName());

            if ($isResourceClass) {
                $className = $type->getName();

                return new ResourceSchema(
                    className: $className,
                    isCollection: is_subclass_of($className, ResourceCollection::class),
                );
            }
        }

        return null;
    }

    protected function resolveFromSource(ReflectionFunctionAbstract $reflection): ?ResourceSchema
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
                return new ResourceSchema(
                    className: $resourceName,
                    isCollection: $type === 'collection',
                );
            }
        }

        return null;
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
        foreach ($fileContent as $line) {
            $line = trim($line);

            if (!Str::startsWith($line, 'use ')) {
                continue;
            }

            preg_match('/^use\s+(?<namespace>[^;]+?)(?:\s+as\s+(?<alias>\w+))?;$/', $line, $matches);

            $nameSpace = $matches['namespace'] ?? '';
            $alias = $matches['alias'] ?? '';

            $isFoundNamespace = ($alias === $resourceName)
                || (empty($alias) && Str::afterLast($nameSpace, '\\') === $resourceName);

            if ($isFoundNamespace) {
                return $nameSpace;
            }
        }

        return '';
    }
}
