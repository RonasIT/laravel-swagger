<?php

namespace RonasIT\Support\AutoDoc\Models\Refs;

use Exception;
use Illuminate\Support\Str;

/**
 * @property string $ref
 * @property string $simpleRef
 * @property string $format
 * @property string $type
 */
class GenericRef
{
    private $reference;
    private $simpleReference;
    private $format;
    private $type;

    public function __construct(string $referenceType, string $reference)
    {
        $this->reference = $reference;
        $this->format = $this->computeReferenceFormat($reference);
        $this->simpleReference = $this->computeSimpleReference($this->reference, $this->format, $referenceType);
    }

    private function computeSimpleReference(string $reference, string $format, string $type): ?string
    {
        switch ($format) {
            case RefFormat::INTERNAL:
                return Str::substr($reference, strrpos($reference, '/'));
            default:
                return null;
        }
    }

    private function computeReferenceFormat(string $reference): string
    {
        if (Str::startsWith($reference, "http:") || Str::startsWith($reference, "https:")) {
            return RefFormat::URL;
        } elseif (Str::startsWith($reference, "#/")) {
            return RefFormat::INTERNAL;
        } elseif (Str::startsWith($reference, ".") || Str::startsWith($reference, "/")) {
            return RefFormat::RELATIVE;
        } else {
            throw new Exception('Invalid ref format');
        }
    }

    public function getSimpleReference(): ?string
    {
        return $this->simpleReference;
    }
}