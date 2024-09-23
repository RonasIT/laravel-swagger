<?php

namespace RonasIT\AutoDoc\Http\Middleware;

use Closure;
use RonasIT\AutoDoc\Services\SwaggerService;

/**
 * @property SwaggerService $service
 */
class AutoDocMiddleware
{
    public static $skipped = false;

    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if ((config('app.env') === 'testing') && !self::$skipped && !empty($request->route())) {
            app(SwaggerService::class)->addData($request, $response);
        }

        self::$skipped = false;

        return $response;
    }
}
