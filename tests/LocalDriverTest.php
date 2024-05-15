<?php

namespace RonasIT\Support\Tests;

use RonasIT\Support\AutoDoc\Drivers\LocalDriver;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Storage;
use RonasIT\Support\AutoDoc\Exceptions\MissedProductionFilePathException;

class LocalDriverTest extends TestCase
{
    protected $localDriverClass;
    protected $productionFilePath;
    protected $filePath;
    protected $fileName;
    protected $tmpDocumentationFilePath;
    protected $tmpData;

    public function setUp(): void
    {
        parent::setUp();

        $this->filePath = Storage::path('storage');
        $this->fileName = '/documentation.json';
        $this->productionFilePath = storage_path($this->filePath.$this->fileName);
        $this->tmpDocumentationFilePath = storage_path('temp_documentation.json');

        $this->tmpData = $this->getJsonFixture('tmp_data');

        config([
            'auto-doc.drivers.local.file_name' => $this->fileName,
            'auto-doc.drivers.local.file_path' => $this->filePath,
        ]);

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

        config([
            'auto-doc.drivers.local.file_name' => null,
            'auto-doc.drivers.local.file_path' => null,
        ]);
        new LocalDriver();
    }

    public function testCreateDirectoryIfNotExists()
    {
        $productionPath = 'non_existent_directory/documentation.json';

        Storage::makeDirectory($productionPath);

        config([
            'auto-doc.drivers.local.file_name' => '/documentation.json',
            'auto-doc.drivers.local.file_path' => 'non_existent_directory',
        ]);

        $this->assertTrue(Storage::exists($productionPath));

        Storage::delete($productionPath);
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

        config([
            'auto-doc.drivers.local.file_name' => 'not_exists_file',
            'auto-doc.drivers.local.file_path' => 'not_exists_file',
        ]);

        (new LocalDriver())->getDocumentation();
    }
}
