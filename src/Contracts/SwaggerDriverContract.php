<?php

namespace RonasIT\AutoDoc\Contracts;

interface SwaggerDriverContract
{
    /**
     * Add current process tmp data to the temp documentation file
     *
     * @param callable $appendDataCallback with 1 array argument, containing the current temp file content as array
     */
    public function appendProcessDataToTmpFile(callable $appendDataCallback): void;

    /**
     * Get temporary data from the temp file shared between all PHPUnit processes
     */
    public function getTmpData(): ?array;

    /**
     * Save current process temporary data
     *
     * @param array $data
     */
    public function saveProcessTmpData(array $data): void;

    /**
     * Get current process temporary data
     */
    public function getProcessTmpData(): ?array;

    /**
     * Save production data
     */
    public function saveData();

    /**
     * Get production documentation
     */
    public function getDocumentation(): array;
}
