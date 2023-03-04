<?php

namespace RonasIT\Support\AutoDoc\Tests\PhpUnitExtensions;

use PHPUnit\Event\TestSuite\Finished;
use PHPUnit\Runner\Extension\Extension as PhpunitExtension;
use PHPUnit\Runner\Extension\Facade as EventFacade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use RonasIT\Support\AutoDoc\Tests\PhpUnitEventSubscribers\SwaggerSubscriber;

final class SwaggerNewExtension implements PhpunitExtension
{
    public function bootstrap(Configuration $configuration, EventFacade $facade, ParameterCollection $parameters): void
    {
        $facade->registerSubscriber(new SwaggerSubscriber());
    }
}
