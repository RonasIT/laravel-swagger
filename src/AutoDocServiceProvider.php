<?php

namespace KWXS\Support\AutoDoc;

use Illuminate\Support\ServiceProvider;

class AutoDocServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/swagger.php' => config_path('swagger.php'),
        ], 'config');

        $this->publishes([
            __DIR__ . '/Views/swagger-description.blade.php' => resource_path('views/swagger-description.blade.php'),
        ], 'view');

        if (!$this->app->routesAreCached()) {
            require __DIR__ . '/Http/routes.php';
        }

        $this->loadViewsFrom(__DIR__ . '/Views', 'swagger');
    }

    public function register()
    {

    }
}
