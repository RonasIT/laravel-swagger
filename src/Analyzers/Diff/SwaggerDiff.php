<?php

namespace RonasIT\Support\AutoDoc\Analyzers\Diff;

use RonasIT\Support\AutoDoc\Analyzers\Diff\Compare\SpecificationDiff;
use RonasIT\Support\AutoDoc\Models\Specification;

class SwaggerDiff
{
    protected $oldSpecSwagger;
    protected $newSpecSwagger;

    protected $missingEndpoints;
    protected $newEndpoints;
    protected $changedEndpoints;

    public function __construct(array $oldSpecSwagger, array $newSpecSwagger)
    {
        $this->oldSpecSwagger = $oldSpecSwagger;
        $this->newSpecSwagger = $newSpecSwagger;
    }

    public function compare(): SwaggerDiff
    {
        $diff = SpecificationDiff::diff(new Specification($this->oldSpecSwagger), new Specification($this->newSpecSwagger));

        $this->missingEndpoints = $diff->getMissingEndpoints();
        $this->newEndpoints = $diff->getNewEndpoints();
        $this->changedEndpoints = $diff->getChangedEndpoints();

        return $this;
    }

    public function getMissingEndpoints()
    {
        return $this->missingEndpoints;
    }

    public function getNewEndpoints()
    {
        return $this->newEndpoints;
    }

    public function getChangedEndpoints()
    {
        return $this->changedEndpoints;
    }
}