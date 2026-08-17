<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach (config('website-text', []) as $key => $definition) {
            $existing = DB::table('website_texts')->where('key', $key)->exists();
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

            if ($existing) {
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
    }

    public function down(): void
    {
        // Definition synchronization is intentionally non-destructive.
    }
};
