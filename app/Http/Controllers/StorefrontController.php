<?php

namespace App\Http\Controllers;

use App\Models\CoaReport;
use App\Models\Product;
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
        $featured = $stacks->first(fn (Product $stack) => $stack->slug() === 'healing-stack') ?? $stacks->first();

        return view('storefront.home', [
            'stacks' => $stacks,
            'compounds' => Catalog::compounds(includeSupplies: false)->take(8),
            'compoundCount' => Catalog::compounds()->count(),
            'featured' => $featured,
            'featuredFrom' => $featured ? Catalog::fromPrice($featured) : 0,
        ]);
    }

    /**
     * Individual compounds and supplies. Research Collections have their own page.
     */
    public function shop(Request $request): View
    {
        $category = (string) $request->string('category');
        $sort = $request->string('sort')->toString() ?: 'featured';

        $all = Catalog::compounds();
        $products = $all;

        if ($category !== '' && $category !== 'all') {
            $ids = Catalog::productIdsInCategory($category);
            $products = $products->whereIn('id', $ids)->values();
        }

        return view('storefront.shop', [
            'products' => $this->sortProducts($products, $sort),
            'totalCount' => $all->count(),
            'categories' => Catalog::categoriesFor($all),
            'activeCategory' => $category === '' ? 'all' : $category,
            'sort' => $sort,
        ]);
    }

    /**
     * Research Collection index.
     */
    public function stacks(Request $request): View
    {
        $category = (string) $request->string('category');
        $sort = $request->string('sort')->toString() ?: 'featured';

        $all = Catalog::stacks();
        $products = $all;

        if ($category !== '' && $category !== 'all') {
            $ids = Catalog::productIdsInCategory($category);
            $products = $products->whereIn('id', $ids)->values();
        }

        $productIds = $all->pluck('id');

        return view('storefront.stacks', [
            'products' => $this->sortProducts($products, $sort),
            'totalCount' => $all->count(),
            'categories' => Catalog::categoriesFor($all),
            'activeCategory' => $category === '' ? 'all' : $category,
            'sort' => $sort,
            'tiers' => StackTier::whereIn('product_id', $productIds)
                ->with('variant.prices')
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
     * @param  Collection<int, Product>  $products
     * @return Collection<int, Product>
     */
    protected function sortProducts(Collection $products, string $sort): Collection
    {
        return match ($sort) {
            'price-asc' => $products->sortBy(fn (Product $p) => Catalog::fromPrice($p))->values(),
            'price-desc' => $products->sortByDesc(fn (Product $p) => Catalog::fromPrice($p))->values(),
            'name' => $products->sortBy(fn (Product $p) => $p->translateAttribute('name'))->values(),
            default => $products->values(),
        };
    }

    public function compound(Request $request, string $slug): View
    {
        $product = Catalog::findBySlug($slug);

        abort_if(! $product || $product->isStack(), 404);

        $variant = $product->variants->first();
        $images = $product->getMedia('images');
        $activeIndex = min(max((int) $request->integer('image'), 0), max($images->count() - 1, 0));

        $tiers = $variant
            ? $variant->prices->sortBy('min_quantity')->values()
            : collect();

        return view('storefront.compound', [
            'product' => $product,
            'variant' => $variant,
            'images' => $images,
            'activeIndex' => $activeIndex,
            'activeImage' => $images->get($activeIndex),
            'priceTiers' => $tiers,
            'unitPrice' => Catalog::unitPrice($product),
            'coa' => CoaReport::where('product_id', $product->id)->first(),
            'usedInStacks' => $this->stacksContaining($product),
            'related' => Catalog::compounds(includeSupplies: false)
                ->reject(fn (Product $item) => $item->id === $product->id)
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
     * @return Collection<int, Product>
     */
    protected function stacksContaining(Product $product): Collection
    {
        $stackIds = StackComponent::where('component_product_id', $product->id)
            ->pluck('stack_product_id');

        return Catalog::stacks()->whereIn('id', $stackIds)->values();
    }

    public function stack(Request $request, string $slug): View
    {
        $product = Catalog::findBySlug($slug);

        abort_if(! $product || ! $product->isStack(), 404);

        $images = $product->getMedia('images');
        $activeIndex = min(max((int) $request->integer('image'), 0), max($images->count() - 1, 0));

        $tiers = StackTier::where('product_id', $product->id)
            ->with('variant.prices')
            ->orderBy('position')
            ->get();

        $components = StackComponent::where('stack_product_id', $product->id)
            ->orderBy('position')
            ->with('component.urls')
            ->get();

        $componentProducts = Catalog::componentProducts($components);

        return view('storefront.stack', [
            'product' => $product,
            'slug' => $slug,
            'images' => $images,
            'activeIndex' => $activeIndex,
            'activeImage' => $images->get($activeIndex),
            'tiers' => $tiers,
            'components' => $components,
            'componentProducts' => $componentProducts,
            'retailValues' => $retailValues = Catalog::retailValues($tiers, $components, $componentProducts),
            'savings' => Catalog::savings($tiers, $retailValues),
            'coas' => CoaReport::whereIn('product_id', $components->pluck('component_product_id'))
                ->orderBy('product_label')
                ->get(),
            'otherStacks' => Catalog::stacks()
                ->reject(fn (Product $item) => $item->id === $product->id)
                ->take(3),
        ]);
    }
}
