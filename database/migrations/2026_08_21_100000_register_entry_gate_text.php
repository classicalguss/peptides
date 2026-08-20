<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Registers the entry acknowledgment gate copy (gate.heading, gate.body,
 * gate.button) as editable website text. The gate shipped as config-only
 * defaults, so it never appeared in the Website Text admin. Any existing
 * row is overwritten with the current default: the body wording is a
 * client-requested compliance revision that must replace older copy.
 */
return new class extends Migration
{
    private const KEYS = ['gate.heading', 'gate.body', 'gate.button'];

    public function up(): void
    {
        $now = now();

        foreach (self::KEYS as $key) {
            $definition = config('website-text', [])[$key] ?? null;

            if ($definition === null) {
                continue;
            }

            $attributes = [
                'page' => $definition['page'],
                'section' => $definition['section'],
                'label' => $definition['label'],
                'location_hint' => $definition['location_hint'] ?? null,
                'route_name' => $definition['route_name'] ?? null,
                'default_value' => $definition['default'],
                'value' => $definition['default'],
                'sort_order' => $definition['sort_order'] ?? 0,
                'updated_at' => $now,
            ];

            if (DB::table('website_texts')->where('key', $key)->exists()) {
                DB::table('website_texts')->where('key', $key)->update($attributes);
            } else {
                DB::table('website_texts')->insert([
                    ...$attributes,
                    'key' => $key,
                    'created_at' => $now,
                ]);
            }
        }

        Cache::forget('website-text.values');
    }

    public function down(): void
    {
        DB::table('website_texts')->whereIn('key', self::KEYS)->delete();

        Cache::forget('website-text.values');
    }
};
