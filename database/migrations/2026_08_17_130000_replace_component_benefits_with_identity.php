<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Compliance revision, item 4: remove benefit descriptions from the
 * "What's Included" collection tables. Each component row now describes
 * what the product is, not what it supposedly does.
 */
return new class extends Migration
{
    private const IDENTITY = [
        'bpc-157-20mg' => 'Lyophilised research peptide',
        'tb-500-20mg' => 'Lyophilised research peptide',
        'ghk-cu-100mg' => 'Copper-binding research peptide',
        'cjc-1295-ipamorelin-20mg' => 'Lyophilised research peptide blend',
        'retatrutide-15mg' => 'Lyophilised research peptide',
        'retatrutide-30mg' => 'Lyophilised research peptide',
        'retatrutide-60mg' => 'Lyophilised research peptide',
        'mots-c-40mg' => 'Mitochondrial-derived research peptide',
        'nad-1000mg' => 'Lyophilised coenzyme',
        'bac-water-10ml' => 'Laboratory supply',
    ];

    public function up(): void
    {
        $handles = DB::table('product_profiles')->pluck('handle', 'product_id');

        foreach (DB::table('stack_components')->get(['id', 'component_product_id']) as $component) {
            $identity = self::IDENTITY[$handles[$component->component_product_id] ?? null] ?? 'Research material';

            DB::table('stack_components')->where('id', $component->id)->update(['benefit' => $identity]);
        }
    }

    public function down(): void
    {
        // Content rewrite; the previous wording is not restored.
    }
};
