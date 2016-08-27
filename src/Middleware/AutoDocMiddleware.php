<?php

/**
 * Created by PhpStorm.
 * User: roman
 * Date: 26.08.16
 * Time: 11:49
 */

namespace RonasIT\Support\AutoDoc\Middleware;

use Closure;
use RonasIT\Support\AutoDoc\Services\SwaggerService;

/**
 * @property SwaggerService $service
*/
class AutoDocMiddleware
{
    protected $service;

    public function __construct()
    {
        $this->service = app(SwaggerService::class);
    }

    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if (!config('auto-doc.enabled')) {
            return $response;
        }

        $this->service->addData($request, $response);

        return $response;
    }
}