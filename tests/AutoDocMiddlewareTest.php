<?php

namespace RonasIT\Support\Tests;

use RonasIT\Support\AutoDoc\Http\Middleware\AutoDocMiddleware;
use Symfony\Component\HttpFoundation\Response;
use RonasIT\Support\Tests\Support\Traits\SwaggerServiceMockTrait;

class AutoDocMiddlewareTest extends TestCase
{
    use SwaggerServiceMockTrait;

    public function testHandle()
    {
        config(['auto-doc.security' => 'laravel']);

        $this->mockDriverSaveTmpData($this->getJsonFixture('tmp_data_auto_doc_middleware'));

        $request = $this->generateRequest('get', 'users/roles', [
            'with' => ['users']
        ]);

        $middleware  = new AutoDocMiddleware();

        $middleware->handle($request, function () {
            return new Response($this->getFixture('example_success_auto_doc_middleware.json'), 200, [
                'Content-type' => 'application/json',
                'authorization' => 'Bearer some_token'
            ]);
        });
    }
}
