<?php

namespace KWXS\Support\AutoDoc\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use KWXS\Support\AutoDoc\Services\SwaggerService;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AutoDocController extends BaseController
{
    protected $service;

    public function __construct()
    {
        $this->service = app(SwaggerService::class);
    }

    public function documentation()
    {
        $documentation = $this->service->getDocFileContent();

        return response()->json($documentation);
    }

    public function index()
    {
        return view('swagger::documentation');
    }

    public function getFile($file)
    {
        $filePath = base_path("vendor/ronasit/laravel-swagger/src/Views/swagger/{$file}");

        if (!file_exists($filePath)) {
            throw new HttpException('File does not exist: ' . $filePath);
        }

        $content = file_get_contents($filePath);

        return response($content);
    }
}
