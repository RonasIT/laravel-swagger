<?php

namespace RonasIT\Support\AutoDoc\Tests\PhpUnitEventSubscribers;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use PHPUnit\Event;
use PHPUnit\Event\Test\AfterLastTestMethodFinished;
use RonasIT\Support\AutoDoc\Services\SwaggerService;

final class SwaggerSubscriber implements Event\Test\AfterLastTestMethodFinishedSubscriber
{
    public function notify(AfterLastTestMethodFinished $event): void
    {
        $this->createApplication();

        app(SwaggerService::class)->saveProductionData();
    }

    protected function createApplication(): Application
    {
        $app = require_once base_path('bootstrap/app.php');

        $app->loadEnvironmentFrom('.env.testing');
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
