<?php

namespace RonasIT\Support\Tests\Support\Traits;

use RonasIT\Support\AutoDoc\Drivers\LocalDriver;

trait SwaggerServiceMockTrait
{
    use MockTrait;

    protected function mockDriverSaveTmpData($expectedData, $driverClass = LocalDriver::class)
    {
        $driver = $this->mockClass($driverClass, ['saveTmpData']);

        $firstCall = array_merge($expectedData, ['paths' => [], 'definitions' => []]);

        $driver
            ->expects($this->exactly(2))
            ->method('saveTmpData')
            ->withConsecutive(
                [$firstCall],
                [$expectedData]
            );

        $this->app->instance($driverClass, $driver);
    }
}
