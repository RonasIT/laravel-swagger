<?php

namespace RonasIT\Support\AutoDoc\Interfaces;

interface SwaggerDriverInterface
{
    /**
     * Save temporary data
     */
    public function saveTmpData(array $data): void;

    /**
     * Get temporary data
     */
    public function getTmpData(): void;

    /**
     * Save production data
     */
    public function saveData(): void;

    /**
     * Get production documentation
     */
    public function getDocumentation(): array;
}
