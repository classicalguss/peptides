<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Compliance revision, item 1: remove human / clinical outcome claims from
 * product-page prose. Rewrites summary, overview, research_info, highlights,
 * and FAQ copy to neutral research-material language. Runs as a migration so
 * the same change lands on every environment's database.
 *
 * Structural sections (dosage, benefits, audience, subtitles) are handled by
 * later revision items and are intentionally untouched here.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->profileText() as $handle => $fields) {
            if (isset($fields['highlights'])) {
                $fields['highlights'] = json_encode($fields['highlights']);
            }

            if (isset($fields['faq'])) {
                $fields['faq'] = json_encode($fields['faq']);
            }

            DB::table('product_profiles')
                ->where('handle', $handle)
                ->update($fields);
        }
    }

    public function down(): void
    {
        // Content rewrite; the previous wording is not restored.
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function profileText(): array
    {
        $collectionSummary = 'A curated collection of lyophilised research materials available in multiple quantities. Each compound is independently analyzed with batch-specific documentation available.';

        return [
            'bpc-157-20mg' => [
                'summary' => 'BPC-157 is a synthetic pentadecapeptide corresponding to a partial sequence of body protection compound (BPC) first isolated from gastric juice. Supplied as lyophilised powder for in-vitro laboratory research.',
                'overview' => 'BPC-157 is a synthetic pentadecapeptide corresponding to a partial sequence of body protection compound (BPC) first isolated from gastric juice. Supplied as lyophilised powder for in-vitro laboratory research.',
                'research_info' => 'Pentadecapeptide BPC-157 is the subject of a substantial published in-vitro and animal literature, including work on VEGFR2 signalling and growth-hormone receptor expression in tendon fibroblast models.',
                'highlights' => [
                    'Synthetic pentadecapeptide, 20mg lyophilised per vial',
                    'Independently analysed with batch-specific COA available',
                    'Subject of a substantial published in-vitro and animal research literature',
                    'Supplied strictly for laboratory research use',
                ],
                'faq' => [
                    ['q' => 'Is this product for human use?', 'a' => 'No. All Powered Up Peptides products are sold strictly for in-vitro laboratory research and are not for human or veterinary use or consumption.'],
                    ['q' => 'How is purity verified?', 'a' => 'Every batch is tested by an independent third-party ISO-accredited lab using HPLC and mass spectrometry. The certificate for your batch is published on our Lab Reports page.'],
                    ['q' => 'Does it ship with bacteriostatic water?', 'a' => 'No. Bacteriostatic water is a separate laboratory supply, and is also included in every Research Collection.'],
                ],
            ],
            'tb-500-20mg' => [
                'summary' => 'TB-500 is a synthetic fragment of thymosin beta-4, a naturally occurring 43-amino-acid peptide. The supplied fragment contains the actin-binding domain. Supplied as lyophilised powder for laboratory research.',
                'overview' => 'TB-500 is a synthetic fragment of thymosin beta-4, a naturally occurring 43-amino-acid peptide. The supplied fragment contains the actin-binding domain. Supplied as lyophilised powder for laboratory research.',
                'research_info' => 'TB-500 corresponds to a 7-amino-acid actin-binding fragment of thymosin beta-4. Published animal literature examines cell-migration and angiogenesis models.',
                'highlights' => [
                    'Synthetic thymosin beta-4 fragment, 20mg lyophilised per vial',
                    'Independently analysed with batch-specific COA available',
                    'Subject of published cell-migration and angiogenesis research literature',
                    'Supplied strictly for laboratory research use',
                ],
                'faq' => [
                    ['q' => 'How does TB-500 differ from BPC-157?', 'a' => 'BPC-157 appears in the literature mainly in localised tissue models, while TB-500 appears mainly in cell-migration assays. The two are frequently studied together in published study designs, which is why both appear in the same research collection.'],
                    ['q' => 'How is purity verified?', 'a' => 'Every batch is analysed by an independent laboratory. See the Lab Reports page for the batch-specific certificate of analysis.'],
                ],
            ],
            'ghk-cu-100mg' => [
                'summary' => 'GHK-Cu is a naturally occurring copper-binding tripeptide (glycyl-L-histidyl-L-lysine) complexed with copper(II). Supplied as lyophilised powder for laboratory research.',
                'overview' => 'GHK-Cu is a naturally occurring copper-binding tripeptide (glycyl-L-histidyl-L-lysine) complexed with copper(II). Supplied as lyophilised powder for laboratory research.',
                'research_info' => 'GHK-Cu is the subject of published transcriptomic analyses examining gene-expression modulation, including collagen I/III expression and metalloproteinase balance, in in-vitro models.',
                'highlights' => [
                    'Copper-binding research tripeptide, 100mg lyophilised per vial',
                    'Independently analysed with batch-specific COA available',
                    'Subject of published transcriptomic and in-vitro research literature',
                    'Supplied strictly for laboratory research use',
                ],
                'faq' => [
                    ['q' => 'Why is the vial 100mg?', 'a' => 'Published GHK-Cu studies work with substantially larger material quantities than growth-factor peptides, so it is supplied at 100mg per vial for cost efficiency.'],
                ],
            ],
            'cjc-1295-ipamorelin-20mg' => [
                'summary' => 'A pre-blended vial pairing CJC-1295 (no DAC), a growth-hormone-releasing-hormone analogue, with Ipamorelin, a selective growth-hormone secretagogue. Supplied as a single lyophilised blend for laboratory research.',
                'overview' => 'A pre-blended vial pairing CJC-1295 (no DAC), a growth-hormone-releasing-hormone analogue, with Ipamorelin, a selective growth-hormone secretagogue. Supplied as a single lyophilised blend for laboratory research.',
                'research_info' => 'CJC-1295 without DAC (mod-GRF 1-29) is a 29-amino-acid GHRH analogue with a short half-life. Ipamorelin is a pentapeptide ghrelin-receptor agonist noted in the literature for receptor selectivity.',
                'highlights' => [
                    'Single-vial lyophilised blend, 20mg total',
                    'Independently analysed with batch-specific COA available',
                    'Both components are the subject of published receptor-pharmacology literature',
                    'Supplied strictly for laboratory research use',
                ],
                'faq' => [
                    ['q' => 'Why no DAC?', 'a' => 'The no-DAC form has a shorter half-life, which the published literature contrasts with the sustained profile of the DAC form.'],
                ],
            ],
            'retatrutide-15mg' => [
                'summary' => 'Retatrutide (LY3437943) is a synthetic 39-amino-acid peptide engineered as a triple agonist of the GIP, GLP-1, and glucagon receptors. Supplied as lyophilised powder for laboratory research.',
                'overview' => 'Retatrutide (LY3437943) is a synthetic 39-amino-acid peptide engineered as a triple agonist of the GIP, GLP-1, and glucagon receptors. Supplied as lyophilised powder for laboratory research.',
                'research_info' => 'Retatrutide incorporates a C20 fatty di-acid moiety and is the subject of a substantial published literature on incretin receptor pharmacology.',
                'highlights' => [
                    'Triple receptor agonist (GIP / GLP-1 / glucagon), 15mg lyophilised per vial',
                    'Independently analysed with batch-specific COA available',
                    'Subject of a substantial published research literature',
                    'Supplied strictly for laboratory research use',
                ],
                'faq' => [
                    ['q' => 'Which vial size should I choose?', 'a' => 'The 15mg vial suits smaller-scale work. The 30mg and 60mg vials reduce cost per milligram where larger material quantities are required.'],
                ],
            ],
            'retatrutide-30mg' => [
                'summary' => 'The 30mg presentation of Retatrutide, supplied where larger material quantities are required. Identical compound to the 15mg presentation.',
                'overview' => 'The 30mg presentation of Retatrutide, supplied where larger material quantities are required. Identical compound to the 15mg presentation.',
                'research_info' => 'Identical compound and batch chemistry to the 15mg presentation, filled at 30mg per vial.',
                'highlights' => [
                    'Triple receptor agonist (GIP / GLP-1 / glucagon), 30mg lyophilised per vial',
                    'Independently analysed with batch-specific COA available',
                    'Lower cost per milligram than the 15mg presentation',
                    'Supplied strictly for laboratory research use',
                ],
                'faq' => [],
            ],
            'retatrutide-60mg' => [
                'summary' => 'The highest-capacity Retatrutide presentation, supplied where larger material quantities are required. Identical compound to the 15mg and 30mg presentations.',
                'overview' => 'The highest-capacity Retatrutide presentation, supplied where larger material quantities are required. Identical compound to the 15mg and 30mg presentations.',
                'research_info' => 'Identical compound and batch chemistry to the 15mg and 30mg presentations, filled at 60mg per vial.',
                'highlights' => [
                    'Triple receptor agonist (GIP / GLP-1 / glucagon), 60mg lyophilised per vial',
                    'Independently analysed with batch-specific COA available',
                    'Lowest cost per milligram in the range',
                    'Supplied strictly for laboratory research use',
                ],
                'faq' => [],
            ],
            'mots-c-40mg' => [
                'summary' => 'MOTS-c is a 16-amino-acid mitochondrial-derived peptide encoded in the mitochondrial 12S rRNA region. Supplied as lyophilised powder for laboratory research.',
                'overview' => 'MOTS-c is a 16-amino-acid mitochondrial-derived peptide encoded in the mitochondrial 12S rRNA region. Supplied as lyophilised powder for laboratory research.',
                'research_info' => 'Published literature examines MOTS-c in AMPK-signalling and glucose-utilisation pathway models, largely in murine studies.',
                'highlights' => [
                    'Mitochondrial-derived research peptide, 40mg lyophilised per vial',
                    'Independently analysed with batch-specific COA available',
                    'Subject of published metabolic-signalling research literature',
                    'Supplied strictly for laboratory research use',
                ],
                'faq' => [
                    ['q' => 'Why does MOTS-c appear in three research collections?', 'a' => 'Its place in the mitochondrial and metabolic research literature overlaps several research areas, so it is included in three collections.'],
                ],
            ],
            'nad-1000mg' => [
                'summary' => 'NAD+ (nicotinamide adenine dinucleotide) is an essential coenzyme present in every living cell, central to mitochondrial energy metabolism and a substrate for sirtuin and PARP enzymes. Supplied as lyophilised powder for laboratory research.',
                'overview' => 'NAD+ (nicotinamide adenine dinucleotide) is an essential coenzyme present in every living cell, central to mitochondrial energy metabolism and a substrate for sirtuin and PARP enzymes. Supplied as lyophilised powder for laboratory research.',
                'research_info' => 'Nicotinamide adenine dinucleotide in its oxidised form. Published literature examines NAD+ availability in mitochondrial and sirtuin-pathway models.',
                'highlights' => [
                    'Coenzyme supplied as lyophilised powder, 1000mg per vial',
                    'Independently analysed with batch-specific COA available',
                    'Subject of a broad published biochemistry literature',
                    'Supplied strictly for laboratory research use',
                ],
                'faq' => [
                    ['q' => 'Is this NAD+ or a precursor like NMN?', 'a' => 'This is NAD+ itself, not a precursor, supplied as lyophilised powder at 1000mg per vial.'],
                ],
            ],
            'bac-water-10ml' => [
                'summary' => 'Sterile water containing 0.9% benzyl alcohol as a bacteriostatic preservative, supplied in sealed 10ml multi-use vials as a laboratory supply.',
                'overview' => 'Sterile water containing 0.9% benzyl alcohol as a bacteriostatic preservative, supplied in sealed 10ml multi-use vials as a laboratory supply.',
                'research_info' => 'Benzyl alcohol at 0.9% inhibits bacterial growth in multi-use laboratory containers, unlike plain sterile water, which is single-use.',
                'highlights' => [
                    'Sterile filled and sealed 10ml vial',
                    '0.9% benzyl alcohol preservative',
                    'Multi-use laboratory supply',
                    'Batch documentation available',
                ],
                'faq' => [
                    ['q' => 'Can plain sterile water be used instead?', 'a' => 'Plain sterile water contains no preservative and is single-use. Bacteriostatic water is the standard multi-use laboratory supply.'],
                ],
            ],
            'healing-stack' => [
                'summary' => $collectionSummary,
                'overview' => $collectionSummary,
            ],
            'energy-stack' => [
                'summary' => $collectionSummary,
                'overview' => $collectionSummary,
            ],
            'beauty-stack' => [
                'summary' => $collectionSummary,
                'overview' => $collectionSummary,
            ],
            'primal-regen-stack' => [
                'summary' => $collectionSummary,
                'overview' => $collectionSummary,
            ],
            'shredd-protocol' => [
                'summary' => $collectionSummary,
                'overview' => $collectionSummary,
            ],
            'cell-stack-protocol' => [
                'summary' => $collectionSummary,
                'overview' => $collectionSummary,
            ],
        ];
    }
};
