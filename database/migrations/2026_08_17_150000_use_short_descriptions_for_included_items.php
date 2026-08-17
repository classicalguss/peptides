<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rework of revision item 4: the What's Included table now shows each
 * component product's own short description (the label above the product
 * name) instead of a separate per-row text. One text, edited in one place,
 * shown in both. The redundant stack_components.benefit column is dropped.
 */
return new class extends Migration
{
    private const SHORT_DESCRIPTIONS = [
        'bpc-157-20mg' => 'Lyophilised research peptide',
        'tb-500-20mg' => 'Lyophilised research peptide',
        'ghk-cu-100mg' => 'Copper-binding research peptide',
        'cjc-1295-ipamorelin-20mg' => 'Lyophilised peptide blend',
        'retatrutide-15mg' => 'Triple-agonist research peptide',
        'retatrutide-30mg' => 'Triple-agonist research peptide',
        'retatrutide-60mg' => 'Triple-agonist research peptide',
        'mots-c-40mg' => 'Mitochondrial-derived research peptide',
        'nad-1000mg' => 'Lyophilised coenzyme',
        'bac-water-10ml' => 'Laboratory supply',
    ];

    public function up(): void
    {
        foreach (self::SHORT_DESCRIPTIONS as $handle => $text) {
            DB::table('product_profiles')->where('handle', $handle)->update(['subtitle' => $text]);
        }

        Schema::table('stack_components', function ($table) {
            $table->dropColumn('benefit');
        });
    }

    public function down(): void
    {
        Schema::table('stack_components', function ($table) {
            $table->string('benefit')->nullable();
        });
    }
};
