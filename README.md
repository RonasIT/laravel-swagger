<img src="resources/assets/images/hero.svg" >

# Laravel Swagger plugin

<p align="left">
<a href="https://packagist.org/packages/ronasit/laravel-swagger"><img src="https://img.shields.io/packagist/dt/ronasit/laravel-swagger" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/ronasit/laravel-swagger"><img src="https://img.shields.io/packagist/v/ronasit/laravel-swagger" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/ronasit/laravel-swagger"><img src="https://img.shields.io/packagist/l/ronasit/laravel-swagger" alt="License"></a>
</p>

[![Laravel Swagger](https://github.com/RonasIT/laravel-swagger/actions/workflows/laravel.yml/badge.svg?branch=master)](https://github.com/RonasIT/laravel-swagger/actions/workflows/laravel.yml)
[![Coverage Status](https://coveralls.io/repos/github/RonasIT/laravel-swagger/badge.svg?branch=master)](https://coveralls.io/github/RonasIT/laravel-swagger?branch=master)

## Comparison to another documentation generators

|                                                    | LaravelSwagger         | [Scramble](https://github.com/dedoc/scramble) |
|----------------------------------------------------|------------------------|----------------------------------------------|
| Force developers to write tests                    | :white_check_mark:     | :x:                                          |
| Guarantee that API works                           | :white_check_mark:     | :x:                                          |
| Works with any route types covered by tests        | :white_check_mark:     | :x:                                          |
| Generate response schema using JSON Resource class | :x: | :white_check_mark:                             |
| Runtime documentation generation                   | :x:                    | :white_check_mark:                        |

## Introduction

This plugin is designed to generate documentation for your REST API during the 
passing PHPUnit tests.

## Installation

1. Install the package using the following command:

```sh
composer require ronasit/laravel-swagger
```

> ***Note***
> 
> For Laravel 5.5 or later the package will be auto-discovered.
> For older versions add the `AutoDocServiceProvider` to the
> providers array in `config/app.php` as follow:
> 
> ```php
> 'providers' => [
>     ...
>     RonasIT\AutoDoc\AutoDocServiceProvider::class,
> ],
> ```

2. Run

```
php artisan vendor:publish --provider=RonasIT\\AutoDoc\\AutoDocServiceProvider
```

3. Add `\RonasIT\AutoDoc\Http\Middleware\AutoDocMiddleware::class` middleware to the global HTTP middleware list `bootstrap\app.php`:

```php
    return Application::configure(basePath: dirname(__DIR__))
        ->withMiddleware(function (Middleware $middleware) {
            $middleware->use([
                //...
                \RonasIT\AutoDoc\Http\Middleware\AutoDocMiddleware::class,
            ]);
        });
```

4. Add `\RonasIT\AutoDoc\Traits\AutoDocTestCaseTrait` trait to `tests/TestCase.php`

5. Configure documentation saving using one of the next ways:
  - Add `SwaggerExtension` to the `<extensions>` block of your `phpunit.xml`.

  ```xml
  <phpunit>
      //...
      <extensions>
          <bootstrap class="RonasIT\AutoDoc\Support\PHPUnit\Extensions\SwaggerExtension"/>
      </extensions>
  </phpunit>
  ```
  - Call `php artisan swagger:push-documentation` console command after
    the `tests` stage.

## Usage

### Basic usage

1. Create a request class:

    ```php
    <?php

    namespace App\Http\Requests;  
    
    use Illuminate\Foundation\Http\FormRequest;
    
    /**
    * @summary Update user
    *
    * @deprecated 
    *
    * @description
    * This request should be used for updating the user data
    *
    * @_204 Successful
    * 
    * @is_active will indicate whether the user is active or not
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
         * Validation Rules
         *
         * @return array
         */
        public function rules()
        {
            return [
                'name' => 'string',
                'is_active' => 'boolean',
                'age' => 'integer|nullable'
            ];
        }
    }

    ```
    > ***Note***
    > 
    > For correct working of plugin you'll have to dispose all the validation rules 
    > in the `rules()` method of your request class. Also, your request class
    > must be connected to the controller via [dependency injection](https://laravel.com/docs/11.x/container#introduction).
    > Plugin will take validation rules from the request class and generate fields description
    > of input parameter.

1. Implement request handling in the corresponding controller class:

    ```php
    <?php

    namespace App\Http\Controllers;

    use App\Http\Requests\Users\UpdateUserDataRequest;

    class UserController extends Controller
    {
        public function update(UpdateUserDataRequest $request, UserService $service, $id)
        {
            // do something here...

            return response('', Response::HTTP_NO_CONTENT);
        }
    }
    ```

    > ***Note***
    > 
    > Dependency injection of the request class is optional, but if it isn't present, 
    > the 'Parameters' block in the API documentation will be empty.

3. Create a test for the API endpoint:

    ```php
    public function testUpdate()
    {
        $response = $this->json('put', '/users/1', [
            'name': 'Updated User',
            'is_active': true,
            'age': 22
        ]);

        $response->assertStatus(Response::HTTP_NO_CONTENT);
    }
    ```

4. Run the tests
5. Go to the route which is defined in the `auto-doc.route` config
6. Profit!

    ![img.png](resources/assets/images/img.png)

### Annotations

You can use the following annotations in the corresponding request class to customize documentation of the API endpoint:

- **@summary** - short description of request
- **@description** - implementation notes
- **@_204** - custom description of response code. You can specify any code as you want.
- **@some_field** - description of the field from the rules method
- **@deprecated** - mark route as deprecated
 
> ***Note***
> 
> If you do not use request class, the summary and description and parameters will be empty.

### Custom driver

You can specify the way to collect and view documentation by creating your own custom driver.

You can find example of drivers [here](https://github.com/RonasIT/laravel-swagger/tree/master/src/Drivers).

### Viewing OpenAPI documentation

As of version 2.2, the package includes the ability to switch between OpenAPI documentation
viewers. To access different viewers, modify the `documentation_viewer` configuration or set the viewer using
the `SWAGGER_SPEC_VIEWER` env. This change is reflected immediately, without the need to rebuild the documentation file.

### Merging additional documentations

The package supports the integration of the primary documentation with additional valid
OpenAPI files specified in the `additional_paths` configuration.

## Migration guides

[3.0.1-beta](MIGRATION-GUIDES.md#301-beta)

## Contributing

Thank you for considering contributing to Laravel Swagger plugin! The contribution guide
can be found in the [Contributing guide](CONTRIBUTING.md).

## License

Laravel Swagger plugin is open-sourced software licensed under the [MIT license](LICENSE).
 
