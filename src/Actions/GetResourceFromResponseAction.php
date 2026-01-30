<?php

namespace RonasIT\AutoDoc\Actions;

use Closure;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Route;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;

class GetResourceFromResponseAction
{
    public function execute(Route $route): ?string
    {
        $actionName = $route->getActionName();

        if ($actionName === 'Closure') {
            return $this->getFromClosure($route->getAction('uses'));
        }

        list($controllerClass, $methodName) = explode('@', $actionName);

        try {
            $method = new ReflectionMethod($controllerClass, $methodName);
        } catch (ReflectionException) {
            return null;
        }

        $returnType = $method->getReturnType()?->getName();

        if (is_null($returnType)) {
            return $this->getFromControllerFile($method);
        }

        return ($this->isResourceClass($returnType)) ? $this->getResourceName($returnType) : null;
    }

    protected function getFromClosure(Closure $closure): ?string
    {
        $closure = new ReflectionFunction($closure);

        $fileContent = file($closure->getFileName());

        $code = $this->getMethodCode($fileContent, $closure);

        return $this->getResourceNameFromReturnString($code);
    }

    protected function getFromControllerFile(ReflectionMethod $method): ?string
    {
        $fileContent = file($method->getFileName());

        $methodCode = $this->getMethodCode($fileContent, $method);

        $resourceName = $this->getResourceNameFromReturnString($methodCode);

        $className = $this->getClassNameFromImports($fileContent, $resourceName);

        return ($this->isResourceClass($className)) ? $this->getResourceName($className) : null;
    }

    protected function getMethodCode(array $fileContent, ReflectionFunctionAbstract $method): string
    {
        $startLineIndex = $method->getStartLine() - 1;

        $methodSlice = array_slice($fileContent, $startLineIndex, $method->getEndLine() - $startLineIndex);

        return implode('', $methodSlice);
    }

    protected function getResourceNameFromReturnString(string $methodCode): string
    {
        preg_match('/(?:return\s+|=>\s*)([^\s(]+)::make/', $methodCode, $matches);

        return $matches[1] ?? '';
    }

    protected function getClassNameFromImports(array $fileContent, string $resourceName): string
    {
        $resourceImport = Arr::first(
            array: $fileContent,
            callback: fn (string $line) => (Str::startsWith($line, 'use') && Str::contains($line, $resourceName)),
        );

        return Str::replace(['use', "as {$resourceName}", ' ', "\n", ';'], '', $resourceImport ?? '');
    }

    protected function isResourceClass(string $className): bool
    {
        return is_subclass_of($className, JsonResource::class);
    }

    protected function getResourceName(string $className): string
    {
        return Str::afterLast($className, '\\');
    }
}
