<?php

namespace App\Http\Controllers;

use App\Models\Policy;
use App\Models\Product;
use App\Support\Catalog;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

/**
 * XML sitemap for search engines. Built from the live catalog on every
 * request, so published, unpublished and deleted products are reflected
 * without any regeneration step.
 */
class SitemapController extends Controller
{
    /**
     * Public routes that always belong in the sitemap.
     *
     * @var array<int, string>
     */
    protected const STATIC_ROUTES = ['home', 'shop', 'stacks', 'lab-reports', 'about', 'contact'];

    public function __invoke(): Response
    {
        return response()
            ->view('sitemap', ['entries' => $this->entries()])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * @return array<int, array{loc: string, lastmod: ?string}>
     */
    protected function entries(): array
    {
        $pages = collect(self::STATIC_ROUTES)
            ->map(fn (string $name) => $this->entry(route($name), null));

        $products = Catalog::stacks()
            ->concat(Catalog::compounds())
            ->map(fn (Product $product) => $this->entry($product->storefrontUrl(), $product->updated_at))
            ->filter();

        $policies = Policy::query()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Policy $policy) => $this->entry(route('policy', $policy), $policy->updated_at));

        return $pages->concat($products)->concat($policies)->values()->all();
    }

    /**
     * @return array{loc: string, lastmod: ?string}|null
     */
    protected function entry(?string $url, ?Carbon $updatedAt): ?array
    {
        if ($url === null) {
            return null;
        }

        return [
            'loc' => canonical_url($url),
            'lastmod' => $updatedAt?->toAtomString(),
        ];
    }
}
