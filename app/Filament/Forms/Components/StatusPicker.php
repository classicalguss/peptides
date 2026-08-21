<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Field;

/**
 * Jira-style status field: a coloured pill showing the current status text;
 * clicking it opens a small editor with the text and a row of predefined
 * colour swatches. The text binds to this field's state, the colour to a
 * sibling field named via colorField().
 */
class StatusPicker extends Field
{
    use HasPlaceholder;

    protected string $view = 'filament.forms.components.status-picker';

    protected string $colorField = 'color';

    /** @var array<string, array{label: string, swatch: string, text: string}> */
    protected array $colors = [];

    public function colorField(string $name): static
    {
        $this->colorField = $name;

        return $this;
    }

    /**
     * @param  array<string, array{label: string, swatch: string, text: string}>  $colors
     */
    public function colors(array $colors): static
    {
        $this->colors = $colors;

        return $this;
    }

    public function getColorStatePath(): string
    {
        return $this->getContainer()->getStatePath().'.'.$this->colorField;
    }

    /** @return array<string, array{label: string, swatch: string, text: string}> */
    public function getColors(): array
    {
        return $this->colors;
    }
}
