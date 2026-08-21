<?php

namespace App\Support;

use App\Models\ProductProfile;
use App\Models\StackTier;
use Illuminate\Support\Collection;
use Lunar\Models\Collection as LunarCollection;
use Lunar\Models\ProductVariant;

class Catalog
{
    /**
     * @return Collection<int, ProductProfile>
     */
    public static function stacks(): Collection
    {
        return static::profiles(ProductProfile::KIND_STACK);
    }

    /**
     * @return Collection<int, ProductProfile>
     */
    public static function compounds(bool $includeSupplies = true): Collection
    {
        return static::profiles(ProductProfile::KIND_COMPOUND)
            ->when(! $includeSupplies, fn (Collection $items) => $items->reject(
                fn (ProductProfile $profile) => $profile->handle === 'bac-water-10ml'
            ))
            ->values();
    }

    /**
     * @return Collection<int, ProductProfile>
     */
    protected static function profiles(string $kind): Collection
    {
        return ProductProfile::where('kind', $kind)
            ->orderBy('position')
            ->with([
                'product.media',
                'product.urls',
                'product.variants.prices',
            ])
            ->get();
    }

    /**
     * Stack protocols first, then individual compounds.
     *
     * @return Collection<int, ProductProfile>
     */
    public static function all(): Collection
    {
        return ProductProfile::orderByRaw("case when kind = 'stack' then 0 else 1 end")
            ->orderBy('position')
            ->with(['product.media', 'product.urls', 'product.variants.prices'])
            ->get();
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
     * @param  Collection<int, ProductProfile>  $profiles
     * @param  array<int, string>  $exclude
     * @return Collection<string, array{label: string, count: int}>
     */
    public static function categoriesFor(Collection $profiles, array $exclude = ['stacks', 'compounds']): Collection
    {
        $productIds = $profiles->pluck('product_id')->all();

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
     * @return array<int, int> product ids
     */
    public static function productIdsInCategory(string $slug): array
    {
        $collection = LunarCollection::whereHas('urls', fn ($query) => $query->where('slug', $slug))->first();

        return $collection ? $collection->products()->pluck('lunar_products.id')->all() : [];
    }

    /**
     * Resolve a storefront page by the product's Lunar URL slug — the value
     * links are built from and the one admins can edit — falling back to the
     * profile handle so older links keep working.
     */
    public static function findByHandle(string $slug): ?ProductProfile
    {
        $query = ProductProfile::with([
            'product.media',
            'product.urls',
            'product.variants.prices',
        ]);

        return (clone $query)
            ->whereHas('product.urls', fn ($urls) => $urls->where('slug', $slug))
            ->first()
            ?? (clone $query)->where('handle', $slug)->first();
    }

    /**
     * Cheapest unit price across every variant and quantity break, in cents.
     */
    public static function fromPrice(ProductProfile $profile): int
    {
        return (int) $profile->product->variants
            ->flatMap->prices
            ->min('price.value') ?: 0;
    }

    /**
     * Single-unit price, in cents.
     */
    public static function unitPrice(ProductProfile $profile): int
    {
        $prices = $profile->product->variants->flatMap->prices
            ->where('min_quantity', 1);

        return (int) ($prices->min('price.value') ?: static::fromPrice($profile));
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

        $product = $variant->product;
        $profile = ProductProfile::where('product_id', $product->id)->first();
        $slug = $product->urls->first()?->slug;

        $url = match (true) {
            $slug === null => null,
            (bool) $profile?->isStack() => route('stack', $slug),
            default => route('compound', $slug),
        };

        $tier = StackTier::where('product_variant_id', $variant->id)->first();

        return [
            'name' => $product->translateAttribute('name'),
            'meta' => $tier ? "{$tier->code} — {$tier->label} · {$tier->supply_days}-day supply" : $profile?->dose,
            'url' => $url,
            'image' => $product->getFirstMedia('images')?->getUrl('small'),
            'accent' => $profile?->accentHex() ?? config('theme.brand.gold'),
        ];
    }

    public static function money(int $cents): string
    {
        return '$'.number_format($cents / 100, ($cents % 100 === 0) ? 0 : 2);
    }
}
