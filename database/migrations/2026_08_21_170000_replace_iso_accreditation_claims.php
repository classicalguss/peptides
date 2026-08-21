<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * New revision list, item 6: ISO-accreditation claims are replaced
 * site-wide with the client's wording — "Testing under an ISO/IEC 17025
 * quality management system" (short) or "Independent laboratory testing
 * performed under an ISO/IEC 17025 quality management system." (long).
 * Both affected strings are admin-editable, so their live values are
 * re-synced to the corrected config defaults.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['global.trust_4_detail', 'home.why_1_body'] as $key) {
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
