<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Compliance revision, item 3: remove outcome-based Research Collection
 * descriptions. Renames the six collections to neutral compound-based names,
 * replaces outcome subtitles/taglines ("Fat Loss + Appetite Control + ...")
 * with factual compound lists, swaps outcome pillar chips for documentation
 * badges, and syncs the Lunar product description attribute (still carrying
 * pre-item-1 outcome copy) to the neutral profile summary for all products.
 */
return new class extends Migration
{
    private const NAMES = [
        'healing-stack' => 'BPC-157 + TB-500 Research Collection',
        'energy-stack' => 'NAD+ + MOTS-c Research Collection',
        'beauty-stack' => 'GHK-Cu + NAD+ Research Collection',
        'primal-regen-stack' => 'BPC-157 + TB-500 + GHK-Cu + NAD+ Research Collection',
        'shredd-protocol' => 'Retatrutide + MOTS-c Research Collection',
        'cell-stack-protocol' => 'Six-Compound Research Collection',
    ];

    private const TAGLINES = [
        'healing-stack' => 'BPC-157 20mg · TB-500 20mg · Bacteriostatic Water',
        'energy-stack' => 'NAD+ 1000mg · MOTS-c 40mg · Bacteriostatic Water',
        'beauty-stack' => 'GHK-Cu 100mg · NAD+ 1000mg · Bacteriostatic Water',
        'primal-regen-stack' => 'BPC-157 · TB-500 · GHK-Cu · NAD+ · Bacteriostatic Water',
        'shredd-protocol' => 'Retatrutide 15mg · MOTS-c 40mg · Bacteriostatic Water',
        'cell-stack-protocol' => 'CJC-1295/Ipamorelin · BPC-157 · TB-500 · GHK-Cu · NAD+ · MOTS-c · Bacteriostatic Water',
    ];

    private const PILLARS = [
        'Third-Party Tested',
        'COA Available',
        'Batch Documented',
        'Research Use Only',
    ];

    public function up(): void
    {
        $profiles = DB::table('product_profiles')->get(['product_id', 'handle', 'kind', 'summary']);

        foreach ($profiles as $profile) {
            $raw = DB::table('lunar_products')->where('id', $profile->product_id)->value('attribute_data');
            $attributes = $raw ? json_decode($raw, true) : [];

            if (isset(self::NAMES[$profile->handle])) {
                $attributes['name']['value']['en'] = self::NAMES[$profile->handle];
            }

            if ($profile->summary !== null && isset($attributes['description'])) {
                $attributes['description']['value']['en'] = $profile->summary;
            }

            DB::table('lunar_products')
                ->where('id', $profile->product_id)
                ->update(['attribute_data' => json_encode($attributes)]);

            if ($profile->kind === 'stack') {
                DB::table('product_profiles')->where('product_id', $profile->product_id)->update([
                    'subtitle' => 'Research Collection',
                    'tagline' => self::TAGLINES[$profile->handle] ?? 'Research Collection',
                    'pillars' => json_encode(self::PILLARS),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Content rewrite; the previous wording is not restored.
    }
};
