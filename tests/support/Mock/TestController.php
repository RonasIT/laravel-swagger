<?php

namespace RonasIT\AutoDoc\Tests\Support\Mock;

class TestController
{
    public function test(TestRequest $request)
    {
    }

    public function testRequestWithoutRuleType(TestRequestWithoutRuleType $request)
    {
    }

    public function testRequestWithAnnotations(TestRequestWithAnnotations $request)
    {
    }

    public function testRequestWithContract(TestContract $contract, string $param)
    {
    }
}
