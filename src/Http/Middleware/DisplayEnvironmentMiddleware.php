<?php

namespace RonasIT\AutoDoc\Http\Middleware;

use Closure;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DisplayEnvironmentMiddleware
{
    public function handle($request, Closure $next)
    {
        if (!in_array(config('app.env'), config('auto-doc.display_environments'))) {
            throw new NotFoundHttpException();
        }

        return $next($request);
    }
}
