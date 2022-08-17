<?php

use KWXS\Support\AutoDoc\Http\Controllers\AutoDocController;


Route::get('/swagger/documentation', ['uses' => AutoDocController::class . '@documentation']);
Route::get('/swagger/{file}', ['uses' => AutoDocController::class . '@getFile']);
Route::get(config('swagger.route'), ['uses' => AutoDocController::class . '@index']);
