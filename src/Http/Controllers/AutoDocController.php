<?php

namespace RonasIT\Support\AutoDoc\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use RonasIT\Support\AutoDoc\Services\SwaggerService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AutoDocController extends BaseController
{
    protected SwaggerService $service;
    protected string $documentationViewer;

    public function __construct()
    {
        $this->service = app(SwaggerService::class);
        $this->documentationViewer = config('auto-doc.documentation_viewer');
    }

    public function documentation(): JsonResponse
    {
        $documentation = $this->service->getDocFileContent();

        return response()->json($documentation);
    }

    public function index(): View|Response
    {
        $currentEnvironment = config('app.env');

        if (in_array($currentEnvironment, config('auto-doc.display_environments'))) {
            return view("auto-doc::documentation-{$this->documentationViewer}");
        }

        return response('Forbidden.', 403);
    }

    public function getFile(Request $request, $file): Response
    {
        $filePath = __DIR__ . "/../../../resources/assets/{$this->documentationViewer}/" . $file;

        if (!file_exists($filePath)) {
            throw new NotFoundHttpException();
        }

        $content = file_get_contents($filePath);

        return response($content)->header('Content-Type', $request->getAcceptableContentTypes());
    }
}
