<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Compliance revision, item 2: remove all dosage / administration /
 * reference-range information. Clears the dosage field on every product
 * profile and retires the "Reference Range In Literature" website text,
 * whose storefront section and admin field have been removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('product_profiles')->update(['dosage' => null]);

        DB::table('website_texts')->where('key', 'compound_product.reference_heading')->delete();

        Cache::forget('website-text.values');
    }

    public function down(): void
    {
        // Content removal; the previous wording is not restored.
    }
};
