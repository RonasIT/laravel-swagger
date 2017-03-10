<?php

/**
 * Created by PhpStorm.
 * User: roman
 * Date: 29.08.16
 * Time: 11:29
 */

namespace RonasIT\Support\AutoDoc\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use RonasIT\Support\AutoDoc\Services\SwaggerService;

class AutoDocController extends BaseController
{
    protected $service;

    public function __construct()
    {
        $this->service = app(SwaggerService::class);
    }

    public function documentation() {

        $documentation = $this->service->getDocFileContent();

        return response()->json($documentation);
    }

    public function index() {
        return view('auto-doc::documentation');
    }
}