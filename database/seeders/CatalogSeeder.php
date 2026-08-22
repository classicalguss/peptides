<?php

namespace Database\Seeders;

use App\FieldTypes\Textarea;
use App\FieldTypes\TextList;
use App\Models\CoaReport;
use App\Models\Product;
use App\Models\StackComponent;
use App\Models\StackTier;
use App\Support\WebsitePageAttributes;
use Illuminate\Database\Seeder;
use Lunar\FieldTypes\Dropdown;
use Lunar\FieldTypes\Number;
use Lunar\FieldTypes\Text;
use Lunar\FieldTypes\TranslatedText;
use Lunar\Models\Attribute;
use Lunar\Models\Brand;
use Lunar\Models\Channel;
use Lunar\Models\Collection as LunarCollection;
use Lunar\Models\CollectionGroup;
use Lunar\Models\Currency;
use Lunar\Models\CustomerGroup;
use Lunar\Models\Language;
use Lunar\Models\ProductOption;
use Lunar\Models\ProductOptionValue;
use Lunar\Models\ProductType;
use Lunar\Models\ProductVariant;
use Lunar\Models\TaxClass;
use Lunar\Models\Url;

/**
 * Builds the catalog from database/data/*.php, which mirror the live site.
 * Re-runnable: the existing catalog is removed first.
 */
class CatalogSeeder extends Seeder
{
    protected Language $language;

    protected Currency $currency;

    protected Channel $channel;

    protected CustomerGroup $customerGroup;

    protected TaxClass $taxClass;

    protected Brand $brand;

    protected ProductType $compoundType;

    protected ProductType $collectionType;

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

        $this->command?->info('Catalog seeded: '.count($this->compounds).' compounds, '.count($this->stacks).' collections.');
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
        CoaReport::query()->delete();
        StackComponent::query()->delete();
        StackTier::query()->delete();

