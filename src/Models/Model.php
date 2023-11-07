<?php

namespace RonasIT\Support\AutoDoc\Models;

use Illuminate\Support\Arr;
use RonasIT\Support\AutoDoc\Models\Refs\GenericRef;
use RonasIT\Support\AutoDoc\Models\Refs\RefType;

/**
 * @property ?GenericRef $ref
 * @property array<string, Property> $properties
 */
class Model
{
    private $reference = null;
    private $properties;

    public function __construct(array $data)
    {
        $this->properties = Arr::get($data, 'properties');

        if (!empty($data['$ref'])) {
            $this->reference = new GenericRef(RefType::TYPE_DEFINITION, $data['$ref']);
        } elseif (!empty($data['items']['$ref'])) {
            $this->reference = new GenericRef(RefType::TYPE_DEFINITION, $data['items']['$ref']);
        }
    }

    public function getReferenceSchema(array $definitionMap)
    {
        $modelName = (empty($this->reference)) ? $this->reference->getSimpleReference() : null;

        return empty($modelName) ? null : $definitionMap[$modelName];
    }

    public function getReference(): ?GenericRef
    {
        return $this->reference;
    }

    public function getProperties()
    {
        return $this->properties;
    }
}