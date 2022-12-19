<?php

namespace RonasIT\Support\Tests\Support\Traits;

use RonasIT\Support\AutoDoc\Drivers\LocalDriver;

trait SwaggerServiceMockTrait
{
    use MockTrait;

    protected function mockDriverSaveTmpData($expectedData)
    {
        $driver = $this->mockCLass(LocalDriver::class, ['saveTmpData']);

        $firstCall = array_merge($expectedData, ['paths' => []]);

        $driver->expects($this->exactly(2))->method('saveTmpData')->withConsecutive([$firstCall], [$expectedData]);

        $this->app->instance(LocalDriver::class, $driver);
    }
}
