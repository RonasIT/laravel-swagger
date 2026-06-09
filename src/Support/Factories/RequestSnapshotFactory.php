<?php

namespace RonasIT\AutoDoc\Support\Factories;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RonasIT\AutoDoc\Contracts\ControllerInspectorContract;
use RonasIT\AutoDoc\DTO\HttpActionMeta;
use RonasIT\AutoDoc\DTO\RequestSnapshot;
use RonasIT\AutoDoc\DTO\RouteSnapshot;
use RonasIT\AutoDoc\Inspectors\ClassControllerInspector;
use RonasIT\AutoDoc\Inspectors\ClosureControllerInspector;
use RonasIT\AutoDoc\Inspectors\RouteInspector;
use RonasIT\AutoDoc\Support\Resolvers\MethodDependencyResolver;
use RonasIT\AutoDoc\Support\Resolvers\ResourceClassResolver;

class RequestSnapshotFactory
{
    public function __construct(
        private MethodDependencyResolver $dependencyResolver,
        private ResourceClassResolver $resourceClassResolver,
        private RequestDataFactory $requestDataFactory,
    ) {
    }

    public function make(Request $request): RequestSnapshot
    {
        $routeInspector = new RouteInspector($request->route());

        $controllerInspector = $this->getControllerInspector($routeInspector);

        $requestClass = $controllerInspector->getRequestClass();
        $resolvedResource = $controllerInspector->getResourceClass();

        $resourceClass = match (true) {
            $resolvedResource === null => null,
            $resolvedResource->isCollection => Str::replaceLast('Resource', 'Collection', class_basename($resolvedResource->class)),
            default => $resolvedResource->class,
        };

        return new RequestSnapshot(
            route: new RouteSnapshot(
                uri: $routeInspector->getUri(),
                httpMethod: strtolower($request->method()),
                routeWheres: $routeInspector->getWheres(),
            ),
            action: new HttpActionMeta(
                requestClass: $requestClass,
                resourceClass: $resourceClass,
            ),
            requestData: $this->requestDataFactory->make($request, $requestClass),
            hasSecurityToken: $this->resolveSecurityToken($request),
        );
    }

    private function getControllerInspector(RouteInspector $routeInspector): ControllerInspectorContract
    {
        return $routeInspector->isClosureAction()
            ? new ClosureControllerInspector(
                $routeInspector->getClosure(),
                $this->resourceClassResolver,
            )
            : new ClassControllerInspector(
                $routeInspector->getControllerClass(),
                $routeInspector->getControllerMethod(),
                $this->dependencyResolver,
                $this->resourceClassResolver,
            );
    }

    private function resolveSecurityToken(Request $request): bool
    {
        $security = config('auto-doc.security', '');
        $driver = config("auto-doc.security_drivers.{$security}", []);

        if (empty($driver['in'])) {
            return false;
        }

        $driverName = match ($driver['in']) {
            'header' => $request->header($driver['name']),
            'cookie' => $request->cookie($driver['name']),
            'query' => $request->query($driver['name']),
            default => null,
        };

        return !empty($driverName);
    }
}
