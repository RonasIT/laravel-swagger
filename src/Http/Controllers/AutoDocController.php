<?php

namespace RonasIT\AutoDoc\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use RonasIT\AutoDoc\Services\SwaggerService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AutoDocController extends BaseController
{
    protected $documentationViewer;

    public function __construct()
    {
        $this->documentationViewer = config('auto-doc.documentation_viewer');
    }

    public function documentationForPostman()
    {
        $documentation = app(SwaggerService::class)->getDocFileContent();

        return response()->json($documentation);
    }

    public function documentation()
    {
        $documentation = app(SwaggerService::class)->getPrettyDocFileContent();

        return response()->json($documentation);
    }

    public function index()
    {
        return view("auto-doc::documentation-{$this->documentationViewer}");
    }

    public function getFile(Request $request, $file)
    {
        $filePath = __DIR__ . "/../../../resources/assets/{$this->documentationViewer}/" . $file;

        if (!file_exists($filePath)) {
            throw new NotFoundHttpException();
        }

        $content = file_get_contents($filePath);

        return response($content)->header('Content-Type', $request->getAcceptableContentTypes());
    }
}
