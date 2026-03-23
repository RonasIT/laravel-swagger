<?php

namespace RonasIT\AutoDoc\Extractors;

use ReflectionException;
use ReflectionMethod;

class ClassControllerExtractor extends BaseControllerExtractor
{
    public function __construct(
        protected string $class,
        protected string $method,
    ) {
        parent::__construct();
    }

    protected function getResourceClass(): ?string
    {
        try {
            $reflectionMethod = ReflectionMethod::createFromMethodName("{$this->class}::{$this->method}");
        } catch (ReflectionException) {
            return null;
        }

        $resourceClass = $reflectionMethod->getReturnType()?->getName();

        return (is_null($resourceClass)) ? $this->getResourceFromCode($reflectionMethod) : $resourceClass;
    }
}
