<?php

namespace RonasIT\AutoDoc\Tests;

use Illuminate\Support\Facades\Route;
use RonasIT\AutoDoc\Exceptions\NonClosureControllerException;
use RonasIT\AutoDoc\Inspectors\RouteInspector;
use RonasIT\AutoDoc\Tests\Support\Mock\TestController;

class UnitTest extends TestCase
{
    public function testRouteInspectorGetClosureException()
    {
        $this->assertExceptionThrew(NonClosureControllerException::class, '');

        $route = Route::get('/some/url')->setAction(['controller' => TestController::class . '@test']);

        $inspector = new RouteInspector($route);

        $inspector->getClosure();
    }
}
