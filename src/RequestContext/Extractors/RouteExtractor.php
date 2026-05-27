<?php

namespace RonasIT\AutoDoc\RequestContext\Extractors;

use Closure;
use Illuminate\Routing\Route;
use Illuminate\Support\Arr;
use RonasIT\AutoDoc\Exceptions\NonClosureControllerException;
use RonasIT\AutoDoc\RequestContext\Resolvers\MethodDependencyResolver;

class RouteExtractor
{
    private const string CLOSURE_ACTION_NAME = 'Closure';

    public readonly ?string $controllerClass;
    public readonly ?string $controllerMethod;
    public readonly bool $isClosureAction;
    public readonly array $wheres;
    protected MethodDependencyResolver $methodDependencyResolver;

    public function __construct(
        protected Route $route,
    ) {
        $this->methodDependencyResolver = app(MethodDependencyResolver::class);

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

    public function getRequestClassName(): ?string
    {
        if (!$this->isUsesRequestClass()) {
            return null;
        }

        $parameters = $this
            ->methodDependencyResolver
            ->resolveClassMethodDependencies(
                instance: app($this->controllerClass),
                method: $this->controllerMethod,
            );

        return Arr::first($parameters, fn ($className) => is_string($className) && preg_match('/Request/', $className));
    }

    protected function isUsesRequestClass(): bool
    {
        return !$this->isClosureAction
            && !empty($this->controllerClass)
            && !empty($this->controllerMethod)
            && method_exists($this->controllerClass, $this->controllerMethod);
    }

    public function getUri(): string
    {
        $basePath = preg_replace("/^\//", '', config('auto-doc.basePath'));

        $uriWithoutBasePath = preg_replace("/^{$basePath}/", '', $this->route->uri());

        $preparedUri = preg_replace("/^\//", '', $uriWithoutBasePath);

        return "/{$preparedUri}";
    }
}
