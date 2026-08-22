<?php

namespace App\Filament\Synthesizers;

use App\FieldTypes\Textarea;
use Lunar\Admin\Support\Synthesizers\AbstractFieldSynth;

class TextareaSynth extends AbstractFieldSynth
{
    public static $key = 'app_textarea_field';

    protected static $targetClass = Textarea::class;
}
