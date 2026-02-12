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

    public function setResource(): ?string
    {
        $code = $this->getFunctionCode(new ReflectionFunction($this->closure));

        $resource = $this->getResourceNameFromCode($code);

        return (!empty($resource)) ? $this->extractClassName($resource) : null;
    }
}
