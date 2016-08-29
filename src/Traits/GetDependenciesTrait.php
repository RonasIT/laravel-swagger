<?php

/**
 * Created by PhpStorm.
 * User: roman
 * Date: 27.08.16
 * Time: 13:51
 */

namespace RonasIT\Support\AutoDoc\Traits;

use ReflectionMethod;
use ReflectionFunctionAbstract;
use ReflectionParameter;

trait GetDependenciesTrait
{
    protected function resolveClassMethodDependencies(array $parameters, $instance, $method)
    {
        if (! method_exists($instance, $method)) {
            return $parameters;
        }

        return $this->getDependencies(
            new ReflectionMethod($instance, $method)
        );
    }

    public function getDependencies(ReflectionFunctionAbstract $reflector) {
        return array_map(function ($parameter) {
            return $this->transformDependency($parameter);
        }, $reflector->getParameters());
    }

    protected function transformDependency(ReflectionParameter $parameter) {
        $class = $parameter->getClass();

        if (empty($class)) {
            return null;
        }

        return $class->name;
    }
}