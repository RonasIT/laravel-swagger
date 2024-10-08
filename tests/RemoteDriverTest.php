<?php

namespace RonasIT\AutoDoc\Tests;

use RonasIT\AutoDoc\Drivers\RemoteDriver;
use RonasIT\AutoDoc\Exceptions\MissedRemoteDocumentationUrlException;
use RonasIT\AutoDoc\Tests\Support\Traits\MockTrait;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class RemoteDriverTest extends TestCase
{
    use MockTrait;

    protected static array $tmpData;
    protected static RemoteDriver $remoteDriverClass;
    protected static string $tmpDocumentationFilePath;

    public function setUp(): void
    {
        parent::setUp();

        self::$tmpData ??= $this->getJsonFixture('tmp_data');
        self::$tmpDocumentationFilePath ??= __DIR__ . '/../storage/temp_documentation.json';

        self::$remoteDriverClass ??= new RemoteDriver();
    }

    public function testSaveTmpData()
    {
        self::$remoteDriverClass->saveTmpData(self::$tmpData);

        $this->assertFileExists(self::$tmpDocumentationFilePath);
        $this->assertFileEquals($this->generateFixturePath('tmp_data_non_formatted.json'), self::$tmpDocumentationFilePath);
    }

    public function testGetTmpData()
    {
        file_put_contents(self::$tmpDocumentationFilePath, json_encode(self::$tmpData));

        $result = self::$remoteDriverClass->getTmpData();

        $this->assertEquals(self::$tmpData, $result);
    }

    public function testGetTmpDataNoFile()
    {
        $result = self::$remoteDriverClass->getTmpData();

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
            ->with('post', 'mocked_url/documentations/mocked_key', self::$tmpData, [
                'Content-Type: application/json',
            ])
            ->willReturn(['', 204]);

        file_put_contents(self::$tmpDocumentationFilePath, json_encode(self::$tmpData));

        $mock->saveData();

        $this->assertFileDoesNotExist(self::$tmpDocumentationFilePath);
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
                'Content-Type: application/json',
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

        $this->assertEquals(self::$tmpData, $documentation);
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

        $this->assertEquals(self::$tmpData, $documentation);
    }
}
