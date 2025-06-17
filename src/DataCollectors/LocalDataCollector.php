<?php

namespace KWXS\Support\AutoDoc\DataCollectors;

use KWXS\Support\AutoDoc\Exceptions\MissedProductionFilePathException;
use KWXS\Support\AutoDoc\Interfaces\DataCollectorInterface;
use LogicException;

class LocalDataCollector implements DataCollectorInterface
{
	protected static $data;

	public $prodFilePath;

	public $tempFilePath;

	public function __construct()
	{
		$this->prodFilePath = config('swagger.production_path');

		if (empty($this->prodFilePath)) {
			throw new MissedProductionFilePathException();
		}
	}

	public function saveTmpData($tempData)
	{
		self::$data = $tempData;
	}

	public function getTmpData()
	{
		return self::$data;
	}

	public function saveData(string $filePath = null)
	{
		$filePath = $filePath ?? $this->prodFilePath;

		if (!$filePath) {
			throw new MissedProductionFilePathException();
		}

		$content = json_encode(self::$data);

		file_put_contents($filePath, $content);

		self::$data = [];
	}

	public function getDocumentation()
	{
		if (!file_exists($this->prodFilePath)) {
			throw new LogicException('File does not exist: ' . $this->prodFilePath);
		}

		$fileContent = file_get_contents($this->prodFilePath);

		return json_decode($fileContent);
	}
}
