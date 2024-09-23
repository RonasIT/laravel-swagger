<?php

namespace RonasIT\AutoDoc\Tests;

use RonasIT\AutoDoc\Http\Middleware\AutoDocMiddleware;

trait AutoDocTestCaseTrait
{
    /**
     * Disabling documentation collecting on current test
     */
    public function skipDocumentationCollecting()
    {
        AutoDocMiddleware::$skipped = true;
    }
}
