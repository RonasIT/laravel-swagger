<?php

namespace RonasIT\AutoDoc\Tests\Support\Traits;

use RonasIT\AutoDoc\Drivers\LocalDriver;
use RonasIT\Support\Traits\MockTrait;
use RonasIT\AutoDoc\Services\SwaggerService;
use Illuminate\Container\Container;
use Mockery;
use Mockery\MockInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

use function PHPUnit\Framework\callback;

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

    protected function mockGetTrace(string &$content): void
    {
        $contentInArray = explode(PHP_EOL, $content);

        $traceInfo = Arr::first(
            array: $contentInArray,
            callback: fn ($value, $key) => Str::containsAll($value, ['args', 'class'])
        );

        $traceInfoInArray = $this->gerTraceInfoInArray($traceInfo);

        $mockedContent = Arr::set(
            array: $contentInArray,
            key: array_search($traceInfo, $contentInArray),
            value: implode(', ', $traceInfoInArray)
        );

        $content = implode(PHP_EOL, $mockedContent);
    }

    protected function gerTraceInfoInArray(string $traceInfo): array
    {
        $errorPlaceInArray = explode(', ', $traceInfo);
        $errorPlaceInArray = array_combine(
            keys: Arr::map($errorPlaceInArray, fn ($value, $key) => Str::before($value, '=')),
            values: $errorPlaceInArray
        );

        foreach ($errorPlaceInArray as $key => $value) {
            if ($key === 'line') {
                $errorPlaceInArray[$key] = 'line=999';
            }

            if ($key === 'class' && Str::contains($value, 'MockObject')) {
                $errorPlaceInArray[$key] = 'class=MockClass';
            }
        }

        return $errorPlaceInArray;
    }
}
