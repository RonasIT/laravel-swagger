<?php

namespace RonasIT\AutoDoc\Tests\Support\Traits;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait TraceMockTrait
{
    protected function mockGetTrace(string &$content): void
    {
        $contentInArray = explode(PHP_EOL, $content);

        $traceInfo = Arr::first(
            array: $contentInArray,
            callback: fn ($value, $key) => Str::contains($value, 'function=')
        );

        $traceInfoInArray = $this->getTraceInfoInArray($traceInfo);

        $mockedContent = Arr::set(
            array: $contentInArray,
            key: array_search($traceInfo, $contentInArray),
            value: implode(', ', $traceInfoInArray)
        );

        $content = implode(PHP_EOL, $mockedContent);
    }

    protected function getTraceInfoInArray(string $traceInfo): array
    {
        $errorPlaceInArray = explode(', ', $traceInfo);
        $errorPlaceInArray = array_combine(
            keys: Arr::map($errorPlaceInArray, fn ($value, $key) => Str::before($value, '=')),
            values: $errorPlaceInArray
        );

        foreach ($errorPlaceInArray as $key => $value) {
            $errorPlaceInArray[$key] = match ($key) {
                'line' => 'line=999',
                'class' => Str::contains($value, 'MockObject') ? 'class=MockClass' : $value,
                'file' => 'file=/src' . Str::after($value, '/src'),
                default => $value,
            };
        }

        return $errorPlaceInArray;
    }

    protected function normalizeFilePath(string $filePath): string
    {
        if (!str_starts_with($filePath, '/app')) {
            return Str::after($filePath, base_path() . DIRECTORY_SEPARATOR);
        }

        return $filePath;
    }
}