        Product::query()->withTrashed()->each(function (Product $product) {
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
        $this->compoundType = ProductType::firstOrCreate(['name' => Product::TYPE_COMPOUND]);
        $this->collectionType = ProductType::firstOrCreate(['name' => Product::TYPE_COLLECTION]);

        // Lunar's own product attributes (name, description) apply to both types;
        // the Website Page group is mapped per type by WebsitePageAttributes.
        $standardAttributeIds = Attribute::where('attribute_type', Product::morphName())
            ->whereIn('handle', ['name', 'description'])
            ->pluck('id');

        foreach ([$this->compoundType, $this->collectionType] as $type) {
            $type->mappedAttributes()->syncWithoutDetaching($standardAttributeIds);
        }

        WebsitePageAttributes::ensure();
    }

    protected function seedCollections(): void
    {
        $this->collectionGroup = CollectionGroup::firstWhere('handle', 'main')
            ?? CollectionGroup::create(['handle' => 'main', 'name' => 'Catalog']);

        foreach ((require database_path('data/lab.php'))['collections'] as $slug => $name) {
            $collection = LunarCollection::create([
                'collection_group_id' => $this->collectionGroup->id,
                'type' => 'static',
                'attribute_data' => collect([
                    'name' => $this->translated($name),
                ]),
            ]);

            $this->url($collection, $slug);

            $this->collections[$slug] = $collection;
        }
    }

    /**
     * Lunar auto-generates a slug from the product/collection name, so any
     * generated URLs are replaced with the slug we want to route on.
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

    /**
     * @param  array<int, string>  $collectionSlugs
     */
    protected function publish(Product $product, array $collectionSlugs): void
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

        $ids = collect($collectionSlugs)
            ->map(fn ($slug) => $this->collections[$slug]->id ?? null)
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

        $product->addMedia($path)
            ->preservingOriginal()
            ->withCustomProperties(['primary' => $primary])
            ->toMediaCollection('images');
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function variant(Product $product, string $sku, int $stock, array $options = []): ProductVariant
    {
        return ProductVariant::create([
            'product_id' => $product->id,
            'tax_class_id' => $this->taxClass->id,
            'sku' => $sku,
            'unit_quantity' => 1,
            'min_quantity' => 1,
            'quantity_increment' => 1,
            'stock' => $stock,
            'backorder' => 0,
            'purchasable' => 'always',
            'shippable' => true,
            ...$options,
        ]);
    }

    protected function seedCompounds(): void
    {
        foreach (require database_path('data/compounds.php') as $data) {
            $product = Product::create([
                'product_type_id' => $this->compoundType->id,
                'brand_id' => $this->brand->id,
                'status' => 'published',
                'attribute_data' => collect([
                    'name' => $this->translated($data['name']),
                    'description' => $this->translated($data['overview']),
                    'subtitle' => new Text($data['subtitle']),
                    'dose' => new Text($data['dose']),
                    'summary' => new Textarea($data['summary']),
                    'overview' => new Textarea($data['overview']),
                    'research_info' => new Textarea($data['research_info'] ?? ''),
                    'storage' => new Textarea($data['storage']),
                    'highlights' => new TextList($data['highlights']),
                    'accent' => new Dropdown($data['accent']),
                    'display_order' => new Number($data['display_order']),
                ]),
            ]);

            $this->url($product, $data['key']);
            $this->publish($product, array_merge(['compounds'], $data['categories']));
            $this->attachImage($product, $data['image'], true);

            $variant = $this->variant($product, $data['sku'], 250);
            $unitPrice = (int) reset($data['prices']);

            foreach ($data['prices'] as $minQuantity => $price) {
                $variant->prices()->create([
                    'price' => $price,
                    'compare_price' => $minQuantity > 1 ? $unitPrice : null,
                    'currency_id' => $this->currency->id,
                    'min_quantity' => $minQuantity,
                ]);
            }

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

        foreach (require database_path('data/stacks.php') as $data) {
            $product = Product::create([
                'product_type_id' => $this->collectionType->id,
                'brand_id' => $this->brand->id,
                'status' => 'published',
                'attribute_data' => collect([
                    'name' => $this->translated($data['name']),
                    'description' => $this->translated($data['description']),
                    'protocol_label' => new Text($data['protocol_label']),
                    'tagline' => new Text($data['tagline']),
                    'summary' => new Textarea($data['summary']),
                    'pillars' => new TextList($data['pillars']),
                    'accent' => new Dropdown($data['accent']),
                    'display_order' => new Number($data['display_order']),
                ]),
            ]);

            $this->url($product, $data['key']);
            $this->publish($product, array_merge(['research-collections'], $data['categories']));

            foreach ($data['gallery'] as $index => $file) {
                $this->attachImage($product, $file, $index === 0);
            }

            $product->productOptions()->syncWithoutDetaching([$option->id => ['position' => 1]]);

            foreach ($data['tiers'] as $position => $tier) {
                $optionValue = $this->optionValue($option, $position + 1, "{$tier['code']} — {$tier['label']}");

                $variant = $this->variant($product, $tier['sku'], 100);
                $variant->values()->syncWithoutDetaching([$optionValue->id]);
                $variant->prices()->create([
                    'price' => $tier['price'],
                    'currency_id' => $this->currency->id,
                    'min_quantity' => 1,
                ]);

                StackTier::create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'code' => $tier['code'],
                    'label' => $tier['label'],
                    'supply_days' => $tier['supply_days'],
                    'position' => $position,
                ]);
            }

            $position = 0;

            foreach ($data['components'] as $key => $quantity) {
                if (! isset($this->compounds[$key])) {
                    continue;
                }

                StackComponent::create([
                    'stack_product_id' => $product->id,
                    'component_product_id' => $this->compounds[$key]->id,
                    'base_quantity' => $quantity,
                    'unit' => 'VIAL',
                    'position' => $position++,
                ]);
            }

            $this->stacks[$data['key']] = $product;
        }
    }

    protected function optionValue(ProductOption $option, int $position, string $name): ProductOptionValue
    {
        return $option->values()->where('position', $position)->first()
            ?? $option->values()->create([
                'name' => [$this->language->code => $name],
                'position' => $position,
            ]);
    }

    /**
     * Certificate PDFs are uploaded through the admin, so seeded batches
     * carry their details without a file.
     */
    protected function seedCoaReports(): void
    {
        foreach ((require database_path('data/lab.php'))['coas'] as $coa) {
            $product = isset($coa['compound']) ? ($this->compounds[$coa['compound']] ?? null) : null;

            CoaReport::create([
                'product_id' => $product?->id,
                'product_label' => $coa['label'],
                'batch_number' => $coa['batch'] ?? null,
                'tested_on' => $coa['tested_on'] ?? null,
                'purity' => $coa['purity'] ?? null,
                'lab_name' => $coa['lab'] ?? null,
                'pdf_path' => null,
                'status' => $coa['status'] ?? 'pass',
                'status_label' => $coa['status_label'] ?? null,
                'status_note' => $coa['status_note'] ?? null,
                'status_color' => $coa['status_color'] ?? 'gray',
            ]);
        }
    }
}
