<?php

namespace RonasIT\AutoDoc\Contracts;

interface SwaggerDriverContract
{
    /**
     * Save current temporary data
     *
     * @param array $data
     */
    public function saveTmpData(array $data): void;

    /**
     * Get current temporary data
     */
    public function getTmpData(): ?array;

    /**
     * Save shared (result) temporary data
     */
    public function saveSharedTmpData(callable $callback): void;

    /**
     * Get shared (result) temporary data
     */
    public function getSharedTmpData(): ?array;

    /**
     * Save production data
     */
    public function saveData();

    /**
     * Get production documentation
     */
    public function getDocumentation(): array;
}
