<?php

namespace App\Support;

use App\Models\Policy;
use App\Models\WebsiteListItem;
use App\Models\WebsiteText as WebsiteTextModel;
use Illuminate\Support\Facades\Cache;

/**
 * Registers the admin-editable website content a fresh database needs:
 * every text key from config/website-text.php, the default items of each
 * list in config/website-lists.php, and the five policy pages. Safe to run
 * repeatedly — existing values are never overwritten, only metadata and the
 * "Restore Original Text" defaults are refreshed from config.
 */
class WebsiteContent
{
    /** @var array<int, array{slug: string, title: string}> */
    public const POLICIES = [
        ['slug' => 'terms-and-conditions', 'title' => 'Terms & Conditions'],
        ['slug' => 'privacy-policy', 'title' => 'Privacy Policy'],
        ['slug' => 'shipping-policy', 'title' => 'Shipping Policy'],
        ['slug' => 'return-and-refund-policy', 'title' => 'Return & Refund Policy'],
        ['slug' => 'research-use-only-policy', 'title' => 'Research Use Only (RUO) Policy'],
    ];

    public static function sync(): void
    {
        static::syncTexts();
        static::syncLists();
        static::syncPolicies();

        Cache::forget('website-text.values');
        Cache::forget('website-list.items');
        Cache::forget('policies.nav');
    }

    protected static function syncTexts(): void
    {
        $existing = WebsiteTextModel::query()->get()->keyBy('key');

        foreach (WebsiteText::definitions() as $key => $definition) {
            $metadata = [
                'page' => $definition['page'],
                'section' => $definition['section'],
                'label' => $definition['label'],
                'location_hint' => $definition['location_hint'] ?? null,
                'route_name' => $definition['route_name'] ?? null,
                'default_value' => $definition['default'],
                'sort_order' => $definition['sort_order'] ?? 0,
            ];

            if ($text = $existing->get($key)) {
                $text->forceFill($metadata)->save();
            } else {
                WebsiteTextModel::query()->create([...$metadata, 'key' => $key, 'value' => $definition['default']]);
            }
        }
    }

    protected static function syncLists(): void
    {
        $present = WebsiteListItem::query()->distinct()->pluck('list_key')->all();

        foreach (WebsiteList::definitions() as $listKey => $definition) {
            if (in_array($listKey, $present, true)) {
                continue;
            }

            foreach (array_values($definition['defaults'] ?? []) as $index => $item) {
                WebsiteListItem::query()->create([
                    'list_key' => $listKey,
                    'sort_order' => $index + 1,
                    'heading' => $item['heading'] ?? null,
                    'body' => $item['body'] ?? null,
                    'extra' => $item['extra'] ?? null,
                ]);
            }
        }
    }

    protected static function syncPolicies(): void
    {
        foreach (self::POLICIES as $index => $policy) {
            if (Policy::query()->where('slug', $policy['slug'])->exists()) {
                continue;
            }

            Policy::query()->create([
                ...$policy,
                'body' => file_get_contents(database_path("data/policies/{$policy['slug']}.html")),
                'sort_order' => $index + 1,
            ]);
        }
    }
}
