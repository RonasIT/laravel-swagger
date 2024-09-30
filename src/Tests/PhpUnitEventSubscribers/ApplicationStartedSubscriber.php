<?php

namespace RonasIT\Support\AutoDoc\Tests\PhpUnitEventSubscribers;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\ParallelTesting;
use PHPUnit\Event\Application\Started;
use PHPUnit\Event\Application\StartedSubscriber;

final class ApplicationStartedSubscriber implements StartedSubscriber
{
    public function notify(Started $event): void
    {
        $this->createApplication();

        if ($token = ParallelTesting::token()) {
            touch(storage_path("worker_in_progress_{$token}.flag"));
        }
    }

    protected function createApplication(): void
    {
        $app = require base_path('bootstrap/app.php');

        $app->loadEnvironmentFrom('.env.testing');
        $app->make(Kernel::class)->bootstrap();
    }
}