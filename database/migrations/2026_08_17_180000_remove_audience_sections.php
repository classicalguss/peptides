<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Compliance revision, item 7: the "Fit Check / Who It's For" audience
 * section is removed entirely from collection pages (template and admin
 * field removed in the same change). Clears the now-unrendered audience
 * data and retires its website texts.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('product_profiles')->update(['audience' => null]);

        DB::table('website_texts')
            ->whereIn('key', ['collection_product.audience_eyebrow', 'collection_product.audience_title'])
            ->delete();

        Cache::forget('website-text.values');
    }

    public function down(): void
    {
        // Content removal; the previous wording is not restored.
    }
};
