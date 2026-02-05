<?php

namespace RonasIT\AutoDoc\Extractors;

use Closure;
use ReflectionFunction;

class ClosureExtractor extends BaseExtractor
{
    public function __construct(
        protected Closure $closure,
    ) {
    }

    public function getResource(): ?string
    {
        $code = $this->getFunctionCode(new ReflectionFunction($this->closure));

        $resource = $this->getResourceNameFromCode($code);

        return (!empty($resource)) ? $this->extractClassName($resource) : null;
    }
}
