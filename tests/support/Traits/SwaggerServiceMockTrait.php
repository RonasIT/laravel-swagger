<?php

namespace RonasIT\Support\Tests\Support\Traits;

use RonasIT\Support\AutoDoc\Drivers\LocalDriver;

trait SwaggerServiceMockTrait
{
    use MockTrait;

    protected function mockDriverGetEmptyAndSaveTpmData($tmpData, $driverClass = LocalDriver::class)
    {
        $driver = $this->mockClass($driverClass, ['getTmpData', 'saveTmpData']);

        $driver
            ->expects($this->exactly(1))
            ->method('getTmpData')
            ->willReturn(array_merge($tmpData, ['paths' => [], 'definitions' => []]));

        $driver
            ->expects($this->exactly(1))
            ->method('saveTmpData')
            ->with($tmpData);

        $this->app->instance($driverClass, $driver);
    }

    protected function mockDriverGetPreparedAndSaveTpmData($getTmpData, $saveTmpData, $driverClass = LocalDriver::class)
    {
        $driver = $this->mockClass($driverClass, ['getTmpData', 'saveTmpData']);

        $driver
            ->expects($this->exactly(1))
            ->method('getTmpData')
            ->willReturn($getTmpData);

        $driver
            ->expects($this->exactly(1))
            ->method('saveTmpData')
            ->with($saveTmpData);

        $this->app->instance($driverClass, $driver);
    }

    protected function mockDriverGetTpmData($tmpData, $driverClass = LocalDriver::class)
    {
        $driver = $this->mockClass($driverClass, ['getTmpData']);

        $driver
            ->expects($this->exactly(1))
            ->method('getTmpData')
            ->willReturn($tmpData);

        $this->app->instance($driverClass, $driver);
    }
}
