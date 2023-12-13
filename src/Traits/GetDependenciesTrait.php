<?php

namespace RonasIT\Support\AutoDoc\Traits;

use ReflectionMethod;
use ReflectionFunction;
use ReflectionParameter;
use Illuminate\Support\Arr;
use ReflectionFunctionAbstract;
use Illuminate\Container\Container;

trait GetDependenciesTrait
{
    protected function resolveClassMethodDependencies(array $parameters, object $instance, string $method): array
    {
        if (!method_exists($instance, $method)) {
            return $parameters;
        }

        return $this->getDependencies(
            new ReflectionMethod($instance, $method)
        );
    }

    public function getDependencies(ReflectionFunctionAbstract $reflector): array
    {
        return array_map(function ($parameter) {
            return $this->transformDependency($parameter);
        }, $reflector->getParameters());
    }

    protected function transformDependency(ReflectionParameter $parameter): ?string
    {
        $class = $parameter->getClass();

        if (empty($class)) {
            return null;
        }

        return interface_exists($class->name) ? $this->getClassByInterface($class->name) : $class->name;
    }

    protected function getClassByInterface(string $interfaceName): ?string
    {
        $bindings = Container::getInstance()->getBindings();

        $implementation = Arr::get($bindings, "{$interfaceName}.concrete");

        if (empty($implementation)) {
            return null;
        }

        $classFields = (new ReflectionFunction($implementation))->getStaticVariables();

        return $classFields['concrete'];
    }
}
