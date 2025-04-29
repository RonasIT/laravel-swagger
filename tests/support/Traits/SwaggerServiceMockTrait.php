<?php

namespace RonasIT\AutoDoc\Tests\Support\Traits;

use RonasIT\AutoDoc\Drivers\LocalDriver;
use RonasIT\Support\Traits\MockTrait;

trait SwaggerServiceMockTrait
{
    use MockTrait;

    protected function mockDriverGetEmptyAndSaveProcessTmpData(
        $processTmpData,
        $savedProcessTmpData = null,
        $driverClass = LocalDriver::class,
    ): void {
        $driver = $this->mockClass(
            class: $driverClass,
            callChain: [
                $this->functionCall(
                    name: 'getProcessTmpData',
                    result: empty($processTmpData)
                        ? $processTmpData
                        : array_merge($processTmpData, ['paths' => [], 'components' => []])
                ),
                $this->functionCall(name: 'saveProcessTmpData', arguments: [$savedProcessTmpData ?? $processTmpData]),
            ]);

        $this->app->instance($driverClass, $driver);
    }

    protected function mockDriverGetPreparedAndSaveTmpData(
        $getTmpData,
        $saveTmpData,
        $driverClass = LocalDriver::class
    ): void {
        $driver = $this->mockClass(
            class: $driverClass,
            callChain: [
                $this->functionCall(name: 'getProcessTmpData', result: $getTmpData),
                $this->functionCall(name: 'saveProcessTmpData', arguments: [$saveTmpData]),
            ]);

        $this->app->instance($driverClass, $driver);
    }

    protected function mockDriverGetTmpData($tmpData, $driverClass = LocalDriver::class): void
    {
        $driver = $this->mockClass(
            class: $driverClass,
            callChain: [
                $this->functionCall(name: 'getProcessTmpData', result: $tmpData),
            ]);

        $this->app->instance($driverClass, $driver);
    }

    protected function mockDriverGetDocumentation($data, $driverClass = LocalDriver::class): void
    {
        $driver = $this->mockClass(
            class: $driverClass,
            callChain: [
                $this->functionCall(name: 'getDocumentation', result: $data),
            ]);

        $this->app->instance($driverClass, $driver);
    }

    protected function mockDriverSaveData($driverClass = LocalDriver::class): void
    {
        $driver = $this->mockClass(
            class: $driverClass,
            callChain: [
                $this->functionCall(name: 'saveData'),
            ]);

        $this->app->instance($driverClass, $driver);
    }
}
