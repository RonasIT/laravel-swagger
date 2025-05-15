<?php

namespace RonasIT\AutoDoc\Tests;

use RonasIT\AutoDoc\Exceptions\FileNotFoundException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use RonasIT\AutoDoc\Drivers\StorageDriver;
use RonasIT\AutoDoc\Exceptions\MissedProductionFilePathException;

class StorageDriverTest extends TestCase
{
    protected static StorageDriver $storageDriverClass;
    protected Filesystem $disk;
    protected static string $productionFilePath;
    protected static string $tmpDocumentationFilePath;
    protected static array $tmpData;

    public function setUp(): void
    {
        parent::setUp();

        $this->disk = Storage::fake('testing');

        self::$productionFilePath ??= 'documentation.json';
        self::$tmpDocumentationFilePath ??= __DIR__ . '/../storage/temp_documentation.json';

        self::$tmpData ??= $this->getJsonFixture('tmp_data');

        config(['auto-doc.drivers.storage.disk' => 'testing']);
        config(['auto-doc.drivers.storage.production_path' => self::$productionFilePath]);

        self::$storageDriverClass = new StorageDriver();
    }

    public function testSaveProcessTmpData()
    {
        self::$storageDriverClass->saveProcessTmpData(self::$tmpData);

        $this->assertFileExists(self::$tmpDocumentationFilePath);
        $this->assertFileEquals($this->generateFixturePath('tmp_data_non_formatted.json'), self::$tmpDocumentationFilePath);
    }

    public function testAppendProcessDataToTempFile()
    {
        self::$storageDriverClass->appendProcessDataToTmpFile(fn () => self::$tmpData);

        $this->assertFileExists(self::$tmpDocumentationFilePath);
        $this->assertFileEquals($this->generateFixturePath('tmp_data_non_formatted.json'), self::$tmpDocumentationFilePath);
    }

    public function testGetProcessTmpData()
    {
        file_put_contents(self::$tmpDocumentationFilePath, json_encode(self::$tmpData));

        $result = self::$storageDriverClass->getProcessTmpData();

        $this->assertEquals(self::$tmpData, $result);
    }

    public function testGetProcessTmpDataNoFile()
    {
        $result = self::$storageDriverClass->getProcessTmpData();

        $this->assertNull($result);
    }

    public function testGetTmpData()
    {
        file_put_contents(self::$tmpDocumentationFilePath, json_encode(self::$tmpData));

        $result = self::$storageDriverClass->getTmpData();

        $this->assertEquals(self::$tmpData, $result);
    }

    public function testGetTmpDataNoFile()
    {
        $result = self::$storageDriverClass->getTmpData();

        $this->assertNull($result);
    }

    public function testCreateClassConfigEmpty()
    {
        $this->expectException(MissedProductionFilePathException::class);

        config(['auto-doc.drivers.storage.production_path' => null]);

        new StorageDriver();
    }

    public function testGetAndSaveProcessTmpData()
    {
        self::$storageDriverClass->saveProcessTmpData(self::$tmpData);

        $this->assertEqualsJsonFixture('tmp_data', self::$storageDriverClass->getProcessTmpData());
    }

    public function testSaveData()
    {
        file_put_contents(self::$tmpDocumentationFilePath, json_encode(self::$tmpData));

        self::$storageDriverClass->saveData();

        $this->disk->assertExists(self::$productionFilePath);
        $this->assertEqualsFixture('tmp_data_non_formatted.json', $this->disk->get(self::$productionFilePath));

        $this->assertFileDoesNotExist(self::$tmpDocumentationFilePath);
    }

    public function testGetDocumentation()
    {
        $this->disk->put(self::$productionFilePath, $this->getFixture('tmp_data_non_formatted.json'));

        $documentation = self::$storageDriverClass->getDocumentation();

        $this->assertEqualsJsonFixture('tmp_data', $documentation);
    }

    public function testGetDocumentationFileNotExists()
    {
        $this->expectException(FileNotFoundException::class);

        $this->expectExceptionMessageMatches('/^Documentation file not found documentation.json$/');

        self::$storageDriverClass->getDocumentation();
    }
}
