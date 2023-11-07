<?php

namespace RonasIT\Support\AutoDoc\Analyzers\Diff\Compare;

use Illuminate\Support\Arr;
use RonasIT\Support\AutoDoc\Models\Model;

class ModelDiff
{
    private $increased = [];
    private $missing = [];
    private $changed = [];

    private $oldDefinitions = [];
    private $newDefinitions = [];

    public static function buildWithDefinitions(array $left, array $right): self
    {
        $instance = new ModelDiff();

        $instance->oldDefinitions = $left;
        $instance->newDefinitions = $right;

        return $instance;
    }

    public function diff(?Model $left, ?Model $right, array &$visited = []): self
    {
        if (
            (empty($left) && empty($right))
            || Arr::first($visited, function ($visited) use ($left) {
                return ($visited === $left);
            })
            || Arr::first($visited, function ($visited) use ($right) {
                return ($visited === $right);
            })
        ) {
            return $this;
        }

        $leftSchema = $left->getReference() ? $left->getReferenceSchema($this->oldDefinitions) : $left;
        $rightSchema = $right->getReference() ? $right->getReferenceSchema($this->newDefinitions) : $right;

        $propertyDiff = ArrayKeyDiff::diff($leftSchema->getData(), $rightSchema->getData());
        $this->increased = $propertyDiff->getIncreased();
        $this->missing = $propertyDiff->getMissing();

        $shared = $propertyDiff->getSharedKeys();
        foreach ($shared as $sharedKey) {
            $propertyLeft = $left->getData()[$sharedKey];
            $propertyRight = $right->getData()[$sharedKey];

            if ($sharedKey === '$ref') {
                $leftSubSchema = (new Model($propertyLeft))->getReferenceSchema($this->oldDefinitions);
                $rightSubSchema = (new Model($propertyRight))->getReferenceSchema($this->newDefinitions);

                if (!empty($leftSubSchema) || !empty($rightSubSchema)) {
                    array_push($visited, $leftSchema, $rightSchema);

                    $this->diff($leftSubSchema, $rightSubSchema, $visited);
                }
            } elseif (!empty($propertyLeft) && !empty($propertyRight) && ($propertyLeft !== $propertyRight)) {
                $this->changed[$sharedKey] = $propertyRight;
            }
        }

        return $this;
    }

    public function getIncreased(): array
    {
        return $this->increased;
    }

    public function getMissing(): array
    {
        return $this->missing;
    }

    public function getChanged(): array
    {
        return $this->changed;
    }
}