<?php

namespace RonasIT\Support\Tests;

use RonasIT\Support\AutoDoc\Drivers\RemoteDriver;

class RemoteDriverTest extends TestCase
{
    protected $tmpData;
    protected $removeDriverClass;
    protected $tmpDocumentationFilePath;

    public function setUp(): void
    {
        parent::setUp();

        $this->tmpData = $this->getJsonFixture('tmp_data');
        $this->tmpDocumentationFilePath = __DIR__ . '/storage/temp_documentation.json';

        $this->removeDriverClass = new RemoteDriver();
    }

    public function testSaveTmpData()
    {
        $this->removeDriverClass->saveTmpData($this->tmpData);

        $this->assertFileExists($this->tmpDocumentationFilePath);
        $this->assertFileEquals($this->generateFixturePath('tmp_data_non_formatted.json'), $this->tmpDocumentationFilePath);
    }

    public function testGetTmpData()
    {
        file_put_contents($this->tmpDocumentationFilePath, json_encode($this->tmpData));

        $result = $this->removeDriverClass->getTmpData();

        $this->assertEquals($this->tmpData, $result);
    }

    public function testGetTmpDataNoFile()
    {
        $result = $this->removeDriverClass->getTmpData();

        $this->assertNull($result);
    }
}
