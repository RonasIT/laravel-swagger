<?php

namespace RonasIT\Support\Tests;

use RonasIT\Support\AutoDoc\Drivers\LocalDriver;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use RonasIT\Support\AutoDoc\Exceptions\MissedProductionFilePathException;

class LocalDriverTest extends TestCase
{
    protected $localDriverClass;
    protected $productionFilePath;
    protected $tmpDocumentationFilePath;
    protected $tmpData;

    public function setUp(): void
    {
        parent::setUp();

        $this->productionFilePath = __DIR__ . '/../storage/documentation.json';
        $this->tmpDocumentationFilePath = __DIR__ . '/../storage/temp_documentation.json';

        $this->tmpData = $this->getJsonFixture('tmp_data');

        config(['auto-doc.drivers.local.production_path' => $this->productionFilePath]);

        $this->localDriverClass = new LocalDriver();
    }

    public function testSaveTmpData()
    {
        $this->localDriverClass->saveTmpData($this->tmpData);

        $this->assertFileExists($this->tmpDocumentationFilePath);
        $this->assertFileEquals($this->generateFixturePath('tmp_data_non_formatted.json'), $this->tmpDocumentationFilePath);
    }

    public function testGetTmpData()
    {
        file_put_contents($this->tmpDocumentationFilePath, json_encode($this->tmpData));

        $result = $this->localDriverClass->getTmpData();

        $this->assertEquals($this->tmpData, $result);
    }

    public function testGetTmpDataNoFile()
    {
        $result = $this->localDriverClass->getTmpData();

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
        $this->localDriverClass->saveTmpData($this->tmpData);

        $this->assertEqualsJsonFixture('tmp_data', $this->localDriverClass->getTmpData());
    }

    public function testSaveData()
    {
        file_put_contents($this->tmpDocumentationFilePath, json_encode($this->tmpData));

        $this->localDriverClass->saveData();

        $this->assertFileExists($this->productionFilePath);
        $this->assertFileEquals($this->generateFixturePath('tmp_data_non_formatted.json'), $this->productionFilePath);

        $this->assertFileDoesNotExist($this->tmpDocumentationFilePath);
    }

    public function testGetDocumentation()
    {
        file_put_contents($this->productionFilePath, json_encode($this->tmpData));

        $documentation = $this->localDriverClass->getDocumentation();

        $this->assertEqualsJsonFixture('tmp_data', $documentation);
    }

    public function testGetDocumentationFileNotExists()
    {
        $this->expectException(FileNotFoundException::class);

        config(['auto-doc.drivers.local.production_path' => 'not_exists_file']);

        (new LocalDriver())->getDocumentation();
    }
}
