<?php

namespace App\Filament\Resources;

use App\Models\Product;
use App\Support\WebsitePageAttributes;
use Filament\GlobalSearch\GlobalSearchResult;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Lunar\Admin\Filament\Resources\ProductResource;

/**
 * Search-only resource: has no pages or navigation of its own. It lets the
 * admin global search box find product page text (descriptions, research
 * wording, highlights, included-items rows), tells the admin which
 * "Website Page" field the text lives in, and links to the product's edit page.
 */
class ProductTextSearchResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'product page text';

    protected static ?string $pluralModelLabel = 'Product Page Text';

    public static function getGlobalSearchResults(string $search): Collection
    {
        $words = array_values(array_filter(preg_split('/\s+/', trim($search)) ?: []));

        if ($words === []) {
            return collect();
        }

        $products = Product::query()->with('components.component')->get();

        return $products
            ->map(fn (Product $product) => [$product, static::matchedFields($product, $words)])
            ->filter(fn (array $pair) => $pair[1] !== [])
            ->take(50)
            ->map(fn (array $pair) => new GlobalSearchResult(
                title: static::getGlobalSearchResultTitle($pair[0]),
                url: static::getGlobalSearchResultUrl($pair[0]),
                details: [
                    'Found in' => implode(', ', $pair[1]),
                    'Type' => $pair[0]->isStack() ? 'Research collection' : 'Individual compound',
                ],
            ))
            ->values();
    }

    /**
     * Labels of the Website Page fields whose text contains every search word.
     *
     * @param  array<int, string>  $words
     * @return array<int, string>
     */
    private static function matchedFields(Product $product, array $words): array
    {
        $fields = [];

        foreach (WebsitePageAttributes::definitions() as $handle => $definition) {
            $list = $product->pageList($handle);
            $text = $list !== [] ? implode(' ', $list) : (string) $product->pageText($handle);

            if ($text !== '' && static::containsAllWords($text, $words)) {
                $fields[] = $definition['name'];
            }
        }

        $componentText = $product->components
            ->map(fn ($component) => $component->component?->subtitle)
            ->filter()
            ->implode(' ');

        if ($componentText !== '' && static::containsAllWords($componentText, $words)) {
            $fields[] = "What's Included table (from each compound's Short description)";
        }

        return $fields;
    }

    /**
     * @param  array<int, string>  $words
     */
    private static function containsAllWords(string $haystack, array $words): bool
    {
        foreach ($words as $word) {
            if (stripos($haystack, $word) === false) {
                return false;
            }
        }

        return true;
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return (string) ($record->translateAttribute('name') ?? $record->slug() ?? 'Product');
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return ProductResource::getUrl('edit', ['record' => $record->getKey()]);
    }

    public static function getPages(): array
    {
        return [];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
