<?php

namespace RonasIT\Support\AutoDoc\Interfaces;

interface SwaggerDriverInterface
{
    /**
     * Save temporary data
     *
     * @param array $data
     */
    public function saveTmpData($data);

    /**
     * Get temporary data
     */
    public function getTmpData();

    /**
     * Save production data
     */
    public function saveData();

    /**
     * Get production documentation
     */
    public function getDocumentation(): array;
}
