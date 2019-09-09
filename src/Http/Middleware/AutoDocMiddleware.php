<?php

namespace Gluck1986\Support\AutoDoc\Http\Middleware;

use App\Http\Requests\ApplicationRequest;
use Closure;
use Gluck1986\Support\AutoDoc\Services\SwaggerService;

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

        if ((config('app.env') == 'testing') && !self::$skipped) {
            if (!$request->route()) {
                return $response;
            }
            $this->service->addData($request, $response);
        }

        self::$skipped = false;

        return $response;
    }
}
