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
        $response = response()->json([
            'param_1' => 1,
            'param_2' => 2,
            'param_3' => 3
        ]);

        $request = FormRequest::create('/test', 'GET', ['request_param_1' => 1]);
        $request->setRouteResolver(function () use ($request) {
            return (new Route('GET', 'test', []))->bind($request);
        });
        $request->headers->set('Content-type', 'text/plain');

        $this->service->addData($request, $response);
        $this->service->saveProductionData();

        $this->assertEquals(is_file(config('auto-doc.drivers.local.production_path')), true);

        $documentationContent = file_get_contents(config('auto-doc.drivers.local.production_path'));
        $documentationContentData = json_decode($documentationContent, true);

        $this->assertEquals($documentationContentData, $this->getJsonFixture('documentation_result.json'));
    }
}
