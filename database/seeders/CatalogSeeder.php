<?php

namespace Database\Seeders;

use App\Models\CoaReport;
use App\Models\ProductProfile;
use App\Models\ProductReview;
use App\Models\StackComponent;
use App\Models\StackTier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Lunar\FieldTypes\Text;
use Lunar\FieldTypes\TranslatedText;
use Lunar\Models\Attribute;
use Lunar\Models\AttributeGroup;
use Lunar\Models\Brand;
use Lunar\Models\Channel;
use Lunar\Models\Collection as LunarCollection;
use Lunar\Models\CollectionGroup;
use Lunar\Models\Currency;
use Lunar\Models\CustomerGroup;
use Lunar\Models\Language;
use Lunar\Models\Product;
use Lunar\Models\ProductOption;
use Lunar\Models\ProductType;
use Lunar\Models\ProductVariant;
use Lunar\Models\TaxClass;
use Lunar\Models\Url;

class CatalogSeeder extends Seeder
{
    protected Language $language;

    protected Currency $currency;

    protected Channel $channel;

    protected CustomerGroup $customerGroup;

    protected TaxClass $taxClass;

    protected Brand $brand;

    protected ProductType $compoundType;

    protected ProductType $stackType;

    protected CollectionGroup $collectionGroup;

    /** @var array<string, LunarCollection> */
    protected array $collections = [];

    /** @var array<string, Product> */
    protected array $compounds = [];

    /** @var array<string, Product> */
    protected array $stacks = [];

    public function run(): void
    {
        $this->language = Language::firstWhere('default', true) ?? Language::firstOrFail();
        $this->currency = Currency::firstWhere('default', true) ?? Currency::firstOrFail();
        $this->channel = Channel::firstWhere('default', true) ?? Channel::firstOrFail();
        $this->customerGroup = CustomerGroup::firstWhere('default', true) ?? CustomerGroup::firstOrFail();
        $this->taxClass = TaxClass::firstWhere('default', true) ?? TaxClass::firstOrFail();

        $this->brand = Brand::firstOrCreate(['name' => 'Powered Up Peptides']);

        $this->clearExistingCatalog();
        $this->seedProductTypes();
        $this->seedCollections();
        $this->seedCompounds();
        $this->seedStacks();
        $this->seedCoaReports();
        $this->seedReviews();

        $this->command->info('Catalog seeded: '.count($this->compounds).' compounds, '.count($this->stacks).' stacks.');
    }

    protected function translated(string $value): TranslatedText
    {
        return new TranslatedText(collect([
            $this->language->code => new Text($value),
        ]));
    }

    /**
     * Makes the seeder re-runnable without duplicating the catalog.
     */
    protected function clearExistingCatalog(): void
    {
        ProductReview::query()->delete();
        CoaReport::query()->delete();
        StackComponent::query()->delete();
        StackTier::query()->delete();
        ProductProfile::query()->delete();

        Product::query()->each(function (Product $product) {
            $product->urls()->delete();
            $product->variants()->each(function (ProductVariant $variant) {
                $variant->prices()->delete();
                $variant->values()->detach();
                $variant->forceDelete();
            });
            $product->clearMediaCollection('images');
            $product->forceDelete();
        });

        LunarCollection::query()->each(function (LunarCollection $collection) {
            $collection->urls()->delete();
            $collection->products()->detach();
            $collection->forceDelete();
        });
    }

    protected function seedProductTypes(): void
    {
        $this->compoundType = ProductType::firstOrCreate(['name' => 'Research Compound']);
        $this->stackType = ProductType::firstOrCreate(['name' => 'Stack Protocol']);

        $attributeIds = Attribute::where('attribute_type', (new Product)->getMorphClass())->pluck('id');

        foreach ([$this->compoundType, $this->stackType] as $type) {
            $type->mappedAttributes()->sync($attributeIds);
        }
    }

