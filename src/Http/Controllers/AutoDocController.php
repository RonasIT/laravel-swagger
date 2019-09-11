<?php

namespace RonasIT\Support\AutoDoc\Http\Controllers;

use finfo;
use Illuminate\Routing\Controller as BaseController;
use RonasIT\Support\AutoDoc\Services\SwaggerService;
use SplFileObject;
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
        return view('auto-doc::documentation');
    }

    public function getFile($file)
    {
        $dir = dirname(dirname(__DIR__));
        $pathParts = [$dir, 'Views', 'swagger', $file];
        $filePath = implode(DIRECTORY_SEPARATOR, $pathParts);

        if (!file_exists($filePath)) {
            throw new NotFoundHttpException();
        }
        $file = new SplFileObject($filePath);
        $info = $file->getFileInfo();
        $len = $info->getSize();
        $content = $file->fread($len);
        $mime_type = $this->getMIME($filePath);

        return response($content)->withHeaders(['content-type' => $mime_type, 'Content-Length' => $len]);
    }

    private function getMIME($fileName): string
    {
        $ext = strtolower(pathinfo($fileName)['extension'] ?? '');
        if (strtolower($ext) === 'css') {
            return 'text/css';
        } elseif (strtolower($ext) === 'js') {
            return 'application/x-javascript';
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);

        return $finfo->file($fileName);
    }
}
