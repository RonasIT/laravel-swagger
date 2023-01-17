<?php

namespace RonasIT\Support\AutoDoc\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use RonasIT\Support\AutoDoc\Services\SwaggerService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
        $currentEnvironment = config('app.env');

        if (in_array($currentEnvironment, config('auto-doc.display_environments'))) {
            return view('auto-doc::documentation');
        }

        return response('Forbidden.', 403);
    }

    public function getFile(Request $request, $file)
    {
        $filePath = base_path("vendor/ronasit/laravel-swagger/resources/assets/swagger/{$file}");

        if (!file_exists($filePath)) {
            throw new NotFoundHttpException();
        }

        $content = file_get_contents($filePath);

        return response($content)->header('Content-Type', $request->getAcceptableContentTypes());
    }
}
