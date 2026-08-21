<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * New revision list, item 5: the site claimed every batch receives mass
 * spectrometry testing, but the current ILS certificates document HPLC
 * "Purity, Identity & Quantitation" only. The two admin-editable strings
 * carrying that claim are re-synced to the corrected config defaults.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['labs.hero_description', 'about.story_paragraph_2'] as $key) {
            $definition = config('website-text', [])[$key] ?? null;

            if ($definition === null) {
                continue;
            }

            DB::table('website_texts')->where('key', $key)->update([
                'default_value' => $definition['default'],
                'value' => $definition['default'],
                'updated_at' => now(),
            ]);
        }

        Cache::forget('website-text.values');
    }

    public function down(): void
    {
        // Content rewrite; the previous wording is not restored.
    }
};
