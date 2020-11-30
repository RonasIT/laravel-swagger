<?php

namespace RonasIT\Support\AutoDoc\Tests;

use RonasIT\Support\AutoDoc\Services\SwaggerService;
use RonasIT\Support\AutoDoc\Http\Middleware\AutoDocMiddleware;

trait AutoDocTestCaseTrait
{
    protected $docService;

    public function init()
    {
        $this->docService = app(SwaggerService::class);
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->init();
    }

    public function createApplication()
    {
        parent::createApplication();
    }

    public function tearDown(): void
    {
        $this->startDocumentationCollecting();

        parent::tearDown();
    }

    public function startDocumentationCollecting()
    {
        $currentTestCount = $this->getTestResultObject()->count();
        $allTestCount = $this->getTestResultObject()->topTestSuite()->count();

        if (($currentTestCount == $allTestCount) && (!$this->hasFailed())) {
            $this->docService->saveProductionData();
        }
    }

    /**
     * Disabling documentation collecting on current test
     */
    public function skipDocumentationCollecting()
    {
        AutoDocMiddleware::$skipped = true;
    }
}
