<?php

namespace RonasIT\Support\Tests;

use RonasIT\Support\AutoDoc\Drivers\LocalDriver;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use RonasIT\Support\AutoDoc\Exceptions\MissedProductionFilePathException;

class LocalDriverTest extends TestCase
{
    protected $tmpData;
    protected $localDriverClass;
    protected $productionFilePath;

    public function setUp(): void
    {
        parent::setUp();

        $this->tmpData = $this->getJsonFixture('tmp_data');
        $this->productionFilePath = __DIR__ . '/../storage/documentation.json';

        config(['auto-doc.drivers.local.production_path' => $this->productionFilePath]);

        $this->localDriverClass = new LocalDriver();
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

        $this->assertEquals($this->tmpData, $this->localDriverClass->getTmpData());
    }

    public function testSaveData()
    {
        $this->localDriverClass->saveTmpData($this->tmpData);

        $this->localDriverClass->saveData();

        $this->assertFileExists($this->productionFilePath);
        $this->assertFileEquals($this->generateFixturePath('tmp_data_non_formatted.json'), $this->productionFilePath);

        $this->assertEquals([], $this->localDriverClass->getTmpData());
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
