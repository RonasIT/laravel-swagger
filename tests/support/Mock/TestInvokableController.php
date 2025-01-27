<?php

namespace RonasIT\AutoDoc\Tests\Support\Mock;

class TestInvokableController
{
    public function __invoke(TestEmptyRequest $request)
    {
    }
}
