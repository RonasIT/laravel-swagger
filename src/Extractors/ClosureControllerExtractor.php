<?php

namespace RonasIT\AutoDoc\Extractors;

use Closure;
use ReflectionFunction;

class ClosureControllerExtractor extends BaseControllerExtractor
{
    public function __construct(
        protected Closure $closure,
    ) {
        parent::__construct();
    }

    protected function getResourceClass(): ?string
    {
        $code = $this->getFunctionCode(new ReflectionFunction($this->closure));

        return $this->getResourceNameFromCode($code);
    }

    protected function isResourceClass(string $className): bool
    {
        return true;
    }
}
