<?php

use App\Support\WebsiteText;
use Illuminate\Support\HtmlString;

if (! function_exists('site_text')) {
    /**
     * Return admin-editable website copy with a code fallback.
     */
    function site_text(string $key, ?string $fallback = null): string
    {
        return WebsiteText::get($key, $fallback);
    }
}

if (! function_exists('foil_last_words')) {
    /**
     * Safely retain the storefront's highlighted heading style for plain text.
     */
    function foil_last_words(string $text, int $count = 1, string $class = 'text-foil'): HtmlString
    {
        $words = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $count = min(max(1, $count), count($words));
        $plain = array_slice($words, 0, count($words) - $count);
        $foil = array_slice($words, -$count);
        $parts = [];

        if ($plain !== []) {
            $parts[] = e(implode(' ', $plain));
        }

        if ($foil !== []) {
            $parts[] = '<span class="'.e($class).'">'.e(implode(' ', $foil)).'</span>';
        }

        return new HtmlString(implode(' ', $parts));
    }
}
