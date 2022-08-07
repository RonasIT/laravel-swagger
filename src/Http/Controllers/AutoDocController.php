<?php

namespace RonasIT\Support\AutoDoc\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use RonasIT\Support\AutoDoc\Services\SwaggerService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AutoDocController extends BaseController
{
    protected $service;
    protected $documentation;

    public function __construct()
    {
        $this->service = app(SwaggerService::class);
    }

    public function documentation()
    {
        $this->documentation = json_decode(json_encode($this->service->getDocFileContent()), true);

        $additionalDocs = config('auto-doc.additional_paths');

        if (!empty($additionalDocs)) {
            array_map(function ($filePath) {
                $fileContent = json_decode(file_get_contents($filePath), true);

                $paths = array_keys($fileContent['paths']);

                array_map(function ($path) use ($fileContent) {
                    if (empty($this->documentation['paths'][$path])) {
                        $this->documentation['paths'][$path] = $fileContent['paths'][$path];
                    } else {
                        $methods = array_keys($this->documentation['paths'][$path]);
                        $additionalDocMethods = array_keys($fileContent['paths'][$path]);

                        array_map(function ($method) use ($methods, $path, $fileContent) {
                            if (!in_array($method, $methods)) {
                                $this->documentation['paths'][$path][$method] = $fileContent['paths'][$path][$method];
                            }
                        }, $additionalDocMethods);
                    }

                    $definitions = array_keys($fileContent['definitions']);

                    array_map(function ($definition) use ($path, $fileContent) {
                        if (empty($this->documentation['definitions'][$path])) {
                            $this->documentation['definitions'][$definition] = $fileContent['definitions'][$definition];
                        }
                    }, $definitions);
                }, $paths);
            }, $additionalDocs);
        }

        return response()->json($this->documentation);
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