<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Compliance revision, item 5: remove remaining Retatrutide weight-loss /
 * appetite-suppression language. Items 1 and 3 already neutralized the
 * prose; this clears the Key Benefits chips on the three Retatrutide
 * products, the benefit/audience lists on the Retatrutide collection, and
 * unapproves a customer review claiming weight loss.
 */
return new class extends Migration
{
    public function up(): void
    {
        $chips = [
            'retatrutide-15mg' => [
                ['title' => 'GIP / GLP-1 / Glucagon', 'detail' => 'Receptor Agonist'],
                ['title' => '39-Amino-Acid', 'detail' => 'Synthetic Peptide'],
                ['title' => 'Batch-Specific', 'detail' => 'COA Available'],
                ['title' => 'Research Use', 'detail' => 'Only'],
            ],
            'retatrutide-30mg' => [
                ['title' => 'GIP / GLP-1 / Glucagon', 'detail' => 'Receptor Agonist'],
                ['title' => '39-Amino-Acid', 'detail' => 'Synthetic Peptide'],
                ['title' => 'Lower Cost', 'detail' => 'Per Milligram'],
                ['title' => 'Research Use', 'detail' => 'Only'],
            ],
            'retatrutide-60mg' => [
                ['title' => 'GIP / GLP-1 / Glucagon', 'detail' => 'Receptor Agonist'],
                ['title' => '39-Amino-Acid', 'detail' => 'Synthetic Peptide'],
                ['title' => 'Lowest Cost', 'detail' => 'Per Milligram'],
                ['title' => 'Research Use', 'detail' => 'Only'],
            ],
        ];

        foreach ($chips as $handle => $benefits) {
            DB::table('product_profiles')->where('handle', $handle)->update([
                'benefits' => json_encode($benefits),
            ]);
        }

        DB::table('product_profiles')->where('handle', 'shredd-protocol')->update([
            'benefits' => json_encode([
                'Retatrutide and MOTS-c supplied together',
                'Bacteriostatic water included as a laboratory supply',
                'Independent third-party analysis for every batch',
                'Batch-specific documentation available',
                'Lower cost than purchasing vials individually',
            ]),
            'audience' => json_encode([
                'Incretin receptor pharmacology research',
                'Metabolic-pathway research',
                'Laboratories requiring batch-specific documentation',
                'Multi-compound research settings',
            ]),
        ]);

        // Review claiming personal weight loss on the Retatrutide collection.
        DB::table('product_reviews')
            ->where(fn ($q) => $q->where('title', 'like', '%appetite%')
                ->orWhere('body', 'like', '%lb%')
                ->orWhere('body', 'like', '%weight%'))
            ->update(['is_approved' => false]);
    }

    public function down(): void
    {
        // Content removal; the previous wording is not restored.
    }
};
