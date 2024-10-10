<?php

namespace RonasIT\AutoDoc\Tests;

use RonasIT\AutoDoc\Drivers\LocalDriver;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use RonasIT\AutoDoc\Exceptions\MissedProductionFilePathException;

class LocalDriverTest extends TestCase
{
    protected static LocalDriver $localDriverClass;
    protected static string $productionFilePath;
    protected static string $tmpDocumentationFilePath;
    protected static array $tmpData;

    public function setUp(): void
    {
        parent::setUp();

        self::$productionFilePath ??= __DIR__ . '/../storage/documentation.json';
        self::$tmpDocumentationFilePath ??= __DIR__ . '/../storage/temp_documentation.json';

        self::$tmpData ??= $this->getJsonFixture('tmp_data');

        config(['auto-doc.drivers.local.production_path' => self::$productionFilePath]);

        self::$localDriverClass ??= new LocalDriver();
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

        config(['auto-doc.drivers.local.production_path' => null]);

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

        $this->assertFileExists(self::$productionFilePath);
        $this->assertFileEquals($this->generateFixturePath('tmp_data_non_formatted.json'), self::$productionFilePath);

        $this->assertFileDoesNotExist(self::$tmpDocumentationFilePath);
    }

    public function testGetDocumentation()
    {
        file_put_contents(self::$productionFilePath, json_encode(self::$tmpData));

        $documentation = self::$localDriverClass->getDocumentation();

        $this->assertEqualsJsonFixture('tmp_data', $documentation);
    }

    public function testGetDocumentationFileNotExists()
    {
        $this->expectException(FileNotFoundException::class);

        config(['auto-doc.drivers.local.production_path' => 'not_exists_file']);

        (new LocalDriver())->getDocumentation();
    }
}
