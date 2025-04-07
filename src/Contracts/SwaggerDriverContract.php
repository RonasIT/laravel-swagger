<?php

namespace RonasIT\AutoDoc\Contracts;

interface SwaggerDriverContract
{
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
     *
     * @param array $data
    */
    public function saveData(array $data): void;

    /**
     * Get production documentation
     */
    public function getDocumentation(): array;
}
