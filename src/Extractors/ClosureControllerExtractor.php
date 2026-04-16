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
        return $this->getResourceFromCode(new ReflectionFunction($this->closure));
    }
}
