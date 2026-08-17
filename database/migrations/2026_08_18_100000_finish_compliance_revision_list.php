<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Lunar\Models\Collection as LunarCollection;
use Lunar\Models\Product;
use Lunar\Models\ProductType;

/**
 * Finishes the client's compliance revision list: items 13, 14, 16, 21, 23,
 * 28 (final wording), 29, 30, 31, 33, 35 (text), 36. Groups every remaining
 * data-level change into one migration since they are being reviewed and
 * deployed together.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->replaceWebsiteText();
        $this->renameProtocolLabelsAndProductType();
        $this->rebuildShopFilters();

        Cache::forget('website-text.values');
    }

    public function down(): void
    {
        // Content rewrite; the previous wording is not restored.
    }

    /**
     * Item 14 (protocol terminology) + 16 (Why Researchers Use It) + 21
     * (footer disclaimer) + 28 (synergistic wording, final copy) + 29
     * (testing language) + 30 (Every Vial, Documented description) + 31
     * (Stack/Stacks terminology) + 33 (Bundle) + 35 (Cold Chain Packed).
     */
    private function replaceWebsiteText(): void
    {
        $replacements = [
            'global.meta_description' => 'Third-party tested research peptides and research collections. Published COA for every batch.',
            'global.announcement_detail' => 'Third-party tested · COA published per batch · Free shipping over $200',
            'global.footer_collections_link' => 'Research Collections',
            'global.nav_collections' => 'Collections',
            'collections.breadcrumb' => 'Collections',
            'global.footer_disclaimer' => 'All products sold by Powered Up Peptides are intended exclusively for in-vitro laboratory research and analytical purposes and are not for human or veterinary use or consumption. Products are not intended for diagnostic, therapeutic, or clinical use. Purchasers must be 21 years of age or older. By purchasing, you acknowledge these restrictions and agree to our Terms and Compliance policies.',
            'global.trust_2_title' => 'Batch Documented',
            'global.trust_4_title' => 'Independent Labs',
            'global.trust_4_detail' => 'ISO-accredited testing',
            'home.hero_eyebrow' => 'Third-Party Tested · COA Published Per Batch',
            'home.meta_title' => 'Powered Up Peptides — Research Peptides & Research Collections',
            'home.hero_description' => 'Premium research peptides and pre-built research collections. Every batch is independently HPLC verified and the certificate of analysis is published before it ships — so you always know exactly what is in the vial.',
            'home.hero_primary_button' => 'Shop Research Collections',
            'home.collections_title' => 'Research Collections',
            'home.collections_description' => 'Curated collections of research compounds offered in multiple quantities. Save up to 58% versus purchasing individual vials separately.',
            'home.compounds_description' => 'Building your own collection? Every compound is available on its own with quantity-break pricing — the more vials, the lower the unit cost.',
            'home.closing_description' => 'Start with a research collection, or browse the full compound library and build your own.',
            'home.closing_primary_button' => 'Shop Research Collections',
            'shop.hero_description' => 'Single vials for researchers building their own collection. Every compound is third-party tested with the certificate published against its batch number, and unit prices drop automatically as quantity goes up.',
            'shop.collections_link' => 'Looking for complete collections? Browse research collections →',
            'shop.cross_sell_description' => 'Our research collections pair these compounds together at a lower cost per vial than buying them individually.',
            'shop.cross_sell_button' => 'View Research Collections',
            'collections.meta_title' => 'Research Collections — Powered Up Peptides',
            'collections.meta_description' => 'Curated research peptide collections. Each collection pairs compounds together with published lab results for every batch.',
            'collections.hero_eyebrow' => 'Research Collections',
            'collections.hero_title' => 'Research Collections',
            'collections.hero_description' => 'Every research collection pairs compounds together at a single price, with independent third-party testing and a published certificate of analysis for every batch.',
            'collections.all_filter' => 'All Collections',
            'collections.category_suffix' => 'Collections',
            'collections.item_singular' => 'collection',
            'collections.item_plural' => 'collections',
            'collections.empty_title' => 'No collections here',
            'collections.empty_button' => 'Show All Collections',
            'collections.view_button' => 'View Collection',
            'collections.cross_sell_description' => 'Every compound inside these collections is available on its own, with quantity-break pricing that drops the unit cost as you scale up.',
            'about.compliance_paragraph_2' => 'We do not provide dosing guidance, medical guidance or advice on administration, and we will not do so if asked. Where our product pages cite figures from published literature, that is a reference to the research record, not a recommendation.',
            'contact.meta_description' => 'Questions about an order, a research collection or a batch certificate? Talk to the Powered Up Peptides team.',
            'contact.hero_description' => 'Order issues, batch certificates, research collection questions or bulk enquiries — a real person reads every message and replies within one business day.',
            'contact.protocol_help_title' => 'Not sure which collection?',
            'contact.protocol_help_description' => 'Every research collection lists its components and quantities.',
            'about.story_paragraph_1' => 'Anyone who has sourced research peptides knows the routine. A vendor claims high purity. There is no certificate, or there is one certificate from two years ago covering a batch you did not receive. You are left running your own verification before you can even begin the work you actually planned.',
            'about.story_paragraph_3' => 'If a batch does not clear our internal quality review, it does not get sold. It is a simple rule, and it is the entire reason this company exists.',
            'cart.empty_description' => 'Start with a research collection, or browse the compound library and build your own.',
            'cart.empty_collections_button' => 'Shop Research Collections',
            'collection_product.breadcrumb' => 'Research Collections',
            'collection_product.choose_heading' => 'Choose Collection Size',
            'collection_product.add_button' => 'Add Collection To Cart',
            'collection_product.compounds_description' => 'Each compound in this research collection has its own product page with research information, storage information, and batch-specific analytical documentation.',
            'collection_product.lab_description' => 'Independent third-party analysis for the exact batches included in this research collection.',
            'collection_product.other_title' => 'Other Research Collections',
            'compound_product.highlights_eyebrow' => 'Research Material Information',
            'compound_product.collections_title' => 'Available In These Research Collections',
            'compound_product.collections_description' => 'This compound is also available as part of a Research Collection, at a lower cost per vial than buying it individually.',
            'compound_product.collections_eyebrow' => 'Available In Research Collections',
            'register.introduction' => 'Track orders, pull batch certificates and reorder your research collection in one click.',
            'account.empty_orders_description' => 'Your first order is waiting.',
        ];

        foreach ($replacements as $key => $value) {
            DB::table('website_texts')->where('key', $key)->update(['value' => $value]);
        }
    }

    /**
     * Item 14 + 23: the collection-page eyebrow becomes the literal
     * "Research Collection" label for every collection (client's header
     * format), replacing the six outcome-named "___ Protocol" values.
     * The Lunar product type "Stack Protocol" also drops "Protocol".
     */
    private function renameProtocolLabelsAndProductType(): void
    {
        DB::table('product_profiles')
            ->where('kind', 'stack')
            ->update(['protocol_label' => 'Research Collection']);

        ProductType::where('name', 'Stack Protocol')->update(['name' => 'Research Collection']);
    }

    /**
     * Item 13: the shop's goal-based filters (Recovery, Energy, Aesthetic,
     * Fat Loss, Performance, Longevity) overlap arbitrarily by intended
     * human use, not by any real compound distinction. Replaced with two
     * honest categories from the client's own suggested list.
     */
    private function rebuildShopFilters(): void
    {
        $peptideProductIds = Product::whereHas('productType', fn ($q) => $q->where('name', 'Research Compound'))
            ->whereDoesntHave('urls', fn ($q) => $q->where('slug', 'bac-water-10ml'))
            ->pluck('id');

        // "Recovery" becomes the neutral "Peptides" filter, covering every
        // peptide compound (everything except BAC Water).
        $this->renameCollection('recovery', 'Peptides', 'peptides');
        $peptides = LunarCollection::whereHas('urls', fn ($q) => $q->where('slug', 'peptides'))->first();
        $peptides?->products()->sync($peptideProductIds);

        // "Supplies" becomes "Laboratory Supplies" (display name only).
        $this->renameCollection('supplies', 'Laboratory Supplies');

        // The master collection grouping all six research collections is
        // named "Stack Protocols" on some environments. Its slug differs
        // between local ('stacks') and production ('research-collections'),
        // so match by current name instead.
        LunarCollection::all()->each(function (LunarCollection $collection) {
            if ($collection->translateAttribute('name') === 'Stack Protocols') {
                $attributes = json_decode(DB::table('lunar_collections')->where('id', $collection->id)->value('attribute_data'), true) ?? [];
                $attributes['name']['value']['en'] = 'Research Collections';

                DB::table('lunar_collections')->where('id', $collection->id)->update([
                    'attribute_data' => json_encode($attributes),
                ]);
            }
        });

        // The remaining outcome-named collections lose their products, so
        // they silently drop out of the shop filter bar (Catalog::categoriesFor
        // only lists collections with at least one matching product).
        foreach (['energy', 'aesthetic', 'fat-loss', 'performance', 'longevity'] as $slug) {
            $collection = LunarCollection::whereHas('urls', fn ($q) => $q->where('slug', $slug))->first();
            $collection?->products()->sync([]);
        }
    }

    private function renameCollection(string $currentSlug, string $newName, ?string $newSlug = null): void
    {
        $collection = LunarCollection::whereHas('urls', fn ($q) => $q->where('slug', $currentSlug))->first();

        if (! $collection) {
            return;
        }

        $attributes = json_decode(DB::table('lunar_collections')->where('id', $collection->id)->value('attribute_data'), true) ?? [];
        $attributes['name']['value']['en'] = $newName;

        DB::table('lunar_collections')->where('id', $collection->id)->update([
            'attribute_data' => json_encode($attributes),
        ]);

        if ($newSlug !== null) {
            DB::table('lunar_urls')
                ->where('element_type', 'collection')
                ->where('element_id', $collection->id)
                ->update(['slug' => $newSlug]);
        }
    }
};
