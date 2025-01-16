<?php

namespace RonasIT\AutoDoc\Support\PHPUnit\EventSubscribers;

use PHPUnit\Event\TestRunner\ExecutionFinished;
use PHPUnit\Event\TestRunner\ExecutionFinishedSubscriber;

final class SwaggerSaveDocumentationSubscriber implements ExecutionFinishedSubscriber
{
    public function notify(ExecutionFinished $event): void
    {
        shell_exec('php artisan swagger:push-documentation');
    }
}