    protected function seedCollections(): void
    {
        $this->collectionGroup = CollectionGroup::firstWhere('handle', 'main')
            ?? CollectionGroup::create(['handle' => 'main', 'name' => 'Catalog']);

        $definitions = array_merge(
            ['stacks' => 'Stack Protocols', 'compounds' => 'Individual Compounds'],
            (require database_path('data/lab.php'))['categories'],
        );

        foreach ($definitions as $handle => $label) {
            $collection = LunarCollection::create([
                'collection_group_id' => $this->collectionGroup->id,
                'type' => 'static',
                'attribute_data' => collect([
                    'name' => $this->translated($label),
                ]),
            ]);

            $this->url($collection, $handle);

            $this->collections[$handle] = $collection;
        }
    }

    /**
     * Lunar auto-generates a slug from the product/collection name, so any
     * generated URLs are replaced with the handle we want to route on.
     */
    protected function url(Product|LunarCollection $model, string $slug): void
    {
        $model->urls()->delete();

        Url::create([
            'element_type' => $model->getMorphClass(),
            'element_id' => $model->id,
            'language_id' => $this->language->id,
            'slug' => $slug,
            'default' => true,
        ]);
    }

    protected function publish(Product $product, array $collectionHandles): void
    {
        $product->channels()->syncWithoutDetaching([
            $this->channel->id => ['enabled' => true, 'starts_at' => now()->subDay(), 'ends_at' => null],
        ]);

        $product->customerGroups()->syncWithoutDetaching([
            $this->customerGroup->id => [
                'enabled' => true,
                'visible' => true,
                'purchasable' => true,
                'starts_at' => now()->subDay(),
                'ends_at' => null,
            ],
        ]);

        $ids = collect($collectionHandles)
            ->map(fn ($handle) => $this->collections[$handle]->id ?? null)
            ->filter()
            ->all();

        if ($ids) {
            $product->collections()->syncWithoutDetaching($ids);
        }
    }

    protected function attachImage(Product $product, ?string $file, bool $primary = false): void
    {
        if (! $file) {
            return;
        }

        $path = public_path('assets/products/'.$file);

        if (! is_file($path)) {
            return;
        }

        $media = $product->addMedia($path)
            ->preservingOriginal()
            ->toMediaCollection('images');

        if ($primary) {
            $media->setCustomProperty('primary', true)->save();
        }
    }

    protected function seedCompounds(): void
    {
        foreach (require database_path('data/compounds.php') as $position => $data) {
            $title = $data['name'].' '.$data['dose'];

            $product = Product::create([
                'product_type_id' => $this->compoundType->id,
                'brand_id' => $this->brand->id,
                'status' => 'published',
                'attribute_data' => collect([
                    'name' => $this->translated($title),
                    'description' => $this->translated($data['overview']),
                ]),
            ]);

            $this->url($product, $data['key']);
            $this->publish($product, array_merge(['compounds'], $data['categories']));
            $this->attachImage($product, $data['image'], true);

            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'tax_class_id' => $this->taxClass->id,
                'sku' => $data['sku'],
                'unit_quantity' => 1,
                'min_quantity' => 1,
                'quantity_increment' => 1,
                'stock' => 250,
                'backorder' => 0,
                'purchasable' => 'always',
                'shippable' => true,
            ]);

            foreach ($data['tiers'] as $minQuantity => $unitPrice) {
                $variant->prices()->create([
                    'price' => (int) round($unitPrice * 100),
                    'compare_price' => $minQuantity > 1 ? (int) round(reset($data['tiers']) * 100) : null,
                    'currency_id' => $this->currency->id,
                    'min_quantity' => $minQuantity,
                ]);
            }

