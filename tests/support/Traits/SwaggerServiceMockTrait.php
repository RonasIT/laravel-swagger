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
        $this->mockClass($driverClass, [
            $this->functionCall(
                name: 'getProcessTmpData',
                result: (empty($processTmpData))
                    ? $processTmpData
                    : array_merge($processTmpData, ['paths' => [], 'components' => []])
            ),
            $this->functionCall(
                name: 'saveProcessTmpData',
                arguments: [$savedProcessTmpData ?? $processTmpData],
            ),
        ]);
    }

    protected function mockDriverGetPreparedAndSaveTmpData(
        $getTmpData,
        $saveTmpData,
        $driverClass = LocalDriver::class
    ): void {
        $this->mockClass($driverClass, [
            $this->functionCall(
                name: 'getProcessTmpData',
                result: $getTmpData
            ),
            $this->functionCall('saveProcessTmpData', [$saveTmpData]),
        ]);
    }

    protected function mockDriverGetTmpData($tmpData, $driverClass = LocalDriver::class): void
    {
        $this->mockClass($driverClass, [
            $this->functionCall(
                name: 'getProcessTmpData',
                result: $tmpData
            ),
        ]);
    }

    protected function mockDriverGetDocumentation($data, $driverClass = LocalDriver::class): void
    {
        $this->mockClass($driverClass, [
            $this->functionCall(
                name: 'getDocumentation',
                result: $data
            ),
        ]);
    }

    protected function mockDriverSaveData($driverClass = LocalDriver::class): void
    {
        $this->mockClass($driverClass, [
            $this->functionCall('saveData'),
        ]);
    }
}
