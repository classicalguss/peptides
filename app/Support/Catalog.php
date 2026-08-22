<?php

namespace App\Support;

use App\Models\Product;
use App\Models\StackComponent;
use App\Models\StackTier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Lunar\Models\Collection as LunarCollection;
use Lunar\Models\ProductVariant;

class Catalog
{
    /**
     * Research Collections, in display order.
     *
     * @return Collection<int, Product>
     */
    public static function stacks(): Collection
    {
        return static::ofType(Product::TYPE_COLLECTION);
    }

    /**
     * Individual compounds (and, optionally, supplies), in display order.
     *
     * @return Collection<int, Product>
     */
    public static function compounds(bool $includeSupplies = true): Collection
    {
        return static::ofType(Product::TYPE_COMPOUND)
            ->when(! $includeSupplies, fn (Collection $items) => $items->reject(
                fn (Product $product) => $product->isSupply()
            ))
            ->values();
    }

    /**
     * @return Collection<int, Product>
     */
    protected static function ofType(string $typeName): Collection
    {
        $typeId = Product::typeId($typeName);

        if ($typeId === null) {
            return collect();
        }

        return static::query()
            ->where('product_type_id', $typeId)
            ->get()
            ->sortBy(fn (Product $product) => [$product->displayOrder(), $product->id])
            ->values();
    }

    /**
     * Published products with everything the storefront cards need.
     *
     * @return Builder<Product>
     */
    protected static function query(): Builder
    {
        return Product::query()
            ->status('published')
            ->with(['media', 'urls', 'variants.prices']);
    }

    /**
     * Navigable categories, keyed by slug.
     *
     * @return Collection<string, array{label: string, count: int}>
     */
    public static function categories(): Collection
    {
        return LunarCollection::with('urls')
            ->withCount('products')
            ->get()
            ->mapWithKeys(fn (LunarCollection $collection) => [
                (string) $collection->urls->first()?->slug => [
                    'label' => $collection->translateAttribute('name'),
                    'count' => $collection->products_count,
                ],
            ])
            ->filter(fn ($item, $slug) => $slug !== '' && $item['count'] > 0);
    }

    /**
     * Categories limited to the supplied products, so counts reflect what the
     * page can actually show. The `stacks` and `compounds` umbrella collections
     * are excluded by default; they duplicate the top-level navigation.
     *
     * @param  Collection<int, Product>  $products
     * @param  array<int, string>  $exclude
     * @return Collection<string, array{label: string, count: int}>
     */
    public static function categoriesFor(Collection $products, array $exclude = ['stacks', 'compounds']): Collection
    {
        $productIds = $products->pluck('id')->all();

        if ($productIds === []) {
            return collect();
        }

        return LunarCollection::with('urls')
            ->get()
            ->mapWithKeys(function (LunarCollection $collection) use ($productIds) {
                $slug = (string) $collection->urls->first()?->slug;

                return [$slug => [
                    'label' => $collection->translateAttribute('name'),
                    'count' => $collection->products()->whereIn('lunar_products.id', $productIds)->count(),
                ]];
            })
            ->filter(fn (array $item, string $slug) => $slug !== ''
                && ! in_array($slug, $exclude, true)
                && $item['count'] > 0);
    }

    /**
     * Product ids in a Lunar collection, looked up once per request per slug.
     *
     * @return array<int, int>
     */
    public static function productIdsInCategory(string $slug): array
    {
        static $memo = [];

        if (! array_key_exists($slug, $memo)) {
            $collection = LunarCollection::whereHas('urls', fn ($query) => $query->where('slug', $slug))->first();

            $memo[$slug] = $collection ? $collection->products()->pluck('lunar_products.id')->map(fn ($id) => (int) $id)->all() : [];
        }

        return $memo[$slug];
    }

    /**
     * Resolve a storefront page by the product's Lunar URL slug — the value
     * links are built from and the one admins edit on the product.
     */
    public static function findBySlug(string $slug): ?Product
    {
        return static::query()
            ->whereHas('urls', fn ($urls) => $urls->where('slug', $slug))
            ->first();
    }

