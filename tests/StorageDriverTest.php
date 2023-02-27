<?php

namespace RonasIT\Support\Tests;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Storage;
use RonasIT\Support\AutoDoc\Drivers\StorageDriver;

class StorageDriverTest extends TestCase
{
    protected $disk;
    protected $tmpData;
    protected $filePath;
    protected $storageDriverClass;

    public function setUp(): void
    {
        parent::setUp();

        $this->tmpData = $this->getJsonFixture('tmp_data');
        $this->filePath = __DIR__ . '/storage/documentation.json';

        config(['auto-doc.drivers.storage.disk' => 'testing']);
        config(['auto-doc.drivers.storage.production_path' => $this->filePath]);

        $this->disk = Storage::fake('testing');

        $this->storageDriverClass = new StorageDriver();
    }

    public function testGetAndSaveTmpData()
    {
        $this->storageDriverClass->saveTmpData($this->tmpData);

        $this->assertEquals($this->tmpData, $this->storageDriverClass->getTmpData());
    }

    public function testSaveData()
    {
        $this->storageDriverClass->saveTmpData($this->tmpData);

        $this->storageDriverClass->saveData();

        $this->disk->assertExists($this->filePath);
        $this->assertEqualsFixture('tmp_data_non_formatted.json', $this->disk->get($this->filePath));

        $this->assertEquals([], $this->storageDriverClass->getTmpData());
    }

    public function testGetDocumentation()
    {
        $this->disk->put($this->filePath, $this->getFixture('tmp_data_non_formatted.json'));

        $documentation = $this->storageDriverClass->getDocumentation();

        $this->assertEqualsJsonFixture('tmp_data', $documentation);
    }

    public function testGetDocumentationFileNotExists()
    {
        $this->expectException(FileNotFoundException::class);

        $this->storageDriverClass->getDocumentation();
    }
}
