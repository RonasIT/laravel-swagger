<?php

namespace RonasIT\Support\AutoDoc\Drivers;

use stdClass;
use RonasIT\Support\AutoDoc\Interfaces\SwaggerDriverInterface;

class RemoteDriver implements SwaggerDriverInterface
{
    protected $key;
    protected $remoteUrl;

    protected static $data;

    public function __construct()
    {
        $this->key = config('auto-doc.drivers.remote.key');
        $this->remoteUrl = config('auto-doc.drivers.remote.url');
    }

    public function saveTmpData($tempData)
    {
        self::$data = $tempData;
    }

    public function getTmpData()
    {
        return self::$data;
    }

    public function saveData()
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL,$this->getUrl());
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($this->getTmpData()));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        curl_exec($curl);

        curl_close($curl);

        self::$data = [];
    }

    public function getDocumentation(): stdClass
    {
        $content = file_get_contents($this->getUrl());

        if (empty($content)) {
            throw new FileNotFoundException();
        }

        return json_decode($content);
    }

    protected function getUrl()
    {
        return "{$this->remoteUrl}/documentations/{$this->key}";
    }
}
