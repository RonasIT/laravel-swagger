<?php

use RonasIT\Support\AutoDoc\Drivers\LocalDriver;
use RonasIT\Support\AutoDoc\Drivers\RemoteDriver;
use RonasIT\Support\AutoDoc\Drivers\StorageDriver;

return [

    /*
    |--------------------------------------------------------------------------
    | Documentation Route
    |--------------------------------------------------------------------------
    |
    | Route which will return documentation
    */
    'route' => '/',

    /*
    |--------------------------------------------------------------------------
    | Info block
    |--------------------------------------------------------------------------
    |
    | Information fields
    */
    'info' => [

        /*
        |--------------------------------------------------------------------------
        | Documentation Template
        |--------------------------------------------------------------------------
        |
        | You can use your custom documentation view
        */
        'description' => 'swagger-description',
        'version' => '0.0.0',
        'title' => 'Name of Your Application',
        'termsOfService' => '',
        'contact' => [
            'email' => 'your@email.com'
        ],
        'license' => [
            'name' => '',
            'url' => ''
        ]
    ],
    'swagger' => [
        'version' => '2.0'
    ],

    /*
    |--------------------------------------------------------------------------
    | Base API path
    |--------------------------------------------------------------------------
    |
    | Base path for API routes. If config is set, all routes which starts from
    | this value will be grouped.
    */
    'basePath' => '/',
    'schemes' => [],
    'definitions' => [],

    /*
    |--------------------------------------------------------------------------
    | Security Library
    |--------------------------------------------------------------------------
    |
    | Library name, which used to secure the project.
    | Available values: "jwt", "laravel", "null"
    */
    'security' => '',
    'defaults' => [

        /*
        |--------------------------------------------------------------------------
        | Default descriptions of code statuses
        |--------------------------------------------------------------------------
        */
        'code-descriptions' => [
            '200' => 'Operation successfully done',
            '204' => 'Operation successfully done',
            '404' => 'This entity not found'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Driver
    |--------------------------------------------------------------------------
    |
    | The name of driver, which will collect and save documentation
    | Feel free to use your own driver class which should be inherited from
    | `RonasIT\Support\AutoDoc\Interfaces\SwaggerDriverInterface` interface,
    | or one of our drivers from the `drivers` config:
    */
    'driver' => env('SWAGGER_DRIVER', 'local'),

    'drivers' => [
        'local' => [
            'class' => LocalDriver::class,
            'production_path' => storage_path('documentation.json')
        ],
        'remote' => [
            'class' => RemoteDriver::class,
            'key' => env('SWAGGER_REMOTE_DRIVER_KEY', 'project_name'),
            'url' => env('SWAGGER_REMOTE_DRIVER_URL', 'https://example.com')
        ],
        'storage' => [
            'class' => StorageDriver::class,

            /*
            |--------------------------------------------------------------------------
            | Storage disk
            |--------------------------------------------------------------------------
            |
            | One of the filesystems.disks config value
            */
            'disk' => env('SWAGGER_STORAGE_DRIVER_DISK', 'public'),
            'production_path' => 'documentation.json'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Swagger documentation visibility environments list
    |--------------------------------------------------------------------------
    |
    | The list of environments in which auto documentation will be displaying
    */
    'display_environments' => [
        'local',
        'development'
    ],

    'config_version' => '2.1'
];
