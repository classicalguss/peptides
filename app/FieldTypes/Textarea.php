<?php

namespace App\FieldTypes;

use JsonSerializable;
use Lunar\Base\FieldType;
use Lunar\Exceptions\FieldTypeException;

/**
 * Multi-line plain text attribute (paragraph copy such as a product's
 * description or storage notes). Behaves like Lunar's Text field type but is
 * edited in a textarea rather than a single-line input.
 */
class Textarea implements FieldType, JsonSerializable
{
    protected ?string $value = null;

    public function __construct(mixed $value = '')
    {
        $this->setValue($value);
    }

    public function jsonSerialize(): mixed
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->getValue() ?? '';
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(mixed $value): void
    {
        if ($value && ! is_string($value) && ! is_numeric($value)) {
            throw new FieldTypeException(self::class.' value must be a string.');
        }

        $this->value = $value === null ? null : (string) $value;
    }

    /**
     * @return array{options: array<string, mixed>}
     */
    public function getConfig(): array
    {
        return ['options' => []];
    }
}
