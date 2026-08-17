<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Compliance revision, item 6: Key Benefits sections are removed entirely
 * from compound and collection pages (templates and admin fields removed in
 * the same change). Clears the now-unrendered benefits data and retires the
 * "The Payoff" / "Key Benefits" website texts.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('product_profiles')->update(['benefits' => null]);

        DB::table('website_texts')
            ->whereIn('key', ['collection_product.benefits_eyebrow', 'collection_product.benefits_title'])
            ->delete();

        Cache::forget('website-text.values');
    }

    public function down(): void
    {
        // Content removal; the previous wording is not restored.
    }
};
