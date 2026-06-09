<?php

namespace RonasIT\AutoDoc\Contracts;

interface ControllerInspectorContract
{
    public function getResourceClass(): ?string;

    public function getRequestClass(): ?string;
}
