<?php

namespace RonasIT\AutoDoc\Support\PHPUnit\EventSubscribers;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use PHPUnit\Event\Application\Finished;
use PHPUnit\Event\Application\FinishedSubscriber;
use RonasIT\AutoDoc\Services\SwaggerService;

final class SwaggerSaveDocumentationSubscriber implements FinishedSubscriber
{
    public function notify(Finished $event): void
    {
        $this->createApplication();

        app(SwaggerService::class)->saveProductionData();
    }

    protected function createApplication(): void
    {
        $app = require Application::inferBasePath() . '/bootstrap/app.php';

        $app->loadEnvironmentFrom('.env.testing');
        $app->make(Kernel::class)->bootstrap();
    }
}
