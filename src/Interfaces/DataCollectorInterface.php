<?php

namespace KWXS\Support\AutoDoc\Interfaces;

interface DataCollectorInterface
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
	 * @param string|null $filePath
	 */
    public function saveData(?string $filePath = null);

    /**
     * Get production documentation
     */
    public function getDocumentation();
}


