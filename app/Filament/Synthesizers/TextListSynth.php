<?php

namespace App\Filament\Synthesizers;

use App\FieldTypes\TextList;
use Lunar\Admin\Support\Synthesizers\AbstractFieldSynth;

class TextListSynth extends AbstractFieldSynth
{
    public static $key = 'app_text_list_field';

    protected static $targetClass = TextList::class;

    public function get(&$target, $key)
    {
        return $target->getValue()[$key] ?? null;
    }

    public function set(&$target, $key, $value)
    {
        $items = $target->getValue();
        $items[$key] = $value;
        $target->setValue($items);
    }

    public function unset(&$target, $key)
    {
        $items = $target->getValue();
        unset($items[$key]);
        $target->setValue($items);
    }
}
