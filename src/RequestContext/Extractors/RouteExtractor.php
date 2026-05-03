<?php

namespace RonasIT\AutoDoc\RequestContext\Extractors;

use Closure;
use Illuminate\Routing\Route;
use RonasIT\AutoDoc\Exceptions\NonClosureControllerException;

class RouteExtractor
{
    private const string CLOSURE_ACTION_NAME = 'Closure';

    public readonly ?string $controllerClass;
    public readonly ?string $controllerMethod;
    public readonly bool $isClosureAction;
    public readonly array $wheres;

    public function __construct(
        protected Route $route,
    ) {
        $actionName = $route->getActionName();

        $actionParts = explode('@', $actionName);

        $this->controllerClass = $actionParts[0] ?? null;
        $this->controllerMethod = $actionParts[1] ?? null;

        $this->isClosureAction = ($actionName === self::CLOSURE_ACTION_NAME);
        $this->wheres = $this->route->wheres;
    }

    public function getClosure(): Closure
    {
        if (!$this->isClosureAction) {
            throw new NonClosureControllerException();
        }

        $uses = $this->route->getAction('uses');

        if (!$uses instanceof Closure) {
            throw new NonClosureControllerException();
        }

        return $uses;
    }
}
