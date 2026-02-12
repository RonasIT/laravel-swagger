<?php

namespace RonasIT\AutoDoc\Extractors;

use Closure;
use Illuminate\Routing\Route;

class RouteExtractor
{
    protected ?string $controllerClass;
    protected ?string $methodName;

    public function __construct(
        protected Route $route,
    ) {
        $actionParts = explode('@', $this->route->getActionName());

        $this->controllerClass = $actionParts[0] ?? null;
        $this->methodName = $actionParts[1] ?? null;
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
