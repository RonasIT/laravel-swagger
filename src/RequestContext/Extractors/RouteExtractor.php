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
        $this->wheres = $this->route->wheres;

        $actionName = $route->getActionName();
        $this->isClosureAction = ($actionName === self::CLOSURE_ACTION_NAME);

        $actionParts = explode('@', $actionName);

        $this->controllerClass = $actionParts[0] ?? null;

        $this->controllerMethod = (is_null($this->controllerClass)) ? null : $actionParts[1] ?? '__invoke';
    }

    public function getClosure(): Closure
    {
        $uses = $this->route->getAction('uses');

        if (!$uses instanceof Closure) {
            throw new NonClosureControllerException();
        }

        return $uses;
    }

    public function getUri(): string
    {
        $basePath = preg_replace("/^\//", '', config('auto-doc.basePath'));

        $uriWithoutBasePath = preg_replace("/^{$basePath}/", '', $this->route->uri());

        $preparedUri = preg_replace("/^\//", '', $uriWithoutBasePath);

        return "/{$preparedUri}";
    }
}
