<?php

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
*/

$app = new Illuminate\Foundation\Application(
    realpath(__DIR__.'/../')
);

$env = $app->detectEnvironment(function()
{
    return getenv('APP_ENV') ?: 'local';
});
$fn = ".env.{$env}";

$app->loadEnvironmentFrom(file_exists(base_path($fn)) ? $fn : '.env');


return $app;