    /**
     * Cost of buying every vial in each tier separately, in cents, keyed by
     * tier code.
     *
     * @param  Collection<int, StackTier>  $tiers
     * @param  Collection<int, StackComponent>  $components
     * @param  Collection<int, Product>  $componentProducts
     * @return array<string, int>
     */
    public static function retailValues(Collection $tiers, Collection $components, Collection $componentProducts): array
    {
        $unitPrices = $componentProducts->mapWithKeys(
            fn (Product $product) => [$product->id => static::unitPrice($product)]
        );

        return $tiers->mapWithKeys(fn (StackTier $tier) => [
            $tier->code => $components->sum(
                fn (StackComponent $component) => ($unitPrices[$component->component_product_id] ?? 0)
                    * $component->base_quantity
                    * $tier->multiplier()
            ),
        ])->all();
    }

    /**
     * Percentage saved per tier against buying the vials separately, keyed
     * by tier code. Derived from live prices, never stored.
     *
     * @param  Collection<int, StackTier>  $tiers
     * @param  array<string, int>  $retailValues
     * @return array<string, float>
     */
    public static function savings(Collection $tiers, array $retailValues): array
    {
        return $tiers->mapWithKeys(function (StackTier $tier) use ($retailValues): array {
            $retail = $retailValues[$tier->code] ?? 0;
            $price = $tier->priceValue();

            return [$tier->code => $retail > $price && $retail > 0 ? round((1 - $price / $retail) * 100, 1) : 0.0];
        })->all();
    }

    /**
     * Largest tier saving for a collection, for "Save up to" badges.
     * Memoised per request because cards call it in loops.
     */
    public static function saveUpTo(Product $product): float
    {
        static $memo = [];

        if (! $product->isStack()) {
            return 0.0;
        }

        if (array_key_exists($product->id, $memo)) {
            return $memo[$product->id];
        }

        $tiers = StackTier::where('product_id', $product->id)->with('variant.prices')->get();
        $components = StackComponent::where('stack_product_id', $product->id)->get();
        $componentProducts = static::componentProducts($components);

        $savings = static::savings($tiers, static::retailValues($tiers, $components, $componentProducts));

        return $memo[$product->id] = (float) (max($savings ?: [0.0]));
    }

    /**
     * The compounds referenced by a collection's components, in display order.
     *
     * @param  Collection<int, StackComponent>  $components
     * @return Collection<int, Product>
     */
    public static function componentProducts(Collection $components): Collection
    {
        return static::query()
            ->whereIn('id', $components->pluck('component_product_id'))
            ->get()
            ->sortBy(fn (Product $product) => [$product->displayOrder(), $product->id])
            ->values();
    }

    /**
     * Cheapest unit price across every variant and quantity break, in cents.
     */
    public static function fromPrice(Product $product): int
    {
        return (int) $product->variants
            ->flatMap->prices
            ->min('price.value') ?: 0;
    }

    /**
     * Single-unit price, in cents.
     */
    public static function unitPrice(Product $product): int
    {
        $prices = $product->variants->flatMap->prices
            ->where('min_quantity', 1);

        return (int) ($prices->min('price.value') ?: static::fromPrice($product));
    }

    /**
     * Display details for a purchased variant, used by cart and order views.
     *
     * @return array{name: string, meta: ?string, url: ?string, image: ?string, accent: string}
     */
    public static function variantDisplay(?ProductVariant $variant): array
    {
        if (! $variant) {
            return ['name' => 'Item no longer available', 'meta' => null, 'url' => null, 'image' => null, 'accent' => config('theme.brand.gold')];
        }

        /** @var Product $product */
        $product = $variant->product;
        $tier = StackTier::where('product_variant_id', $variant->id)->first();

        return [
            'name' => $product->translateAttribute('name'),
            'meta' => $tier ? "{$tier->code} — {$tier->label} · {$tier->supply_days}-day supply" : $product->dose,
            'url' => $product->storefrontUrl(),
            'image' => $product->getFirstMedia('images')?->getUrl('small'),
            'accent' => $product->accentHex(),
        ];
    }

    public static function money(int $cents): string
    {
        return '$'.number_format($cents / 100, ($cents % 100 === 0) ? 0 : 2);
    }
}
