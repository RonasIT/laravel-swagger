<?php

/**
 * Created by PhpStorm.
 * User: roman
 * Date: 26.08.16
 * Time: 11:49
 */
class AutoDocServiceProvider
{
    public function handle($request, Closure $next)
    {
        return $next($request);
    }
}