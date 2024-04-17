<?php

namespace RonasIT\Support\AutoDoc\Tests\PhpUnitEventSubscribers;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use PHPUnit\Event\Test\AfterLastTestMethodFinished;
use PHPUnit\Event\Test\AfterLastTestMethodFinishedSubscriber;
use RonasIT\Support\AutoDoc\Services\SwaggerService;

final class SwaggerSaveDocumentationSubscriber implements AfterLastTestMethodFinishedSubscriber
{
    public function notify(AfterLastTestMethodFinished $event): void
    {
        $this->createApplication();

        app(SwaggerService::class)->saveProductionData();
    }

    protected function createApplication(): Application
    {
        $app = require base_path('bootstrap/app.php');

        $app->loadEnvironmentFrom('.env.testing');
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
