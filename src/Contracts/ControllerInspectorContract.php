<?php

namespace RonasIT\AutoDoc\Contracts;

use RonasIT\AutoDoc\DTO\ResolvedResource;

interface ControllerInspectorContract
{
    public function getResourceClass(): ?ResolvedResource;

    public function getRequestClass(): ?string;
}
