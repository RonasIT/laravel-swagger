<?php

namespace RonasIT\Support\AutoDoc\Analyzers\Diff\Compare;

class ArrayKeyDiff
{
    private $increased = [];
    private $missing = [];
    private $sharedKeys = [];

    public static function diff(array $left, array $right): self
    {
        $instance = new ArrayKeyDiff();

        $instance->increased = $right;

        foreach ($left as $leftKey => $leftValue) {
            if (array_key_exists($leftKey, $right)) {
                unset($instance->increased[$leftKey]);
                $instance->sharedKeys[] = $leftKey;
            } else {
                $instance->missing[$leftKey] = $leftValue;
            }
        }

        return $instance;
    }

    public function getMissing(): array
    {
        return $this->missing;
    }

    public function getIncreased(): array
    {
        return $this->increased;
    }

    public function getSharedKeys(): array
    {
        return $this->sharedKeys;
    }
}