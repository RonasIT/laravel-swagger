<?php

namespace RonasIT\AutoDoc\Tests;

use Illuminate\Support\Facades\Route;
use RonasIT\AutoDoc\Exceptions\NonClosureControllerException;
use RonasIT\AutoDoc\Extractors\RouteExtractor;
use RonasIT\AutoDoc\Tests\Support\Mock\TestController;

class UnitTest extends TestCase
{
    public function testRouteExtractorGetClosureException()
    {
        $this->assertExceptionThrew(NonClosureControllerException::class, '');

        $route = Route::get('/some/url')->setAction(['controller' => TestController::class . '@test']);

        $extractor = new RouteExtractor($route);

        $extractor->getClosure();
    }
}
