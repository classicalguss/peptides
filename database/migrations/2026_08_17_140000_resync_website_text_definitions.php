<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Re-sync website text definitions from config into the database so newly
 * registered keys (included-table column headings, revision item 4) become
 * editable in the admin. Existing admin-edited values are preserved.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach (config('website-text', []) as $key => $definition) {
            $metadata = [
                'page' => $definition['page'],
                'section' => $definition['section'],
                'label' => $definition['label'],
                'location_hint' => $definition['location_hint'] ?? null,
                'route_name' => $definition['route_name'] ?? null,
                'default_value' => $definition['default'],
                'sort_order' => $definition['sort_order'] ?? 0,
                'updated_at' => $now,
            ];

            if (DB::table('website_texts')->where('key', $key)->exists()) {
                DB::table('website_texts')->where('key', $key)->update($metadata);
            } else {
                DB::table('website_texts')->insert([
                    ...$metadata,
                    'key' => $key,
                    'value' => $definition['default'],
                    'created_at' => $now,
                ]);
            }
        }

        Cache::forget('website-text.values');
    }

    public function down(): void
    {
        // Definition synchronization is intentionally non-destructive.
    }
};
