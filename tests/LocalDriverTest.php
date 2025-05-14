<?php

namespace RonasIT\AutoDoc\Tests;

use RonasIT\AutoDoc\Drivers\LocalDriver;
use RonasIT\AutoDoc\Exceptions\FileNotFoundException;
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

    public function testSaveProcessTmpData()
    {
        self::$localDriverClass->saveProcessTmpData(self::$tmpData);

        $this->assertFileExists(self::$tmpDocumentationFilePath);
        $this->assertFileEquals($this->generateFixturePath('tmp_data_non_formatted.json'), self::$tmpDocumentationFilePath);
    }

    public function testSaveProcessTmpDataCheckTokenBasedPath()
    {
        $this->mockParallelTestingToken();

        $processTempFilePath = __DIR__ . '/../storage/temp_documentation_workerID.json';

        app(LocalDriver::class)->saveProcessTmpData(self::$tmpData);

        $this->assertFileExists($processTempFilePath);
        $this->assertFileEquals($this->generateFixturePath('tmp_data_non_formatted.json'), $processTempFilePath);
    }

    public function testAppendProcessTempDataToTempFile()
    {
        self::$localDriverClass->appendProcessDataToTmpFile(fn () => self::$tmpData);

        $this->assertFileExists(self::$tmpDocumentationFilePath);
        $this->assertFileEquals($this->generateFixturePath('tmp_data_non_formatted.json'), self::$tmpDocumentationFilePath);
    }

    public function testGetProcessTmpData()
    {
        file_put_contents(self::$tmpDocumentationFilePath, json_encode(self::$tmpData));

        $result = self::$localDriverClass->getProcessTmpData();

        $this->assertEquals(self::$tmpData, $result);
    }

    public function testGetProcessTmpDataNoFile()
    {
        $result = self::$localDriverClass->getProcessTmpData();

        $this->assertNull($result);
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

    public function testGetAndSaveProcessTmpData()
    {
        self::$localDriverClass->saveProcessTmpData(self::$tmpData);

        $this->assertEqualsJsonFixture('tmp_data', self::$localDriverClass->getProcessTmpData());
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

        $this->expectExceptionMessage('Documentation file not found: '.config(['auto-doc.drivers.local.production_path']));

        config(['auto-doc.drivers.local.production_path' => 'not_exists_file']);

        (new LocalDriver())->getDocumentation();
    }
}
