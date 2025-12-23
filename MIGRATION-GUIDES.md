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