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
    'route' => '',

    /*
    |--------------------------------------------------------------------------
    | Global application prefix
    |--------------------------------------------------------------------------
    |
    | Usefully in case the webserver using a path to route requests to the app
    | In case your app available at https://some.domain.com/service - use
    | /service as global prefix config
    */
    'global_prefix' => env('SWAGGER_GLOBAL_PREFIX', ''),

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
        | You can use your custom documentation view.
        */
        'description' => 'auto-doc::swagger-description',
        'version' => '0.0.0',
        'title' => env('APP_NAME', 'Name of Your Application'),
        'termsOfService' => '',
        'contact' => [
            'email' => null
        ],
        'license' => [
            'name' => '',
            'url' => ''
        ]
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

    /*
    |--------------------------------------------------------------------------
    | OpenAPI Spec viewer
    |--------------------------------------------------------------------------
    |
    | Tool for rendering API documentation in HTML format.
    | Available values: "swagger", "elements", "rapidoc"
    */
    'documentation_viewer' => env('SWAGGER_SPEC_VIEWER', 'swagger'),

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
    | Paths to additional documentation
    |--------------------------------------------------------------------------
    | An array of documentation paths to merge with the main documentation.
    |
    | For example, if your additional documentation is located in storage/additional_docs/example.json
    | you need to add 'storage/additional_docs/example.json' to additional_paths, if your additional
    | documentation is located in the root directory of your project you need to add the filename to additional_paths
    */
    'additional_paths' => [],

    /*
    |--------------------------------------------------------------------------
    | Response example array items limit
    |--------------------------------------------------------------------------
    | All array responses will be automatically cut for the config items count
    |
    | Note: you should collect documentation after the config change
    */
    'response_example_limit_count' => 5,

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

    'config_version' => '2.6'
];
