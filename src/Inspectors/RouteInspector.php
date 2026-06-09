<?php

namespace RonasIT\AutoDoc\Inspectors;

use Closure;
use Illuminate\Routing\Route;
use RonasIT\AutoDoc\Exceptions\NonClosureControllerException;

class RouteInspector
{
    private const string CLOSURE_ACTION_NAME = 'Closure';

    public function __construct(
        private readonly Route $route,
    ) {
    }

    public function getUri(): string
    {
        $basePath = ltrim(config('auto-doc.basePath'), '/');

        $routeUri = $this->route->uri();

        $isContainsBasePath = !empty($basePath) && str_starts_with($routeUri, $basePath . '/');

        $uri = $isContainsBasePath ? substr($routeUri, strlen($basePath)) : $routeUri;

        return '/' . ltrim($uri, '/');
    }

    public function isClosureAction(): bool
    {
        return $this->route->getActionName() === self::CLOSURE_ACTION_NAME;
    }

    public function getClosure(): Closure
    {
        $uses = $this->route->getAction('uses');

        if (!$uses instanceof Closure) {
            throw new NonClosureControllerException();
        }

        return $uses;
    }

    public function getControllerClass(): ?string
    {
        if ($this->isClosureAction()) {
            return null;
        }

        $actionParts = $this->getActionParts();

        return $actionParts[0] ?? null;
    }

    public function getControllerMethod(): ?string
    {
        if ($this->isClosureAction()) {
            return null;
        }

        $actionParts = $this->getActionParts();

        return $actionParts[1] ?? '__invoke';
    }

    public function getWheres(): array
    {
        return $this->route->wheres;
    }

    private function getActionParts(): array
    {
        return explode('@', $this->route->getActionName());
    }
}
