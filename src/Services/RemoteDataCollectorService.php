<?php
/**
 * Created by PhpStorm.
 * User: artem
 * Date: 25.02.17
 * Time: 11:28
 */

namespace  RonasIT\Support\AutoDoc\Services;


use RonasIT\Support\AutoDoc\Exceptions\CannotFindTemporaryFileException;
use Illuminate\Support\Str;

class RemoteDataCollectorService
{
    protected $remoteUrl;
    protected $tempFilePath;
    protected $key;

    public function __construct()
    {
        $this->tempfilePath = config('auto-doc.files.temporary');
        $this->key = Str::camel(config('auto-doc.info.title'));
        $this->remoteUrl = "http://localhost:8000/documentations/{$this->key}";

        if (empty($this->tempfilePath)) {
            throw new CannotFindTemporaryFileException();
        }
    }

    public function saveData($tempFile){
        $this->tempfilePath = $tempFile;

        $this->makeRequest();
    }

    public function getFileContent() {
        $content = json_decode(file_get_contents($this->remoteUrl), true);

        return json_decode($content['document']);
    }

    protected function makeRequest() {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->remoteUrl,
            CURLOPT_POST => 1,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => [
                'document' => file_get_contents($this->tempfilePath),
            ]
        ]);

        $response = curl_exec($curl);

        if (curl_error($curl)) {
            throw new CurlRequestErrorException();
        } else {
            curl_close($curl);
        }
    }
}