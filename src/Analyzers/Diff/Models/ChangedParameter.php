<?php

namespace RonasIT\Support\AutoDoc\Analyzers\Diff\Models;

use RonasIT\Support\AutoDoc\Models\Parameter;

/**
 * @property Parameter $leftParam
 * @property Parameter $rightParam
 */
class ChangedParameter implements Changed
{
    private $increased = [];
    private $missing = [];
    private $changed = [];

    private $leftParam;
    private $rightParam;

    public function isDiff(): bool
    {
        return (!empty($this->missing) || !empty($this->increased) || !empty($this->changed));
    }

    /**
     * @param array $changed
     */
    public function setChanged(array $changed): void
    {
        $this->changed = $changed;
    }

    /**
     * @param array $missing
     */
    public function setMissing(array $missing): void
    {
        $this->missing = $missing;
    }

    /**
     * @param array $increased
     */
    public function setIncreased(array $increased): void
    {
        $this->increased = $increased;
    }

    /**
     * @param Parameter $rightParam
     */
    public function setRightParam(Parameter $rightParam): void
    {
        $this->rightParam = $rightParam;
    }

    /**
     * @param Parameter $leftParam
     */
    public function setLeftParam(Parameter $leftParam): void
    {
        $this->leftParam = $leftParam;
    }
}