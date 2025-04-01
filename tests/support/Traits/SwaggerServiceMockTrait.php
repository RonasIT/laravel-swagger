<?php

namespace RonasIT\AutoDoc\Tests\Support\Traits;

use RonasIT\AutoDoc\Drivers\LocalDriver;

trait SwaggerServiceMockTrait
{
    use MockTrait;

    protected function mockDriverGetEmptyAndSaveProcessTmpData(
        $processTmpData,
        $savedProcessTmpData = null,
        $driverClass = LocalDriver::class,
    ): void {
        $driver = $this->mockClass($driverClass, ['getProcessTmpData', 'saveProcessTmpData']);

        $driver
            ->expects($this->exactly(1))
            ->method('getProcessTmpData')
            ->willReturn(
                empty($processTmpData)
                ? $processTmpData
                : array_merge($processTmpData, ['paths' => [], 'components' => []])
            );

        $driver
            ->expects($this->exactly(1))
            ->method('saveProcessTmpData')
            ->with($savedProcessTmpData ?? $processTmpData);

        $this->app->instance($driverClass, $driver);
    }

    protected function mockDriverGetPreparedAndSaveTmpData(
        $getTmpData,
        $saveTmpData,
        $driverClass = LocalDriver::class
    ): void {
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

    protected function mockDriverGetTmpData($tmpData, $driverClass = LocalDriver::class): void
    {
        $driver = $this->mockClass($driverClass, ['getProcessTmpData']);

        $driver
            ->expects($this->exactly(1))
            ->method('getProcessTmpData')
            ->willReturn($tmpData);

        $this->app->instance($driverClass, $driver);
    }

    protected function mockDriverGetProcessTmpDataAndGetSharedTmpData(array $processTmpData, array $sharedTmpData, string $driverClass = LocalDriver::class): void
    {
        $driver = $this->mockClass($driverClass, ['getProcessTmpData']);

        $driver
            ->expects($this->exactly(1))
            ->method('getProcessTmpData')
            ->willReturn($processTmpData);

        $this->app->instance($driverClass, $driver);
    }

    protected function mockDriverGetDocumentation($data, $driverClass = LocalDriver::class): void
    {
        $driver = $this->mockClass($driverClass, ['getDocumentation']);

        $driver
            ->expects($this->exactly(1))
            ->method('getDocumentation')
            ->willReturn($data);

        $this->app->instance($driverClass, $driver);
    }

    protected function mockDriverSaveData($driverClass = LocalDriver::class): void
    {
        $driver = $this->mockClass($driverClass, ['saveData']);

        $driver
            ->expects($this->exactly(1))
            ->method('saveData');

        $this->app->instance($driverClass, $driver);
    }
}
