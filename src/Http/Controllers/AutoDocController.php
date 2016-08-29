<?php

/**
 * Created by PhpStorm.
 * User: roman
 * Date: 29.08.16
 * Time: 11:29
 */

namespace RonasIT\Support\AutoDoc\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;

class AutoDocController extends BaseController
{
    public function documentation() {
        $documentationPath = config('auto-doc.files.production');

        if (!file_exists($documentationPath)) {
            return response()->json([
                'message' => 'Documentation not exists'
            ]);
        }

        $documentation = file_get_contents($documentationPath);

        return response()->json(
            json_decode($documentation, true)
        );
    }

    public function index() {
        return view('auto-doc::documentation');
    }
}