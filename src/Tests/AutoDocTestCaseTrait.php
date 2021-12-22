<?php

namespace RonasIT\Support\AutoDoc\Tests;

use RonasIT\Support\AutoDoc\Services\SwaggerService;
use RonasIT\Support\AutoDoc\Http\Middleware\AutoDocMiddleware;

trait AutoDocTestCaseTrait
{
    public $docService;

    public function createApplication()
    {
        parent::createApplication();
    }

    public function tearDown(): void
    {
        $this->saveDocumentation();

        parent::tearDown();
    }

    public function saveDocumentation()
    {
        $currentTestCount = $this->getTestResultObject()->count();
        $allTestCount = $this->getTestResultObject()->topTestSuite()->count();

        if (($currentTestCount == $allTestCount) && (!$this->hasFailed())) {
            $docService = $this->docService ?? app(SwaggerService::class);
            $docService->saveProductionData();
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
