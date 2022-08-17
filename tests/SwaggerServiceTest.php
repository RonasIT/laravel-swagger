<?php

namespace RonasIT\Tests;

use RonasIT\Support\AutoDoc\Services\SwaggerService;

class SwaggerServiceTest extends TestCase
{
    public function createApplication()
    {
        return require __DIR__ . '/../../../bootstrap/app.php';
    }

    public function testGetAppUrl()
    {
        $result = app(SwaggerService::class)->getApp();

        $this->assertEquals("fdfgffgssfdsf", $result);
    }
}
