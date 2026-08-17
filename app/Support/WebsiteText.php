<?php

namespace App\Support;

use App\Models\WebsiteText as WebsiteTextModel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class WebsiteText
{
    private static ?bool $tableAvailable = null;

    public static function get(string $key, ?string $fallback = null): string
    {
        $definition = config("website-text.{$key}");
        $fallback ??= is_array($definition) ? ($definition['default'] ?? '') : '';

        if (! (self::$tableAvailable ??= Schema::hasTable('website_texts'))) {
            return $fallback;
        }

        $values = Cache::rememberForever(
            'website-text.values',
            fn () => WebsiteTextModel::query()->pluck('value', 'key')->all(),
        );

        return array_key_exists($key, $values) ? (string) $values[$key] : $fallback;
    }

    /** @return array<string, array<string, mixed>> */
    public static function definitions(): array
    {
        return config('website-text', []);
    }
}
