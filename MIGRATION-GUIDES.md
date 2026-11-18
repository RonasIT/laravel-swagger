# 3.4

`display_environments` now controls where the documentation exists at all:

- all package routes (the viewer page, `/auto-doc/documentation`, `/auto-doc/documentation/postman`,
  `/auto-doc/{file}`) return 404 outside of `display_environments`, the viewer page returned 403 before.
  If your project tests request these routes, add `testing` to `display_environments` in the tests;
- `AutoDocController` no longer has the `$service` property, resolve `SwaggerService` via `app()`
  if you extend the controller;
- the new `target_environments` config key restricts collecting and saving the documentation during tests
  to builds whose target environment is listed in `display_environments`.
  Set the `SWAGGER_TARGET_ENVIRONMENT` env variable (comma-separated) in your CI/CD configuration
  of the build to enable it. Without the variable the documentation is collected and saved as before.
  The key is merged from the package config, so you don't need to republish your `auto-doc.php` config;
- the documentation saved before the update stays in place: remove `documentation.json` saved by the `storage`
  driver (e.g. on the `public` disk) or pushed by the `remote` driver for the environments
  that are not listed in `display_environments`.

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