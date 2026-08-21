<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Seeds any configured website list that has no items yet (revision item
 * 15 adds the laboratory-supplies checkmark lines). Lists that already
 * have items — including admin-edited ones — are left alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach (config('website-lists', []) as $listKey => $definition) {
            if (DB::table('website_list_items')->where('list_key', $listKey)->exists()) {
                continue;
            }

            foreach ($definition['defaults'] as $index => $item) {
                DB::table('website_list_items')->insert([
                    'list_key' => $listKey,
                    'sort_order' => $index + 1,
                    'heading' => $item['heading'] ?? null,
                    'body' => $item['body'] ?? null,
                    'extra' => $item['extra'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        Cache::forget('website-list.items');
    }

    public function down(): void
    {
        // Seeding is intentionally non-destructive.
    }
};
