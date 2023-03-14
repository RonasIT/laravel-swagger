<?php

namespace RonasIT\Support\Tests;

use RonasIT\Support\AutoDoc\Drivers\RemoteDriver;
use RonasIT\Support\AutoDoc\Exceptions\MissedRemoteDocumentationUrlException;
use RonasIT\Support\Tests\Support\Traits\MockTrait;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class RemoteDriverTest extends TestCase
{
    use MockTrait;

    protected $tmpData;
    protected $remoteDriverClass;
    protected $tmpDocumentationFilePath;

    public function setUp(): void
    {
        parent::setUp();

        $this->tmpData = $this->getJsonFixture('tmp_data');
        $this->tmpDocumentationFilePath = __DIR__ . '/../storage/temp_documentation.json';

        $this->remoteDriverClass = new RemoteDriver();
    }

    public function testSaveTmpData()
    {
        $this->remoteDriverClass->saveTmpData($this->tmpData);

        $this->assertFileExists($this->tmpDocumentationFilePath);
        $this->assertFileEquals($this->generateFixturePath('tmp_data_non_formatted.json'), $this->tmpDocumentationFilePath);
    }

    public function testGetTmpData()
    {
        file_put_contents($this->tmpDocumentationFilePath, json_encode($this->tmpData));

        $result = $this->remoteDriverClass->getTmpData();

        $this->assertEquals($this->tmpData, $result);
    }

    public function testGetTmpDataNoFile()
    {
        $result = $this->remoteDriverClass->getTmpData();

        $this->assertNull($result);
    }

    public function testCreateClassConfigEmpty()
    {
        $this->expectException(MissedRemoteDocumentationUrlException::class);

        config(['auto-doc.drivers.remote.url' => null]);

        new RemoteDriver();
    }

    public function testSaveData()
    {
        config(['auto-doc.drivers.remote.key' => 'mocked_key']);
        config(['auto-doc.drivers.remote.url' => 'mocked_url']);

        $mock = $this->mockClass(RemoteDriver::class, ['makeHttpRequest']);

        $mock
            ->expects($this->once())
            ->method('makeHttpRequest')
            ->with('post', 'mocked_url/documentations/mocked_key', $this->tmpData, [
                'Content-Type: application/json'
            ])
            ->willReturn(['', 204]);

        file_put_contents($this->tmpDocumentationFilePath, json_encode($this->tmpData));

        $mock->saveData();

        $this->assertFileDoesNotExist($this->tmpDocumentationFilePath);
    }

    public function testSaveDataWithoutTmpFile()
    {
        config(['auto-doc.drivers.remote.key' => 'mocked_key']);
        config(['auto-doc.drivers.remote.url' => 'mocked_url']);

        $mock = $this->mockClass(RemoteDriver::class, ['makeHttpRequest']);

        $mock
            ->expects($this->once())
            ->method('makeHttpRequest')
            ->with('post', 'mocked_url/documentations/mocked_key', null, [
                'Content-Type: application/json'
            ])
            ->willReturn(['', 204]);

        $mock->saveData();
    }

    public function testGetDocumentation()
    {
        config(['auto-doc.drivers.remote.key' => 'mocked_key']);
        config(['auto-doc.drivers.remote.url' => 'mocked_url']);

        $mock = $this->mockClass(RemoteDriver::class, ['makeHttpRequest']);

        $mock
            ->expects($this->once())
            ->method('makeHttpRequest')
            ->with('get', 'mocked_url/documentations/mocked_key')
            ->willReturn([$this->getFixture('tmp_data_non_formatted.json'), 200]);

        $documentation = $mock->getDocumentation();

        $this->assertEquals($this->tmpData, $documentation);
    }

    public function testGetDocumentationNoFile()
    {
        $this->expectException(FileNotFoundException::class);

        config(['auto-doc.drivers.remote.key' => 'mocked_key']);
        config(['auto-doc.drivers.remote.url' => 'mocked_url']);

        $mock = $this->mockClass(RemoteDriver::class, ['makeHttpRequest']);

        $mock
            ->expects($this->once())
            ->method('makeHttpRequest')
            ->with('get', 'mocked_url/documentations/mocked_key')
            ->willReturn([json_encode(['error' => 'Not found.']), 404]);

        $documentation = $mock->getDocumentation();

        $this->assertEquals($this->tmpData, $documentation);
    }
}
