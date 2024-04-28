<?php

namespace RonasIT\Support\AutoDoc\Tests\PhpUnitEventSubscribers;

use Illuminate\Contracts\Console\Kernel;
use PHPUnit\Event\Application\Finished;
use PHPUnit\Event\Application\FinishedSubscriber;
use RonasIT\Support\AutoDoc\Services\SwaggerService;

final class SwaggerSaveDocumentationSubscriber implements FinishedSubscriber
{
    public function notify(Finished $event): void
    {
        $this->createApplication();

        app(SwaggerService::class)->saveProductionData();
    }

    protected function createApplication(): void
    {
        $app = require base_path('bootstrap/app.php');

        $app->loadEnvironmentFrom('.env.testing');
        $app->make(Kernel::class)->bootstrap();
    }
}