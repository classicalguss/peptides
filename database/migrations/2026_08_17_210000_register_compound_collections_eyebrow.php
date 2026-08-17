<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Registers the related-collections eyebrow on compound pages as editable
 * website text. It was hardcoded as "Bundle &amp;amp; Save", which Blade
 * escaped a second time and rendered on the page as "BUNDLE &AMP; SAVE".
 */
return new class extends Migration
{
    public function up(): void
    {
        $key = 'compound_product.collections_eyebrow';
        $definition = config('website-text', [])[$key] ?? null;

        if ($definition === null || DB::table('website_texts')->where('key', $key)->exists()) {
            return;
        }

        $now = now();

        DB::table('website_texts')->insert([
            'key' => $key,
            'page' => $definition['page'],
            'section' => $definition['section'],
            'label' => $definition['label'],
            'location_hint' => $definition['location_hint'] ?? null,
            'route_name' => $definition['route_name'] ?? null,
            'default_value' => $definition['default'],
            'value' => $definition['default'],
            'sort_order' => $definition['sort_order'] ?? 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        Cache::forget('website-text.values');
    }

    public function down(): void
    {
        DB::table('website_texts')->where('key', 'compound_product.collections_eyebrow')->delete();

        Cache::forget('website-text.values');
    }
};
