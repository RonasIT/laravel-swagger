<?php

namespace RonasIT\AutoDoc\Contracts;

interface ControllerInspectorContract
{
    public function getResourceSchemaName(): ?string;

    public function getRequestClass(): ?string;
}
