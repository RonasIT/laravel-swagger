<?php

use KWXS\Support\AutoDoc\Http\Controllers\AutoDocController;


Route::get('/auto-doc/documentation', ['uses' => AutoDocController::class . '@documentation']);
Route::get('/auto-doc/{file}', ['uses' => AutoDocController::class . '@getFile']);
Route::get(config('auto-doc.route'), ['uses' => AutoDocController::class . '@index']);
