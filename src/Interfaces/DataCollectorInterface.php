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
    public function saveTmpData($data);

    public function getTmpData();

    public function saveData($data);

    public function getFileContent();
}


