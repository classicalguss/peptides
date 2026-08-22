<?php

namespace App\Filament\FieldTypes;

use App\Filament\Synthesizers\TextareaSynth;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Textarea;
use Lunar\Admin\Support\FieldTypes\BaseFieldType;
use Lunar\Models\Attribute;

class TextareaField extends BaseFieldType
{
    protected static string $synthesizer = TextareaSynth::class;

    public static function getFilamentComponent(Attribute $attribute): Component
    {
        return Textarea::make($attribute->handle)
            ->rows(5)
            ->autosize()
            ->when(filled($attribute->validation_rules), fn (Textarea $component) => $component->rules($attribute->validation_rules))
            ->required((bool) $attribute->required)
            ->helperText($attribute->translate('description'))
            ->columnSpanFull();
    }
}
