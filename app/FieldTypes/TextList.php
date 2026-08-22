<?php

namespace App\FieldTypes;

use JsonSerializable;
use Lunar\Base\FieldType;
use Lunar\Exceptions\FieldTypeException;

/**
 * An ordered list of short text lines (highlights, pills). Stored as a plain
 * JSON array, unlike Lunar's key/value ListField.
 */
class TextList implements FieldType, JsonSerializable
{
    /** @var array<int, string> */
    protected array $value = [];

    public function __construct(mixed $value = [])
    {
        $this->setValue($value);
    }

    public function jsonSerialize(): mixed
    {
        return $this->value;
    }

    /**
     * @return array<int, string>
     */
    public function getValue(): array
    {
        return $this->value;
    }

    public function setValue(mixed $value): void
    {
        if ($value === null || $value === '') {
            $value = [];
        }

        if (is_string($value)) {
            $value = json_decode($value, true) ?? [];
        }

        if (! is_array($value)) {
            throw new FieldTypeException(self::class.' value must be an array.');
        }

        $this->value = array_values(array_filter(
            array_map(fn ($item) => trim((string) $item), $value),
            fn (string $item) => $item !== ''
        ));
    }

    /**
     * @return array{options: array<string, mixed>}
     */
    public function getConfig(): array
    {
        return ['options' => []];
    }
}
