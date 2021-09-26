# Laravel AutoDoc plugin

The fork of the https://github.com/RonasIT/laravel-swagger repo.

## Added

- support of minime/annotations >= 3.0

This plugin is designed to gather information and generate documentation about
your Rest-Api while passing the tests. The principle of operation is based on
the fact that the special Middleware installed on the Route for which you want
to collect information that after the successful completion of all tests
generated Swagger-file. In addition this plug-in is able to draw Swagger-template
to display the generated documentation for a config.

## Installation

### Composer

1.  `composer require shirokovnv/laravel-swagger`

### Laravel

1.  add `RonasIT\Support\AutoDoc\AutoDocServiceProvider::class,` to providers in `config/app.php`
1.  run `php artisan vendor:publish`

### Plugin

1.  Add middleware **\RonasIT\Support\AutoDoc\Http\Middleware\AutoDocMiddleware::class** to _Http/Kernel.php_.
1.  Use **\RonasIT\Support\AutoDoc\Tests\AutoDocTestCaseTrait** in your TestCase in _tests/TestCase.php_
1.  In _config/auto-doc.php_ you can specify enabling of plugin, info of your project,
    some defaults descriptions and route for rendering of documentation.
1.  In _.env_ file you should add following lines
    `LOCAL_DATA_COLLECTOR_PROD_PATH=/example-folder/documentation.json LOCAL_DATA_COLLECTOR_TEMP_PATH=/tmp/documentation.json`

## Usages

For correct working of plugin you have to dispose all the validation rules in the rules() method of class YourRequest,
which must be connected to the controller via DependencyInjection. In annotation of custom request you can specify
summary and description of this request. Plugin will take validation rules from your request and use it as description
of input parameter.

### Example

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @summary Updating of user
 *
 * @description
 *  This request mostly needed to specity flags <strong>free_comparison</strong> and
 *  <strong>all_cities_available</strong> of user
 *
 * @_204 Successful MF!
 */
class UpdateUserDataRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'all_cities_available' => 'boolean',
            'free_comparison' => 'boolean'
        ];
    }
}

```

- **@summary** - short description of request
- **@description** - Implementation Notes
- **@\_204** - Custom description of code of response. You can specify any code as you want.

If you do not create a class Request, the summary, Implementation Notes and parameters will be empty.
Plugin will collect codes and examples of responses only.

If you do not create annotations to request summary will generate automatically from Name of Request.
For example request **UpdateUserDataRequest** will have summary **Update user data request**.

If you do not create annotations for descriptions of codes it will be generated automatically the following priorities:

1.  Annotations of request
2.  Default description from _auto-doc.defaults.code-descriptions.{$code}_
3.  Descriptions from **Symfony\Component\HttpFoundation\Response::$statusTexts**

Note about configs:

- _auto-doc.route_ - it's a route where will be located generated documentation
- _auto-doc.basePath_ - it's a route where located root of your api

Also you can specify way to collect documentation by creating your custom data collector class.
