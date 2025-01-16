<?php

namespace RonasIT\AutoDoc\Traits;

use ReflectionMethod;
use ReflectionFunction;
use ReflectionParameter;
use Illuminate\Support\Arr;
use ReflectionFunctionAbstract;
use Illuminate\Container\Container;

trait GetDependenciesTrait
{
    public function resolveClassMethodDependencies(object $instance, string $method): array
    {
        return array_map(function ($parameter) {
            return $this->transformDependency($parameter);
        }, (new ReflectionMethod($instance, $method))->getParameters());
    }

    protected function transformDependency(ReflectionParameter $parameter)
    {
        $type = $parameter->getType();

        if (empty($type)) {
            return null;
        }

        return interface_exists($type->getName()) ? $this->getClassByInterface($type->getName()) : $type->getName();
    }

    protected function getClassByInterface($interfaceName)
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
