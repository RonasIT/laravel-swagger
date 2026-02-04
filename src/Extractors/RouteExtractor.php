<?php

namespace RonasIT\AutoDoc\Extractors;

use Closure;
use Illuminate\Routing\Route;

class RouteExtractor extends Extractor
{
    protected ?string $controllerClass = null;
    protected ?string $methodName = null;

    public function __construct(
        protected Route $route,
    ) {
        if (!$this->usesClosure()) {
            list($this->controllerClass, $this->methodName) = explode('@', $this->route->getActionName());
        }
    }

    public function getControllerClass(): ?string
    {
        return $this->controllerClass;
    }

    public function getMethodName(): ?string
    {
        return $this->methodName;
    }

    public function getClosure(): Closure
    {
        return $this->route->getAction('uses');
    }

    public function usesClosure(): bool
    {
        return $this->route->getActionName() === 'Closure';
    }
}
