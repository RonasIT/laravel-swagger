<?php

namespace RonasIT\AutoDoc\Tests\Support\Traits;

use Closure;
use Illuminate\Support\Arr;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;

trait MockTrait
{
    use PHPMock;

    protected function mockClass($className, $methods = [])
    {
        return $this
            ->getMockBuilder($className)
            ->onlyMethods($methods)
            ->getMock();
    }

    /**
     * Mock native function. Call chain should looks like:
     *
     * [
     *     [
     *         'function' => 'function_name',
     *         'arguments' => ['firstArgumentValue', 2, true],
     *         'result' => '123'
     *     ],
     *     $this->functionCall('function_name', ['firstArgumentValue', 2, true], '123')
     * ]
     *
     * @param string $namespace
     * @param array $callChain
     */
    public function mockNativeFunction(string $namespace, array $callChain): void
    {
        $methodsCalls = collect($callChain)->groupBy('function');

        $methodsCalls->each(function ($calls, $function) use ($namespace) {
            $matcher = $this->exactly($calls->count());

            $mock = $this->getFunctionMock($namespace, $function);

            $mock
                ->expects($matcher)
                ->willReturnCallback(function (...$args) use ($matcher, $calls, $namespace, $function) {
                    $callIndex = $this->getInvocationCount($matcher) - 1;
                    $expectedCall = $calls[$callIndex];

                    $expectedArguments = Arr::get($expectedCall, 'arguments');

                    if (!empty($expectedArguments)) {
                        $this->assertArguments(
                            $args,
                            $expectedArguments,
                            $namespace,
                            $function,
                            $callIndex,
                            false
                        );
                    }

                    return $expectedCall['result'];
                });
        });
    }

    protected function assertArguments(
        $actual,
        $expected,
        string $class,
        string $function,
        int $callIndex,
        bool $isClass = true
    ): void {
        $message = ($isClass)
            ? "Class '{$class}'\nMethod: '{$function}'\nMethod call index: {$callIndex}"
            : "Namespace '{$class}'\nFunction: '{$function}'\nCall index: {$callIndex}";

        foreach ($actual as $index => $argument) {
            $this->assertEquals(
                $expected[$index],
                $argument,
                "Failed asserting that arguments are equals to expected.\n{$message}\nArgument index: {$index}"
            );
        }
    }

    public function functionCall(string $name, array $arguments = [], $result = true): array
    {
        return [
            'function' => $name,
            'arguments' => $arguments,
            'result' => $result,
        ];
    }

    protected function getInvocationCount(InvokedCount $matcher): int
    {
        return method_exists($matcher, 'getInvocationCount')
            ? $matcher->getInvocationCount()
            : $matcher->numberOfInvocations();
    }
}
