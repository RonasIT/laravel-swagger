<?php

namespace RonasIT\AutoDoc\Extractors;

use Closure;
use Illuminate\Routing\Route;
use RonasIT\AutoDoc\Exceptions\NonClosureControllerException;

class RouteExtractor
{
    private const string CLOSURE_ACTION_NAME = 'Closure';

    public readonly ?string $controllerClass;
    public readonly ?string $methodName;
    public readonly bool $usesClosure;

    public function __construct(
        protected Route $route,
    ) {
        $actionName = $route->getActionName();

        $actionParts = explode('@', $actionName);

        $this->controllerClass = $actionParts[0] ?? null;
        $this->methodName = $actionParts[1] ?? null;

        $this->usesClosure = $actionName === self::CLOSURE_ACTION_NAME;
    }

    public function getClosure(): Closure
    {
        if (!$this->usesClosure) {
            throw new NonClosureControllerException();
        }

        return $this->route->getAction('uses');
    }
}
