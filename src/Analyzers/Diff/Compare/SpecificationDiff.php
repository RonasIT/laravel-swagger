<?php

namespace RonasIT\Support\AutoDoc\Analyzers\Diff\Compare;

use RonasIT\Support\AutoDoc\Analyzers\Diff\Models\ChangedEndpoint;
use RonasIT\Support\AutoDoc\Models\Specification;

class SpecificationDiff
{
    private $newEndpoints = [];
    private $missingEndpoints = [];
    private $changedEndpoints = [];

    public static function diff(Specification $left, Specification $right): self
    {
        $instance = new SpecificationDiff();

        $pathDiff = ArrayKeyDiff::diff($left->getPaths(), $right->getPaths());
        $instance->newEndpoints = $pathDiff->getIncreased();
        $instance->missingEndpoints = $pathDiff->getMissing();
        $sharedKeys = $pathDiff->getSharedKeys();

        foreach ($sharedKeys as $pathUrl) {
            $changedEndpoint = new ChangedEndpoint();
            $changedEndpoint->setPathUrl($pathUrl);

            $oldPath = $left->getPaths()[$pathUrl];
            $newPath = $right->getPaths()[$pathUrl];


        }

        return $instance;
    }

    public function getChangedEndpoints(): array
    {
        return $this->changedEndpoints;
    }

    public function getMissingEndpoints(): array
    {
        return $this->missingEndpoints;
    }

    public function getNewEndpoints(): array
    {
        return $this->newEndpoints;
    }
}