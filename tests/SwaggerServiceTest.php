<?php

namespace RonasIT\Tests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;
use Illuminate\Routing\Route;
use RonasIT\Support\AutoDoc\Services\SwaggerService;
use RonasIT\Tests\Traits\FixturesTrait;

class SwaggerServiceTest extends TestCase
{
    use InteractsWithViews, FixturesTrait;

    protected $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(SwaggerService::class);
    }

    public function testSaveProductionData()
    {
        $response = response()->json(['kuku' => 1]);

        $request = FormRequest::create('/test', 'GET', ['kuku' => 1]);
        $request->setRouteResolver(function () use ($request) {
            return (new Route('GET', 'test', []))->bind($request);
        });
        $request->headers->set('Content-type', 'text/plain');

        app(SwaggerService::class)->addData($request, $response);
        app(SwaggerService::class)->saveProductionData();

        $this->assertEquals(is_file(config('auto-doc.drivers.local.production_path')), true);
    }
}
