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

        $additionalDocs = config('auto-doc.additional_paths');

        if (!empty($additionalDocs)) {
            $documentation = json_decode(json_encode($documentation), true);

            foreach ($additionalDocs as $filePath) {
                $fileContent = json_decode(file_get_contents($filePath), true);

                $paths = array_keys($fileContent['paths']);

                foreach ($paths as $path) {
                    if (empty($documentation['paths'][$path])) {
                        $documentation['paths'][$path] = $fileContent['paths'][$path];
                    } else {
                        $methods = array_keys($documentation['paths'][$path]);
                        $additionalDocMethods = array_keys($fileContent['paths'][$path]);

                        foreach ($additionalDocMethods as $method) {
                            if (!in_array($method, $methods)) {
                                $documentation['paths'][$path][$method] = $fileContent['paths'][$path][$method];
                            }
                        }
                    }
                }

                $definitions = array_keys($fileContent['definitions']);

                foreach ($definitions as $definition) {
                    if (empty($documentation['definitions'][$path])) {
                        $documentation['definitions'][$definition] = $fileContent['definitions'][$definition];
                    }
                }
            }
        }

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
        $filePath = base_path("vendor/ronasit/laravel-swagger/src/Views/swagger/{$file}");

        if (!file_exists($filePath)) {
            throw new NotFoundHttpException();
        }

        $content = file_get_contents($filePath);

        return response($content)->header('Content-Type', $request->getAcceptableContentTypes());
    }
}