<?php

namespace RonasIT\Support\AutoDoc\Analyzers\Diff\Compare;

use Illuminate\Support\Arr;
use RonasIT\Support\AutoDoc\Analyzers\Diff\Models\ChangedParameter;
use RonasIT\Support\AutoDoc\Models\Model;
use RonasIT\Support\AutoDoc\Models\Parameter;

/**
 * @property Parameter[] $increased
 * @property Parameter[] $missing
 * @property ChangedParameter[] $changed
 * @property array<string, Model> $oldDefinitions
 * @property array<string, Model> $newDefinitions
 */
class ParameterDiff
{
    private $increased = [];
    private $missing = [];
    private $changed = [];

    private $oldDefinitions = [];
    private $newDefinitions = [];

    public static function buildWithDefinitions(array $left, array $right): self
    {
        $instance = new ParameterDiff();

        $instance->oldDefinitions = $left;
        $instance->newDefinitions = $right;

        return $instance;
    }

    /**
     * @param Parameter[] $left
     * @param Parameter[] $right
     * @return $this
     */
    public function diff(array $left, array $right): self
    {
        $paramDiff = ArrayDiff::diff($left, $right, function (array $right, Parameter $leftParam) {
            foreach ($right as $rightParam) {
                if (
                    ($leftParam->getName() === $rightParam->getName())
                    && ($leftParam->getIn() === $rightParam->getIn())
                ) {
                    return $rightParam;
                }
            }

            return null;
        });

        $this->increased = $paramDiff->getIncreased();
        $this->missing = $paramDiff->getMissing();

        $shared = $paramDiff->getShared();

        foreach ($shared as $rightParam) {
            $leftParam = Arr::first($left, function (Parameter $leftParam) use ($rightParam) {
                return (
                    ($leftParam->getName() === $rightParam->getName())
                    && ($leftParam->getIn() === $rightParam->getIn())
                );
            });
            $changedParam = $this->getChangedParam($leftParam, $rightParam);

            if ($changedParam->isDiff()) {
                $this->changed[] = $changedParam;
            }
        }

        return $this;
    }

    protected function getChangedParam(Parameter $leftParam, Parameter $rightParam): ChangedParameter
    {
        $changedParam = new ChangedParameter();
        $changedParam->setLeftParam($leftParam);
        $changedParam->setRightParam($rightParam);

        $diff = ModelDiff::buildWithDefinitions($this->oldDefinitions, $this->newDefinitions)
            ->diff($leftParam->getSchema(), $rightParam->getSchema());

        $changedParam->setChanged($diff->getChanged());
        $changedParam->setIncreased($diff->getIncreased());
        $changedParam->setMissing($diff->getMissing());

        return $changedParam;
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