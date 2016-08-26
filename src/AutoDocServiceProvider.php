<?php

/**
 * Created by PhpStorm.
 * User: roman
 * Date: 26.08.16
 * Time: 11:49
 */

namespace RonasIT\Support\AutoDoc;

use Illuminate\Support\ServiceProvider;

class AutoDocServiceProvider extends ServiceProvider
{
    public function boot() {
        $this->publishes([
            __DIR__.'/../config/auto-doc.php' => config_path('auto-doc.php'),
        ], 'config');
    }

    public function register()
    {

    }
}