<?php

namespace RonasIT\AutoDoc\Support\PHPUnit\EventSubscribers;

use PHPUnit\Event\Application\Finished;
use PHPUnit\Event\Application\FinishedSubscriber;
use RonasIT\AutoDoc\Services\SwaggerService;

final class SwaggerSaveDocumentationSubscriber implements FinishedSubscriber
{
    public function notify(Finished $event): void
    {
        app(SwaggerService::class)->saveProductionData();
    }
}
