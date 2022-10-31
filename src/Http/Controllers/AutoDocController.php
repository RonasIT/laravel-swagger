<?php

namespace RonasIT\Support\AutoDoc\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use RonasIT\Support\AutoDoc\Services\SwaggerService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Support\Arr;

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
        $this->documentation = $this->service->getDocFileContent();

        $this->limitResponseData();

        $this->cutExceptions();

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

    protected function limitResponseData()
    {
        $paths = array_keys($this->documentation['paths']);

        $responseExampleLimitCount = config('auto-doc.response_example_limit_count');

        if (!empty($responseExampleLimitCount)) {
            foreach ($paths as $path) {
                $example = Arr::get($this->documentation['paths'][$path], 'get.responses.200.schema.example');

                if (!empty($example['data'])) {
                    $limitedResponseData = array_slice($example['data'], 0, $responseExampleLimitCount, true);
                    $this->documentation['paths'][$path]['get']['responses'][200]['schema']['example']['data'] = $limitedResponseData;
                }
            }
        }
    }

    protected function cutExceptions()
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
}