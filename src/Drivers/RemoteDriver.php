<?php

namespace RonasIT\Support\AutoDoc\Drivers;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
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
        $this->makeHttpRequest('post', $this->getUrl(), $this->getTmpData(), [
            'Content-Type: application/json'
        ]);

        if (file_exists($this->tempFileName)) {
            unlink($this->tempFileName);
        }
    }

    public function getDocumentation(): array
    {
        list($content, $statusCode) = $this->makeHttpRequest('get', $this->getUrl());

        if (empty($content) || $statusCode !== 200) {
            throw new FileNotFoundException();
        }

        return json_decode($content, true);
    }

    protected function getUrl(): string
    {
        return "{$this->remoteUrl}/documentations/{$this->key}";
    }

    protected function makeHttpRequest($type, $url, $data = [], $headers = [])
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        if ($type === 'post') {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $result = curl_exec($curl);

        $statusCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

        curl_close($curl);

        return [$result, $statusCode];
    }
}
