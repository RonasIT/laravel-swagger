# 3.0.1-beta

As package starting to work with new Open API specification, need to regenerate documentation file.

The base namespace of the package had also been changed, so you need to use new namespace in:

- `bootstrap\app.php` (`Http/Kernel.php` for Laravel <= 10), change namespace of
  `\RonasIT\Support\AutoDoc\Http\Middleware\AutoDocMiddleware::class` to
  `\RonasIT\AutoDoc\Http\Middleware\AutoDocMiddleware::class`;
- `tests/TestCase.php`, change namespace of `\RonasIT\Support\AutoDoc\Tests\AutoDocTestCaseTrait` to
  `\RonasIT\AutoDoc\Tests\AutoDocTestCaseTrait`;
