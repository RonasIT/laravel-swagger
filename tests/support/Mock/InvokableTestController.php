<?php

namespace RonasIT\AutoDoc\Tests\Support\Mock;

class InvokableTestController
{
    public function __invoke(TestRequestContract $someVar)
    {
    }
}
