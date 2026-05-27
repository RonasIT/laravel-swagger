<?php

namespace RonasIT\AutoDoc\RequestContext\Resolvers;

use Illuminate\Container\Container;
use Illuminate\Support\Arr;
use ReflectionMethod;
use ReflectionParameter;

class MethodDependencyResolver
{
    public function resolveClassMethodDependencies(object $instance, string $method): array
    {
        $parameters = (new ReflectionMethod($instance, $method))->getParameters();

        return array_map(fn ($parameter) => $this->transformDependency($parameter), $parameters);
    }

    protected function transformDependency(ReflectionParameter $parameter): ?string
    {
        $type = $parameter->getType();

        if (empty($type)) {
            return null;
        }

        return interface_exists($type->getName())
            ? $this->getClassByInterface($type->getName())
            : $type->getName();
    }

    protected function getClassByInterface(string $interfaceName): ?string
    {
        $app = Container::getInstance();

        $bindings = $app->getBindings();

        $implementation = Arr::get($bindings, "{$interfaceName}.concrete");

        return (empty($implementation))
            ? null
            : get_class(call_user_func($implementation, $app));
    }
}
