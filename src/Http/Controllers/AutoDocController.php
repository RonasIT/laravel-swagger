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

        $this->mergingContentsDocFiles();

        $this->limitResponseData();

        $this->cuttingExceptions();

        return response()->json($this->documentation);
    }

    public function mergingContentsDocFiles()
    {
        $additionalDocs = config('auto-doc.additional_paths');

        if (!empty($additionalDocs)) {
            foreach ($additionalDocs as $filePath) {
                $fileContent = json_decode(file_get_contents($filePath), true);

                $paths = array_keys($fileContent['paths']);

                foreach ($paths as $path) {
                    if (empty($this->documentation['paths'][$path])) {
                        $this->documentation['paths'][$path] = $fileContent['paths'][$path];
                    } else {
                        $methods = array_keys($this->documentation['paths'][$path]);
                        $additionalDocMethods = array_keys($fileContent['paths'][$path]);

                        foreach ($additionalDocMethods as $method) {
                            if (!in_array($method, $methods)) {
                                $this->documentation['paths'][$path][$method] = $fileContent['paths'][$path][$method];
                            }
                        }
                    }
                }

                $definitions = array_keys($fileContent['definitions']);

                foreach ($definitions as $definition) {
                    if (empty($this->documentation['definitions'][$definition])) {
                        $this->documentation['definitions'][$definition] = $fileContent['definitions'][$definition];
                    }
                }
            }
        }
    }

    protected function limitResponseData($method = 'get', $code = 200)
    {
        $paths = array_keys($this->documentation['paths']);

        $limitResponse = config('auto-doc.max_response_count');

        foreach ($paths as $path) {
            if (!empty($limitResponse)) {
                $example = Arr::get($this->documentation['paths'][$path], "{$method}.responses.{$code}.schema.example");

                if (!empty($example['data'])) {
                    $limitedResponseData = array_slice($example['data'], 0, $limitResponse, true);
                    $this->documentation['paths'][$path][$method]['responses'][$code]['schema']['example']['data'] = $limitedResponseData;
                } elseif (!empty($example) && count($example) != count($example, COUNT_RECURSIVE)) {
                    $limitedResponseData = array_slice($example, 0, $limitResponse, true);
                    $this->documentation['paths'][$path][$method]['responses'][$code]['schema']['example'] = $limitedResponseData;
                }
            }
        }
    }

    protected function cuttingExceptions()
    {
        $paths = $this->documentation['paths'];

        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $data) {
                if(!empty($data['responses'])) {
                    foreach ($data['responses'] as $code => $data) {
                        $example = Arr::get($data, 'schema.example');

                        if (!empty($example['exception'])) {
                            $uselessKeys = array_keys(Arr::except($example, ['message']));

                            $this->documentation['paths'][$path][$method]['responses'][$code]['schema']['example'] = Arr::except($example, $uselessKeys);
                        }
                    }
                }
            }
        }
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