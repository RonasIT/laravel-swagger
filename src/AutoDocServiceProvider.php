<?php

namespace KWXS\Support\AutoDoc;

use Illuminate\Support\ServiceProvider;

class AutoDocServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/auto-doc.php' => config_path('auto-doc.php'),
        ], 'config');

        $this->publishes([
            __DIR__ . '/../config/local-data-collector.php' => config_path('local-data-collector.php'),
        ], 'config');

        $this->publishes([
            __DIR__ . '/Views/swagger-description.blade.php' => resource_path('views/swagger-description.blade.php'),
        ], 'view');

        if (!$this->app->routesAreCached()) {
            require __DIR__ . '/Http/routes.php';
        }

        $this->loadViewsFrom(__DIR__ . '/Views', 'auto-doc');
    }

    public function register()
    {

    }
}
