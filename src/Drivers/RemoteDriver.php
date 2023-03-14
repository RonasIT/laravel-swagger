<?php

namespace RonasIT\Support\AutoDoc\Drivers;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use RonasIT\Support\AutoDoc\Exceptions\MissedRemoteDocumentationUrlException;

class RemoteDriver extends BaseDriver
{
    protected $key;
    protected $remoteUrl;

    public function __construct()
    {
        parent::__construct();

        $this->key = config('auto-doc.drivers.remote.key');
        $this->remoteUrl = config('auto-doc.drivers.remote.url');

        if (empty($this->remoteUrl)) {
            throw new MissedRemoteDocumentationUrlException();
        }
    }

    public function saveData(): void
    {
        $this->makeHttpRequest('post', $this->getUrl(), $this->getTmpData(), [
            'Content-Type: application/json'
        ]);

        $this->clearTmpData();
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

    /**
     * @codeCoverageIgnore
     */
    protected function makeHttpRequest($type, $url, $data = [], $headers = []): array
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
