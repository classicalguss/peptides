<?php

namespace App\Filament\FieldTypes;

use App\Filament\Synthesizers\TextListSynth;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TagsInput;
use Lunar\Admin\Support\FieldTypes\BaseFieldType;
use Lunar\Models\Attribute;

class TextListField extends BaseFieldType
{
    protected static string $synthesizer = TextListSynth::class;

    public static function getFilamentComponent(Attribute $attribute): Component
    {
        return TagsInput::make($attribute->handle)
            ->placeholder('Type an item and press Enter')
            ->reorderable()
            ->dehydrateStateUsing(fn ($state) => $state)
            ->when(filled($attribute->validation_rules), fn (TagsInput $component) => $component->rules($attribute->validation_rules))
            ->required((bool) $attribute->required)
            ->helperText($attribute->translate('description'))
            ->columnSpanFull();
    }
}
