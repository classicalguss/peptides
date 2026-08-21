<?php

namespace App\Http\Controllers;

use App\Models\CoaReport;
use App\Models\ProductProfile;
use App\Models\StackComponent;
use App\Models\StackTier;
use App\Support\Catalog;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function home(): View
    {
        $stacks = Catalog::stacks();
        $featured = $stacks->firstWhere('handle', 'healing-stack') ?? $stacks->first();

        return view('storefront.home', [
            'stacks' => $stacks,
            'compounds' => Catalog::compounds(includeSupplies: false)->take(8),
            'compoundCount' => Catalog::compounds()->count(),
            'featured' => $featured,
            'featuredFrom' => $featured ? Catalog::fromPrice($featured) : 0,
        ]);
    }

    /**
     * Individual compounds and supplies. Stack protocols have their own page.
     */
    public function shop(Request $request): View
    {
        $category = (string) $request->string('category');
        $sort = $request->string('sort')->toString() ?: 'featured';

        $all = Catalog::compounds();
        $profiles = $all;

        if ($category !== '' && $category !== 'all') {
            $ids = Catalog::productIdsInCategory($category);
            $profiles = $profiles->whereIn('product_id', $ids)->values();
        }

        return view('storefront.shop', [
            'profiles' => $this->sortProfiles($profiles, $sort),
            'totalCount' => $all->count(),
            'categories' => Catalog::categoriesFor($all),
            'activeCategory' => $category === '' ? 'all' : $category,
            'sort' => $sort,
        ]);
    }

    /**
     * Stack protocol index.
     */
    public function stacks(Request $request): View
    {
        $category = (string) $request->string('category');
        $sort = $request->string('sort')->toString() ?: 'featured';

        $all = Catalog::stacks();
        $profiles = $all;

        if ($category !== '' && $category !== 'all') {
            $ids = Catalog::productIdsInCategory($category);
            $profiles = $profiles->whereIn('product_id', $ids)->values();
        }

        $productIds = $all->pluck('product_id');

        return view('storefront.stacks', [
            'profiles' => $this->sortProfiles($profiles, $sort),
            'totalCount' => $all->count(),
            'categories' => Catalog::categoriesFor($all),
            'activeCategory' => $category === '' ? 'all' : $category,
            'sort' => $sort,
            'tiers' => StackTier::whereIn('product_id', $productIds)
                ->orderBy('position')
                ->get()
                ->groupBy('product_id'),
            'componentCounts' => StackComponent::whereIn('stack_product_id', $productIds)
                ->get()
                ->groupBy('stack_product_id')
                ->map->count(),
        ]);
    }

    /**
     * @param  Collection<int, ProductProfile>  $profiles
     * @return Collection<int, ProductProfile>
     */
    protected function sortProfiles(Collection $profiles, string $sort): Collection
    {
        return match ($sort) {
            'price-asc' => $profiles->sortBy(fn (ProductProfile $p) => Catalog::fromPrice($p))->values(),
            'price-desc' => $profiles->sortByDesc(fn (ProductProfile $p) => Catalog::fromPrice($p))->values(),
            'name' => $profiles->sortBy(fn (ProductProfile $p) => $p->product->translateAttribute('name'))->values(),
            default => $profiles->values(),
        };
    }

    public function compound(Request $request, string $slug): View
    {
        $profile = Catalog::findByHandle($slug);

        abort_if(! $profile || $profile->isStack(), 404);

        $variant = $profile->product->variants->first();
        $images = $profile->product->getMedia('images');
        $activeIndex = min(max((int) $request->integer('image'), 0), max($images->count() - 1, 0));

        $tiers = $variant
            ? $variant->prices->sortBy('min_quantity')->values()
            : collect();

        $unitPrice = Catalog::unitPrice($profile);

        return view('storefront.compound', [
            'profile' => $profile,
            'variant' => $variant,
            'images' => $images,
            'activeIndex' => $activeIndex,
            'activeImage' => $images->get($activeIndex),
            'priceTiers' => $tiers,
            'unitPrice' => $unitPrice,
            'coa' => CoaReport::where('product_id', $profile->product_id)->first(),
            'usedInStacks' => $this->stacksContaining($profile),
            'related' => Catalog::compounds(includeSupplies: false)
                ->reject(fn (ProductProfile $item) => $item->id === $profile->id)
                ->take(4),
        ]);
    }

    public function labReports(Request $request): View
    {
        $search = trim((string) $request->string('batch'));

        $reports = CoaReport::query()
            ->when($search !== '', fn ($query) => $query
                ->where('batch_number', 'like', "%{$search}%")
                ->orWhere('product_label', 'like', "%{$search}%"))
            ->orderByRaw("case status when 'pass' then 0 else 1 end")
            ->orderBy('product_label')
            ->with('product.urls')
            ->get();

        return view('storefront.lab-reports', [
            'reports' => $reports,
            'search' => $search,
            'total' => CoaReport::count(),
        ]);
    }

    /**
     * @return Collection<int, ProductProfile>
     */
    protected function stacksContaining(ProductProfile $profile): Collection
    {
        $stackIds = StackComponent::where('component_product_id', $profile->product_id)
            ->pluck('stack_product_id');

        return Catalog::stacks()->whereIn('product_id', $stackIds)->values();
    }

    public function stack(Request $request, string $slug): View
    {
        $profile = Catalog::findByHandle($slug);

        abort_if(! $profile || ! $profile->isStack(), 404);

        $images = $profile->product->getMedia('images');
        $activeIndex = min(max((int) $request->integer('image'), 0), max($images->count() - 1, 0));

        $tiers = StackTier::where('product_id', $profile->product_id)
            ->orderBy('position')
            ->get();

        $components = StackComponent::where('stack_product_id', $profile->product_id)
            ->orderBy('position')
            ->with(['component.urls', 'componentProfile'])
            ->get();

        $componentProfiles = ProductProfile::whereIn('product_id', $components->pluck('component_product_id'))
            ->with(['product.media', 'product.urls', 'product.variants.prices'])
            ->orderBy('position')
            ->get();

        return view('storefront.stack', [
            'profile' => $profile,
            'slug' => $slug,
            'images' => $images,
            'activeIndex' => $activeIndex,
            'activeImage' => $images->get($activeIndex),
            'tiers' => $tiers,
            'components' => $components,
            'componentProfiles' => $componentProfiles,
            'retailValues' => $this->retailValues($tiers, $components, $componentProfiles),
            'coas' => CoaReport::whereIn('product_id', $components->pluck('component_product_id'))
                ->orderBy('product_label')
                ->get(),
            'otherStacks' => Catalog::stacks()
                ->reject(fn (ProductProfile $item) => $item->id === $profile->id)
                ->take(3),
        ]);
    }

    /**
     * Cost of buying every vial in a tier separately, keyed by tier code.
     *
     * @return array<string, int>
     */
    protected function retailValues(Collection $tiers, Collection $components, Collection $componentProfiles): array
    {
        $unitPrices = $componentProfiles->mapWithKeys(
            fn (ProductProfile $profile) => [$profile->product_id => Catalog::unitPrice($profile)]
        );

        return $tiers->mapWithKeys(fn (StackTier $tier) => [
            $tier->code => $components->sum(
                fn (StackComponent $component) => ($unitPrices[$component->component_product_id] ?? 0)
                    * $component->base_quantity
                    * $tier->multiplier()
            ),
        ])->all();
    }
}
