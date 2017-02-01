<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 26.08.16
 * Time: 11:49
 */

return [
    'enabled' => env('AUTODOC_ENABLED'),
    'files' => [
        'production' => env('SWAGGER_FILEPATH_PRODUCTION'),
        'temporary' => env('SWAGGER_FILEPATH_TEMP'),
    ],
    'route' => '/',
    'info' => [
        'description' => 'Description of your application',
        'version' => '0.0.0',
        'title' => 'Name of Your Application',
        'termsOfService' => '',
        'contacts' => [
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
    'basePath' => '/',
    'schemes' => [],
    'definitions' => [],
    'security' => [], //possible values : jwt, laravel, oauth
    'defaults' => [
        'code-descriptions' => [
            '200' => 'Operation successfully done',
            '204' => 'Operation successfully done',
            '404' => 'This entity not found'
        ]
    ]
];
