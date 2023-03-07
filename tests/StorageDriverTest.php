<?php

namespace RonasIT\Support\Tests;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Storage;
use RonasIT\Support\AutoDoc\Drivers\StorageDriver;
use RonasIT\Support\AutoDoc\Exceptions\MissedProductionFilePathException;

class StorageDriverTest extends TestCase
{
    protected $storageDriverClass;
    protected $disk;
    protected $productionFilePath;
    protected $tmpDocumentationFilePath;
    protected $tmpData;

    public function setUp(): void
    {
        parent::setUp();

        $this->disk = Storage::fake('testing');

        $this->productionFilePath = 'documentation.json';
        $this->tmpDocumentationFilePath = 'temp_documentation.json';

        $this->tmpData = $this->getJsonFixture('tmp_data');

        config(['auto-doc.drivers.storage.disk' => 'testing']);
        config(['auto-doc.drivers.storage.production_path' => $this->productionFilePath]);

        $this->storageDriverClass = new StorageDriver();
    }

    public function testSaveTmpData()
    {
        $this->storageDriverClass->saveTmpData($this->tmpData);

        $this->disk->assertExists($this->tmpDocumentationFilePath);

        $this->assertEqualsFixture('tmp_data_non_formatted.json', $this->disk->get($this->tmpDocumentationFilePath));
    }

    public function testGetTmpData()
    {
        $this->disk->put($this->tmpDocumentationFilePath, json_encode($this->tmpData));

        $result = $this->storageDriverClass->getTmpData();

        $this->assertEqualsJsonFixture('tmp_data', $result);
    }

    public function testGetTmpDataNoFile()
    {
        $result = $this->storageDriverClass->getTmpData();

        $this->assertNull($result);
    }

    public function testCreateClassConfigEmpty()
    {
        $this->expectException(MissedProductionFilePathException::class);

        config(['auto-doc.drivers.storage.production_path' => null]);

        new StorageDriver();
    }

    public function testGetAndSaveTmpData()
    {
        $this->storageDriverClass->saveTmpData($this->tmpData);

        $this->assertEqualsJsonFixture('tmp_data', $this->storageDriverClass->getTmpData());
    }

    public function testSaveData()
    {
        $this->disk->put($this->tmpDocumentationFilePath, json_encode($this->tmpData));

        $this->storageDriverClass->saveData();

        $this->disk->assertExists($this->productionFilePath);
        $this->assertEqualsFixture('tmp_data_non_formatted.json', $this->disk->get($this->productionFilePath));

        $this->disk->assertMissing($this->tmpDocumentationFilePath);
    }

    public function testGetDocumentation()
    {
        $this->disk->put($this->productionFilePath, $this->getFixture('tmp_data_non_formatted.json'));

        $documentation = $this->storageDriverClass->getDocumentation();

        $this->assertEqualsJsonFixture('tmp_data', $documentation);
    }

    public function testGetDocumentationFileNotExists()
    {
        $this->expectException(FileNotFoundException::class);

        $this->storageDriverClass->getDocumentation();
    }
}
