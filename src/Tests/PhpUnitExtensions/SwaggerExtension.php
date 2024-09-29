<?php

namespace RonasIT\Support\AutoDoc\Tests\PhpUnitExtensions;

use PHPUnit\Runner\Extension\Extension as PhpunitExtension;
use PHPUnit\Runner\Extension\Facade as EventFacade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use RonasIT\Support\AutoDoc\Tests\PhpUnitEventSubscribers\ApplicationFinishedSubscriber;
use RonasIT\Support\AutoDoc\Tests\PhpUnitEventSubscribers\ApplicationStartedSubscriber;

final class SwaggerExtension implements PhpunitExtension
{
    public function bootstrap(Configuration $configuration, EventFacade $facade, ParameterCollection $parameters): void
    {
        $facade->registerSubscriber(new ApplicationStartedSubscriber());
        $facade->registerSubscriber(new ApplicationFinishedSubscriber());
    }
}
