<?php

namespace RonasIT\AutoDoc\Inspectors;

use Closure;
use Illuminate\Http\Request;
use ReflectionFunction;
use ReflectionNamedType;
use RonasIT\AutoDoc\Contracts\ControllerInspectorContract;
use RonasIT\AutoDoc\Support\Resolvers\ResourceClassResolver;

class ClosureControllerInspector implements ControllerInspectorContract
{
    public function __construct(
        private Closure $closure,
        private ResourceClassResolver $resourceClassResolver,
    ) {
    }

    public function getResourceClass(): ?string
    {
        return $this->resourceClassResolver->resolve(new ReflectionFunction($this->closure));
    }

    public function getRequestClass(): ?string
    {
        $parameters = (new ReflectionFunction($this->closure))->getParameters();

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if ($this->isRequest($type)) {
                return $type->getName();
            }
        }

        return null;
    }

    protected function isRequest(mixed $type): bool
    {
        return $type instanceof ReflectionNamedType
            && !$type->isBuiltin()
            && is_subclass_of($type->getName(), Request::class);
    }
}
