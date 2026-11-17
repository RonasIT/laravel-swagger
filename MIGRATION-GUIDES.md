# 3.4

The config version had been increased, so you need to update your `auto-doc.php` config file:

- add the `target_environments` key from the package config;
- set `config_version` to `3.1`.

`display_environments` now controls where the documentation exists at all:

- all package routes (the viewer page, `/auto-doc/documentation`, `/auto-doc/documentation/postman`,
  `/auto-doc/{file}`) return 404 outside of `display_environments`, the viewer page returned 403 before;
- the documentation file is saved only if one of the build target environments is listed in `display_environments`.
  Set the `SWAGGER_TARGET_ENVIRONMENT` env variable (comma-separated) in your CI/CD configuration
  of the build, it is `development` by default.

# 3.0.1-beta

As package starting to work with new Open API specification, need to regenerate documentation file.

The base namespace of the package had also been changed, so you need to use new namespace in:

- `bootstrap\app.php` (`Http/Kernel.php` for Laravel <= 10), change namespace of
  `\RonasIT\Support\AutoDoc\Http\Middleware\AutoDocMiddleware` to
  `\RonasIT\AutoDoc\Http\Middleware\AutoDocMiddleware`;
- `tests/TestCase.php`, change namespace of `\RonasIT\Support\AutoDoc\Tests\AutoDocTestCaseTrait` to
  `\RonasIT\AutoDoc\Traits\AutoDocTestCaseTrait`;
- `phpunit.xml`, change namespace of extension from `RonasIT\Support\AutoDoc\Tests\PhpUnitExtensions\SwaggerExtension` to
  `RonasIT\AutoDoc\Support\PHPUnit\Extensions\SwaggerExtension`;
- your custom documentation drivers, change interface from `RonasIT\AutoDoc\Interfaces\SwaggerDriverInterface` to
  `RonasIT\AutoDoc\Contracts\SwaggerDriverContract`.
- if you have published views of the package, you need to update the following file:
  `resources/views/vendor/auto-doc/error.blade.php`