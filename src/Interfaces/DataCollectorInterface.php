<?php
/**
 * Created by PhpStorm.
 * User: artem
 * Date: 28.02.17
 * Time: 22:19
 */

namespace RonasIT\Support\AutoDoc\Interfaces;


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
     *
     * @param array $data
     */
    public function saveData($data);

    /**
     * Get production documentation
     */
    public function getDocumentation();
}


