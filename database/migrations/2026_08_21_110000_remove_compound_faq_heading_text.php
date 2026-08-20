<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * New revision list, item 2: the FAQ section is removed from individual
 * compound pages, so its heading is no longer used anywhere and leaves the
 * Website Text admin. The per-product FAQ data in product_profiles.faq is
 * preserved but unused, matching the earlier reviews removal.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('website_texts')->where('key', 'compound_product.faq_title')->delete();

        Cache::forget('website-text.values');
    }

    public function down(): void
    {
        // The heading definition was removed from config/website-text.php;
        // re-registering it would require restoring that entry first.
    }
};
