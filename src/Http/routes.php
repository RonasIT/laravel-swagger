<?php

use RonasIT\AutoDoc\Http\Controllers\AutoDocController;
use RonasIT\AutoDoc\Http\Middleware\DisplayEnvironmentMiddleware;

Route::group([
    'prefix' => config('auto-doc.global_prefix'),
    'middleware' => DisplayEnvironmentMiddleware::class,
], function () {
    Route::get('/auto-doc/documentation', ['uses' => AutoDocController::class . '@documentation']);
    Route::get('/auto-doc/documentation/postman', ['uses' => AutoDocController::class . '@documentationForPostman']);
    Route::get('/auto-doc/{file}', ['uses' => AutoDocController::class . '@getFile']);
    Route::get(config('auto-doc.route'), ['uses' => AutoDocController::class . '@index']);
});
