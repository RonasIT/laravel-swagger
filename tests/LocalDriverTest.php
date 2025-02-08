<?php

namespace RonasIT\AutoDoc\Tests;

use RonasIT\AutoDoc\Drivers\LocalDriver;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use RonasIT\AutoDoc\Exceptions\MissedProductionFilePathException;

class LocalDriverTest extends TestCase
{
    protected static LocalDriver $localDriverClass;
    protected static string $baseFile;
    protected static string $tmpDocumentationFilePath;
    protected static array $tmpData;

    public function setUp(): void
    {
        parent::setUp();

        $documentationDirectory = config('auto-doc.drivers.local.directory');

        self::$baseFile ??= storage_path("{$documentationDirectory}/documentation.json");
        self::$tmpDocumentationFilePath ??= storage_path('temp_documentation.json');

        self::$tmpData ??= $this->getJsonFixture('tmp_data');

        config(['auto-doc.drivers.local.base_file_name' => 'documentation']);

        self::$localDriverClass ??= new LocalDriver();
    }

    public function testDirectoryEndsWithDirectorySeparator()
    {
        config(['auto-doc.drivers.local.directory' => 'documentations'.DIRECTORY_SEPARATOR]);

        $driver = new LocalDriver();
        $driver->saveTmpData(self::$tmpData);

        $this->assertFileExists(self::$tmpDocumentationFilePath);
        $this->assertFileEquals($this->generateFixturePath('tmp_data_non_formatted.json'), self::$tmpDocumentationFilePath);
    }

    public function testSaveTmpData()
    {
        self::$localDriverClass->saveTmpData(self::$tmpData);

        $this->assertFileExists(self::$tmpDocumentationFilePath);
        $this->assertFileEquals($this->generateFixturePath('tmp_data_non_formatted.json'), self::$tmpDocumentationFilePath);
    }

    public function testGetTmpData()
    {
        file_put_contents(self::$tmpDocumentationFilePath, json_encode(self::$tmpData));

        $result = self::$localDriverClass->getTmpData();

        $this->assertEquals(self::$tmpData, $result);
    }

    public function testGetTmpDataNoFile()
    {
        $result = self::$localDriverClass->getTmpData();

        $this->assertNull($result);
    }

    public function testCreateClassConfigEmpty()
    {
        $this->expectException(MissedProductionFilePathException::class);

        config(['auto-doc.drivers.local.base_file_name' => null]);

        new LocalDriver();
    }

    public function testGetAndSaveTmpData()
    {
        self::$localDriverClass->saveTmpData(self::$tmpData);

        $this->assertEqualsJsonFixture('tmp_data', self::$localDriverClass->getTmpData());
    }

    public function testSaveData()
    {
        file_put_contents(self::$tmpDocumentationFilePath, json_encode(self::$tmpData));

        self::$localDriverClass->saveData();

        $this->assertFileExists(self::$baseFile);
        $this->assertFileEquals($this->generateFixturePath('tmp_data_non_formatted.json'), self::$baseFile);

        $this->assertFileDoesNotExist(self::$tmpDocumentationFilePath);
    }

    public function testSaveDataWhenDirectoryNotExists()
    {
        $documentationDirectory = 'test_directory';
        if (is_dir($documentationDirectory)) {
            rmdir(storage_path($documentationDirectory));
        }

        self::$localDriverClass->saveTmpData(self::$tmpData);

        self::$localDriverClass->saveData();

        $this->assertFileExists(self::$baseFile);
        $this->assertFileEquals($this->generateFixturePath('tmp_data_non_formatted.json'), self::$baseFile);

        $this->assertFileDoesNotExist(self::$tmpDocumentationFilePath);
    }

    public function testGetDocumentation()
    {
        file_put_contents(self::$baseFile, json_encode(self::$tmpData));

        $documentation = self::$localDriverClass->getDocumentation();

        $this->assertEqualsJsonFixture('tmp_data', $documentation);
    }

    public function testGetDocumentationFileNotExists()
    {
        $this->expectException(FileNotFoundException::class);

        config(['auto-doc.drivers.local.base_file_name' => 'not_exists_file.json']);

        (new LocalDriver())->getDocumentation();
    }
}
