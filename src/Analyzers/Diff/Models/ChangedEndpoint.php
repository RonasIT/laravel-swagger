<?php

namespace RonasIT\Support\AutoDoc\Analyzers\Diff\Models;

class ChangedEndpoint implements Changed
{
    private $pathUrl;
    private $newOperations;
    private $missingOperations;
    private $changedOperations;

    public function isDiff(): bool
    {
        return !empty($this->changedOperations);
    }

    /**
     * @return mixed
     */
    public function getPathUrl()
    {
        return $this->pathUrl;
    }

    /**
     * @param mixed $pathUrl
     */
    public function setPathUrl($pathUrl): void
    {
        $this->pathUrl = $pathUrl;
    }

    /**
     * @return mixed
     */
    public function getNewOperations()
    {
        return $this->newOperations;
    }

    /**
     * @param mixed $newOperations
     */
    public function setNewOperations($newOperations): void
    {
        $this->newOperations = $newOperations;
    }

    /**
     * @return mixed
     */
    public function getMissingOperations()
    {
        return $this->missingOperations;
    }

    /**
     * @param mixed $missingOperations
     */
    public function setMissingOperations($missingOperations): void
    {
        $this->missingOperations = $missingOperations;
    }

    /**
     * @return mixed
     */
    public function getChangedOperations()
    {
        return $this->changedOperations;
    }

    /**
     * @param mixed $changedOperations
     */
    public function setChangedOperations($changedOperations): void
    {
        $this->changedOperations = $changedOperations;
    }
}