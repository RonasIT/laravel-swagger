<?php

namespace RonasIT\AutoDoc\Tests;

use RonasIT\AutoDoc\Drivers\RemoteDriver;
use RonasIT\AutoDoc\Exceptions\MissedRemoteDocumentationUrlException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use RonasIT\Support\Traits\MockTrait;

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

    public function testSaveProcessTempData()
    {
        self::$remoteDriverClass->saveProcessTmpData(self::$tmpData);

        $this->assertFileExists(self::$tmpDocumentationFilePath);
        $this->assertFileEquals($this->generateFixturePath('tmp_data_non_formatted.json'), self::$tmpDocumentationFilePath);
    }

    public function testAppendProcessDataToTempFile()
    {
        self::$remoteDriverClass->appendProcessDataToTmpFile(fn () => self::$tmpData);

        $this->assertFileExists(self::$tmpDocumentationFilePath);
        $this->assertFileEquals($this->generateFixturePath('tmp_data_non_formatted.json'), self::$tmpDocumentationFilePath);
    }

    public function testGetProcessTempData()
    {
        file_put_contents(self::$tmpDocumentationFilePath, json_encode(self::$tmpData));

        $result = self::$remoteDriverClass->getProcessTmpData();

        $this->assertEquals(self::$tmpData, $result);
    }

    public function testGetProcessTempDataNoFile()
    {
        $result = self::$remoteDriverClass->getProcessTmpData();

        $this->assertNull($result);
    }

    public function testGetTempData()
    {
        file_put_contents(self::$tmpDocumentationFilePath, json_encode(self::$tmpData));

        $result = self::$remoteDriverClass->getTmpData();

        $this->assertEquals(self::$tmpData, $result);
    }

    public function testGetTempDataNoFile()
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

        $mock = $this->mockClass(RemoteDriver::class, [
            $this->functionCall(
                name: 'makeHttpRequest',
                arguments: [
                    'post',
                    'mocked_url/documentations/mocked_key',
                    self::$tmpData,
                    ['Content-Type: application/json'],
                ],
                result: ['', 204],
            ),
        ]);

        file_put_contents(self::$tmpDocumentationFilePath, json_encode(self::$tmpData));

        $mock->saveData();

        $this->assertFileDoesNotExist(self::$tmpDocumentationFilePath);
    }

    public function testSaveDataWithoutTmpFile()
    {
        config(['auto-doc.drivers.remote.key' => 'mocked_key']);
        config(['auto-doc.drivers.remote.url' => 'mocked_url']);

        $mock = $this->mockClass(RemoteDriver::class, [
            $this->functionCall(
                name: 'makeHttpRequest',
                arguments: [
                    'post',
                    'mocked_url/documentations/mocked_key',
                    null,
                    ['Content-Type: application/json'],
                ],
                result: ['', 204],
            ),
        ]);

        $mock->saveData();
    }

    public function testGetDocumentation()
    {
        config(['auto-doc.drivers.remote.key' => 'mocked_key']);
        config(['auto-doc.drivers.remote.url' => 'mocked_url']);

        $mock = $this->mockClass(RemoteDriver::class, [
            $this->functionCall(
                name: 'makeHttpRequest',
                arguments: [
                    'get',
                    'mocked_url/documentations/mocked_key',
                ],
                result: [$this->getFixture('tmp_data_non_formatted.json'), 200],
            ),
        ]);

        $documentation = $mock->getDocumentation();

        $this->assertEquals(self::$tmpData, $documentation);
    }

    public function testGetDocumentationNoFile()
    {
        $this->expectException(FileNotFoundException::class);

        config(['auto-doc.drivers.remote.key' => 'mocked_key']);
        config(['auto-doc.drivers.remote.url' => 'mocked_url']);

        $mock = $this->mockClass(RemoteDriver::class, [
            $this->functionCall(
                name: 'makeHttpRequest',
                arguments: [
                    'get',
                    'mocked_url/documentations/mocked_key',
                ],
                result: [json_encode(['error' => 'Not found.']), 404],
            ),
        ]);

        $documentation = $mock->getDocumentation();

        $this->assertEquals(self::$tmpData, $documentation);
    }
}
