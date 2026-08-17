<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Compliance revision, item 10 (HIGH PRIORITY): keep BAC Water presentation
 * minimal and research-focused. The product's own page was already neutral
 * from item 1; this removes the three remaining site-wide phrases that
 * frame bacteriostatic water as something the buyer personally "needs" to
 * reconstitute and use peptides.
 *
 * The included-items description also matches revision item 12's quoted
 * text and replacement verbatim, so that wording is applied here rather
 * than being changed twice.
 */
return new class extends Migration
{
    private const REPLACEMENTS = [
        'home.collections_description' => 'Curated collections of research compounds offered in multiple quantities, including the laboratory supplies listed on each collection page.',
        'collection_product.included_description' => 'Vial quantities are shown by collection size. Each collection contains the research materials and laboratory supplies listed below.',
        'compound_product.collections_description' => 'This compound is also available as part of a Research Collection, at a lower cost per vial than buying it individually.',
    ];

    public function up(): void
    {
        foreach (self::REPLACEMENTS as $key => $value) {
            DB::table('website_texts')->where('key', $key)->update(['value' => $value]);
        }

        Cache::forget('website-text.values');
    }

    public function down(): void
    {
        // Content rewrite; the previous wording is not restored.
    }
};
