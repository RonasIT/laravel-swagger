<?php

namespace RonasIT\AutoDoc\Actions;

use Illuminate\Routing\Route;
use ReflectionException;
use RonasIT\AutoDoc\Extractors\ClosureExtractor;
use RonasIT\AutoDoc\Extractors\MethodExtractor;
use RonasIT\AutoDoc\Extractors\RouteExtractor;

class GetResourceFromResponseAction
{
    public function execute(Route $route): ?string
    {
        $routeExtractor = new RouteExtractor($route);

        if ($routeExtractor->usesClosure()) {
            return (new ClosureExtractor($routeExtractor->getClosure()))->getResource();
        }

        try {
            $methodExtractor = new MethodExtractor($routeExtractor->getControllerClass(), $routeExtractor->getMethodName());
        } catch (ReflectionException) {
            return null;
        }

        return $methodExtractor->getResource();
    }
}
