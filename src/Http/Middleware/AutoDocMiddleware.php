<?php

namespace RonasIT\Support\AutoDoc\Http\Middleware;

use Closure;
use RonasIT\Support\AutoDoc\Services\SwaggerService;

/**
 * @property SwaggerService $service
 */
class AutoDocMiddleware
{
    protected $service;
    public static $skipped = false;

    public function __construct()
    {
        $this->service = app(SwaggerService::class);
    }

    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if ((config('app.env') == config('auto-doc.testing_env')) && !self::$skipped) {
            $this->service->addData($request, $response);
        }

        self::$skipped = false;

        return $response;
    }
}
