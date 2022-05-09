<?php

namespace RonasIT\Support\AutoDoc\Drivers;

use stdClass;
use RonasIT\Support\AutoDoc\Interfaces\SwaggerDriverInterface;

class RemoteDriver implements SwaggerDriverInterface
{
    protected $key;
    protected $remoteUrl;
    protected $tempFileName;

    public function __construct()
    {
        $this->key = config('auto-doc.drivers.remote.key');
        $this->remoteUrl = config('auto-doc.drivers.remote.url');
        $this->tempFileName = storage_path('temp_documentation.json');
    }

    public function saveTmpData($data)
    {
        file_put_contents($this->tempFileName, json_encode($data));
    }

    public function getTmpData()
    {
        if (file_exists($this->tempFileName)) {
            $content = file_get_contents($this->tempFileName);

            return json_decode($content, true);
        }

        return null;
    }

    public function saveData()
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $this->getUrl());
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($this->getTmpData()));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        curl_exec($curl);
        curl_close($curl);

        if (file_exists($this->tempFileName)) {
            unlink($this->tempFileName);
        }
    }

    public function getDocumentation(): stdClass
    {
        $content = file_get_contents($this->getUrl());

        if (empty($content)) {
            throw new FileNotFoundException();
        }

        return json_decode($content);
    }

    protected function getUrl(): string
    {
        return "{$this->remoteUrl}/documentations/{$this->key}";
    }
}
