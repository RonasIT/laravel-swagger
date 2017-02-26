<?php
/**
 * Created by PhpStorm.
 * User: artem
 * Date: 25.02.17
 * Time: 10:44
 */

namespace  RonasIT\Support\AutoDoc\Services;


use Illuminate\Contracts\Filesystem\FileNotFoundException;
use RonasIT\Support\AutoDoc\Exceptions\CannotFindTemporaryFileException;

class LocalDataCollectorService
{
    protected $filePath;

    public function __construct()
    {
        $this->filePath = config('auto-doc.files.production');

        if (empty($this->filePath)) {
            throw new CannotFindTemporaryFileException();
        }
    }

    public function saveData($tempData){
        rename($tempData, $this->filePath);
    }

    public function getFileContent() {
        if (!file_exists($this->filePath)) {
            throw new FileNotFoundException();
        }

        $fileContent = file_get_contents($this->filePath);

        return json_decode($fileContent);
    }
}
