<?php

namespace RonasIT\AutoDoc\Contracts;

use RonasIT\AutoDoc\DTO\ResourceSchema;

interface ControllerInspectorContract
{
    public function getResource(): ?ResourceSchema;

    public function getRequestClass(): ?string;
}
