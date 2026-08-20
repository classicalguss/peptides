<?php

namespace App\Filament\Resources;

use App\Models\ProductProfile;
use Filament\GlobalSearch\GlobalSearchResult;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Lunar\Admin\Filament\Resources\ProductResource;

/**
 * Search-only resource: has no pages or navigation of its own. It lets the
 * admin global search box find product page text (descriptions, research
 * wording, highlights, included-items rows), tells the admin which
 * section the text lives in, and links to the product's edit page.
 */
class ProductTextSearchResource extends Resource
{
    protected static ?string $model = ProductProfile::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'product page text';

    protected static ?string $pluralModelLabel = 'Product Page Text';

    /** Column => label of the admin section where it is edited. */
    private const TEXT_COLUMNS = [
        'subtitle' => 'Short description',
        'tagline' => 'Tagline',
        'summary' => 'Main description',
        'overview' => 'Main description',
        'research_info' => 'Research background',
        'storage' => 'Storage and handling',
        'highlights' => 'Highlights list',
        'pillars' => 'Highlight pills',
    ];

    public static function getGlobalSearchResults(string $search): Collection
    {
        $words = array_values(array_filter(preg_split('/\s+/', trim($search)) ?: []));

        if ($words === []) {
            return collect();
        }

        $query = ProductProfile::query()->with(['product', 'components.componentProfile']);

        foreach ($words as $word) {
            $query->where(function ($constraint) use ($word) {
                foreach (array_keys(self::TEXT_COLUMNS) as $column) {
                    $constraint->orWhere($column, 'like', "%{$word}%");
                }

                $constraint->orWhereHas('components.componentProfile', fn ($c) => $c->where('subtitle', 'like', "%{$word}%"));
            });
        }

        return $query->limit(50)->get()->map(fn (ProductProfile $record) => new GlobalSearchResult(
            title: static::getGlobalSearchResultTitle($record),
            url: static::getGlobalSearchResultUrl($record),
            details: [
                'Found in' => static::matchedSections($record, $words) ?: 'Product page text',
                'Type' => $record->isStack() ? 'Research collection' : 'Individual compound',
            ],
        ));
    }

    /**
     * Human labels of the admin sections whose text matches every search word.
     *
     * @param  array<int, string>  $words
     */
    private static function matchedSections(ProductProfile $record, array $words): string
    {
        $sections = [];

        foreach (self::TEXT_COLUMNS as $column => $label) {
            $value = $record->getRawOriginal($column);

            if ($value !== null && static::containsAllWords((string) $value, $words)) {
                $sections[$label] = true;
            }
        }

        $componentText = $record->components->map(fn ($c) => $c->componentProfile?->subtitle)->filter()->implode(' ');

        if ($componentText !== '' && static::containsAllWords($componentText, $words)) {
            $sections["What's Included table (from each compound's Short description)"] = true;
        }

        return implode(', ', array_keys($sections));
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
        return $record->product?->translateAttribute('name') ?? $record->handle;
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return ProductResource::getUrl('edit', ['record' => $record->product_id]);
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
