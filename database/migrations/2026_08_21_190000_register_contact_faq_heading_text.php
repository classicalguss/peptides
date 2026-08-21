<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * New revision list, item 11: the FAQ section heading ("Common Questions" /
 * "Before You Ask") joins the FAQ entries as admin-editable text so the
 * whole section can be managed without a developer.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach (['contact.faq_eyebrow', 'contact.faq_title'] as $key) {
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
        DB::table('website_texts')->whereIn('key', ['contact.faq_eyebrow', 'contact.faq_title'])->delete();

        Cache::forget('website-text.values');
    }
};
