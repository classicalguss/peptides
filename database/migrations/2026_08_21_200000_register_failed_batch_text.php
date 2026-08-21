<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * New revision list, item 13: a "Did not pass" batch status joins the COA
 * admin. Its public label and note are admin-editable website text.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach (['labs.failed_label', 'labs.failed_note'] as $key) {
            $definition = config('website-text', [])[$key] ?? null;

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
        DB::table('website_texts')->whereIn('key', ['labs.failed_label', 'labs.failed_note'])->delete();

        Cache::forget('website-text.values');
    }
};
