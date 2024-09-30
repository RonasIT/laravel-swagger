<?php

namespace RonasIT\Support\AutoDoc\Tests\PhpUnitEventSubscribers;

use Illuminate\Contracts\Console\Kernel;
 use Illuminate\Support\Facades\ParallelTesting;
use PHPUnit\Event\Application\Finished;
use PHPUnit\Event\Application\FinishedSubscriber;
use RonasIT\Support\AutoDoc\Services\SwaggerService;

final class ApplicationFinishedSubscriber implements FinishedSubscriber
{
    public function notify(Finished $event): void
    {
        $this->createApplication();

        if ($token = ParallelTesting::token()) {
            unlink(storage_path("worker_in_progress_{$token}.flag"));

            if (glob(storage_path('worker_in_progress_*.flag')) === false) {
                app(SwaggerService::class)->saveProductionData();
            }
        } else {
            app(SwaggerService::class)->saveProductionData();
        }
    }

    protected function createApplication(): void
    {
        $app = require base_path('bootstrap/app.php');

        $app->loadEnvironmentFrom('.env.testing');
        $app->make(Kernel::class)->bootstrap();
    }
}