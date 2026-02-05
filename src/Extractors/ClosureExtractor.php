<?php

namespace RonasIT\AutoDoc\Extractors;

use Closure;
use ReflectionFunction;

class ClosureExtractor extends BaseExtractor
{
    public function __construct(Closure $closure)
    {
        $this->reflectionFunction = new ReflectionFunction($closure);
    }

    public function getResource(): ?string
    {
        $code = $this->getFunctionCode();

        return $this->getResourceNameFromCode($code);
    }
}
