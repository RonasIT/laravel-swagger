<?php

namespace RonasIT\AutoDoc\Http\Middleware;

use Closure;

class DisplayEnvironmentMiddleware
{
    public function handle($request, Closure $next)
    {
        abort_unless(in_array(config('app.env'), (array) config('auto-doc.display_environments')), 404);

        return $next($request);
    }
}
