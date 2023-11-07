<?php

namespace RonasIT\Support\AutoDoc\Analyzers\Diff\Compare;

use Closure;

class ArrayDiff
{
    private $increased = [];
    private $missing = [];
    private $shared = [];

    public static function diff(array $left, array $right, Closure $biFunc): self
    {
        $instance = new ArrayDiff();

        $instance->increased = $right;

        foreach ($left as $leftValue) {
            $rightValue = $biFunc($right, $leftValue);

            if (is_null($rightValue)) {
                $instance->missing[] = $leftValue;
            } else {
                foreach ($instance->increased as $index => $increasedValue) {
                    if (!is_null($biFunc([$increasedValue], $leftValue))) {
                        unset($instance->increased[$index]);
                        break;
                    }
                }

                $instance->shared[] = $rightValue;
            }
        }

        return $instance;
    }

    public function getIncreased(): array
    {
        return $this->increased;
    }

    public function getMissing(): array
    {
        return $this->missing;
    }

    public function getShared(): array
    {
        return $this->shared;
    }
}