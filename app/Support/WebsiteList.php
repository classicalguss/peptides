<?php

namespace App\Support;

use App\Models\WebsiteListItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-managed repeating content (FAQ entries, trust-bar promises, process
 * steps, checkmark bullets). Each list is defined in config/website-lists.php
 * and its items live in website_list_items, where the admin can add, remove
 * and reorder them.
 */
class WebsiteList
{
    private static ?bool $tableAvailable = null;

    /**
     * @return Collection<int, object{heading: ?string, body: ?string, extra: ?string}>
     */
    public static function items(string $key): Collection
    {
        if (! (self::$tableAvailable ??= Schema::hasTable('website_list_items'))) {
            return collect(self::definitions()[$key]['defaults'] ?? [])
                ->map(fn (array $item) => (object) [
                    'heading' => $item['heading'] ?? null,
                    'body' => $item['body'] ?? null,
                    'extra' => $item['extra'] ?? null,
                ]);
        }

        $all = Cache::rememberForever(
            'website-list.items',
            fn () => WebsiteListItem::query()
                ->orderBy('list_key')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['list_key', 'heading', 'body', 'extra'])
                ->groupBy('list_key')
                ->map(fn (Collection $items) => $items->map(fn (WebsiteListItem $item) => (object) [
                    'heading' => $item->heading,
                    'body' => $item->body,
                    'extra' => $item->extra,
                ])->values())
                ->all(),
        );

        return collect($all[$key] ?? []);
    }

    /** @return array<string, array<string, mixed>> */
    public static function definitions(): array
    {
        return config('website-lists', []);
    }

    /** @return array<string, string> list key => "Page — Section" label */
    public static function labels(): array
    {
        return collect(self::definitions())
            ->map(fn (array $definition) => $definition['page'].' — '.$definition['label'])
            ->all();
    }
}
