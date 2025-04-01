<?php

namespace RonasIT\AutoDoc\Contracts;

interface SwaggerDriverContract
{
    /**
     * Save (result) temporary data
     *
     * @param callable $callback
     */
    public function appendProcessDataToTmpFile(callable $callback): void;

    /**
     * Get (result) temporary data
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