            ProductProfile::create([
                'product_id' => $product->id,
                'kind' => ProductProfile::KIND_COMPOUND,
                'handle' => $data['key'],
                'accent' => $data['accent'],
                'subtitle' => $data['subtitle'],
                'dose' => $data['dose'],
                'summary' => $data['overview'],
                'overview' => $data['overview'],
                'research_info' => $data['research_info'],
                'dosage' => $data['dosage'],
                'storage' => $data['storage'],
                'benefits' => $data['benefits'],
                'highlights' => $data['highlights'],
                'faq' => $data['faq'],
                'position' => $position,
            ]);

            $this->compounds[$data['key']] = $product;
        }
    }

    protected function seedStacks(): void
    {
        $option = ProductOption::firstOrCreate(['handle' => 'protocol'], [
            'name' => [$this->language->code => 'Protocol'],
            'label' => [$this->language->code => 'Choose Your Protocol'],
            'shared' => true,
        ]);

        $optionValues = [];
        $position = 0;

        foreach ([
            'HP' => 'HP — Beginner',
            'Z' => 'Z — Intermediate',
            'S' => 'S — Advanced',
        ] as $code => $label) {
            $position++;

            $optionValues[$code] = $option->values()->where('position', $position)->first()
                ?? $option->values()->create([
                    'name' => [$this->language->code => $label],
                    'position' => $position,
                ]);
        }

        foreach (require database_path('data/stacks.php') as $position => $data) {
            $product = Product::create([
                'product_type_id' => $this->stackType->id,
                'brand_id' => $this->brand->id,
                'status' => 'published',
                'attribute_data' => collect([
                    'name' => $this->translated($data['name']),
                    'description' => $this->translated($data['description']),
                ]),
            ]);

            $this->url($product, $data['key']);
            $this->publish($product, array_merge(['stacks'], $data['categories']));

            foreach ($data['gallery'] as $index => $file) {
                $this->attachImage($product, $file, $index === 0);
            }

            $product->productOptions()->syncWithoutDetaching([$option->id => ['position' => 1]]);

            foreach ($data['tiers'] as $tierIndex => $tier) {
                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    'tax_class_id' => $this->taxClass->id,
                    'sku' => 'PUP-'.Str::upper(Str::of($data['key'])->replace('-stack', '')->replace('-protocol', '')->slug('')).'-'.$tier['code'],
                    'unit_quantity' => 1,
                    'min_quantity' => 1,
                    'quantity_increment' => 1,
                    'stock' => 100,
                    'backorder' => 0,
                    'purchasable' => 'always',
                    'shippable' => true,
                ]);

                $variant->values()->syncWithoutDetaching([$optionValues[$tier['code']]->id]);

                $variant->prices()->create([
                    'price' => (int) round($tier['price'] * 100),
                    'compare_price' => (int) round($tier['price'] / (1 - ($tier['save'] / 100)) * 100),
                    'currency_id' => $this->currency->id,
                    'min_quantity' => 1,
                ]);

                StackTier::create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'code' => $tier['code'],
                    'label' => $tier['label'],
                    'price' => (int) round($tier['price'] * 100),
                    'subscribe_price' => (int) round($tier['subscribe'] * 100),
                    'supply_days' => $tier['days'],
                    'save_percent' => $tier['save'],
                    'position' => $tierIndex,
                ]);
            }

            foreach (array_values($data['components']) as $index => $quantity) {
                $key = array_keys($data['components'])[$index];

                if (! isset($this->compounds[$key])) {
                    continue;
                }

                StackComponent::create([
                    'stack_product_id' => $product->id,
                    'component_product_id' => $this->compounds[$key]->id,
                    'base_quantity' => $quantity,
                    'unit' => $key === 'bac-water-10ml' ? 'VIAL' : 'VIAL',
                    'benefit' => $this->componentBenefit($key),
                    'position' => $index,
                ]);
            }

            ProductProfile::create([
                'product_id' => $product->id,
                'kind' => ProductProfile::KIND_STACK,
                'handle' => $data['key'],
                'accent' => $data['accent'],
                'subtitle' => $data['tagline'],
                'tagline' => $data['tagline'],
                'protocol_label' => $data['protocol'],
                'summary' => $data['description'],
                'overview' => $data['description'],
                'benefits' => $data['benefits'],
                'pillars' => $data['pillars'],
                'audience' => $data['audience'],
                'save_up_to' => $data['save_up_to'],
                'position' => $position,
            ]);

            $this->stacks[$data['key']] = $product;
        }

        $this->recalculateSavings();
    }

    /**
     * The mockup's advertised discounts do not reconcile with the per-vial
     * prices, so savings are derived from the real cost of buying every
     * component separately. This keeps cards, buy box and the included-items
     * table all quoting the same number.
     */
    protected function recalculateSavings(): void
    {
        $unitPrices = [];

        foreach ($this->compounds as $key => $product) {
            $unitPrices[$product->id] = (int) $product->variants
                ->flatMap->prices
                ->where('min_quantity', 1)
                ->min('price.value');
        }

        foreach ($this->stacks as $product) {
            $components = StackComponent::where('stack_product_id', $product->id)->get();
            $best = 0.0;

            foreach (StackTier::where('product_id', $product->id)->get() as $tier) {
                $retail = $components->sum(
                    fn (StackComponent $component) => ($unitPrices[$component->component_product_id] ?? 0)
                        * $component->base_quantity
                        * $tier->multiplier()
                );

                // Clamped at zero: a negative figure means the bundle costs more
                // than its parts, which is a pricing error rather than a discount.
                $save = $retail > 0 ? max(0.0, round((1 - ($tier->price / $retail)) * 100, 1)) : 0.0;

                $tier->update(['save_percent' => $save]);

                $tier->variant?->prices()->update(['compare_price' => $retail]);

                $best = max($best, $save);
            }

            ProductProfile::where('product_id', $product->id)->update(['save_up_to' => $best]);
        }
    }

    protected function componentBenefit(string $key): string
    {
        return match ($key) {
            'bpc-157-20mg' => 'Tissue repair, tendon & ligament healing, gut & nerve support',
            'tb-500-20mg' => 'Accelerates recovery, reduces inflammation, improves mobility',
            'ghk-cu-100mg' => 'Collagen synthesis, skin remodelling, antioxidant support',
            'nad-1000mg' => 'Cellular energy, DNA repair pathways, longevity signalling',
            'mots-c-40mg' => 'Mitochondrial support, metabolic efficiency, endurance',
            'cjc-1295-ipamorelin-20mg' => 'GH optimization, lean mass, deep sleep quality',
            'retatrutide-15mg', 'retatrutide-30mg', 'retatrutide-60mg' => 'Appetite control, fat loss, metabolic optimization',
            'bac-water-10ml' => 'Bacteriostatic water for safe reconstitution and storage',
            default => 'Research compound',
        };
    }

    protected function seedCoaReports(): void
    {
        $lab = require database_path('data/lab.php');

        foreach ($lab['coas'] as $coa) {
            $product = $this->compounds[$coa['compound']] ?? null;

            CoaReport::updateOrCreate(['batch_number' => $coa['batch']], [
                'product_id' => $product?->id,
                'product_label' => $product
                    ? $product->translateAttribute('name')
                    : Str::headline($coa['compound']),
                'tested_on' => $coa['tested_on'],
                'purity' => $coa['purity'],
                'lab_name' => $coa['lab'],
                'pdf_path' => null,
            ]);
        }
    }

    protected function seedReviews(): void
    {
        $lab = require database_path('data/lab.php');

        foreach ($lab['reviews'] as $review) {
            $product = $this->compounds[$review['product']] ?? $this->stacks[$review['product']] ?? null;

            if (! $product) {
                continue;
            }

            ProductReview::create([
                'product_id' => $product->id,
                'author_name' => $review['author'],
                'rating' => $review['rating'],
                'title' => $review['title'],
                'body' => $review['body'],
                'is_verified' => true,
                'is_approved' => true,
            ]);
        }
    }
}
