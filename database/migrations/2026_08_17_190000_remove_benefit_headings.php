<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Compliance revision, item 8: remove remaining benefit-style headings.
 * "The Payoff" and "Key Benefits" went with item 6; the homepage collections
 * eyebrow is the last outcome heading, and the client's own replacement for
 * it (revision item 34) is used here. Also registers the collection lab
 * results heading as editable website text.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('website_texts')
            ->where('key', 'home.collections_eyebrow')
            ->update(['value' => 'Backed By Independent Testing']);

        $now = now();

        $definitions = config('website-text', []);

        foreach (['collection_product.lab_eyebrow', 'collection_product.lab_title'] as $key) {
            $definition = $definitions[$key] ?? null;

            if ($definition === null || DB::table('website_texts')->where('key', $key)->exists()) {
                continue;
            }

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
        }

        Cache::forget('website-text.values');
    }

    public function down(): void
    {
        // Content change; the previous wording is not restored.
    }
};
