<?php

namespace App\Support;

use App\FieldTypes\Textarea;
use App\FieldTypes\TextList;
use App\Models\Product;
use Lunar\FieldTypes\Dropdown;
use Lunar\FieldTypes\Number;
use Lunar\FieldTypes\Text;
use Lunar\Models\Attribute;
use Lunar\Models\AttributeGroup;
use Lunar\Models\ProductType;

/**
 * The "Website Page" attribute group: the storefront copy and display
 * settings every product carries as standard Lunar attributes. Shared by the
 * migration that introduced them and the catalog seeder so both stay in step.
 */
class WebsitePageAttributes
{
    public const GROUP_HANDLE = 'website_page';

    /**
     * @return array<string, array{name: string, description: string, type: class-string, types: array<int, string>, configuration?: array<string, mixed>}>
     */
    public static function definitions(): array
    {
        $compound = [Product::TYPE_COMPOUND];
        $collection = [Product::TYPE_COLLECTION];
        $both = [Product::TYPE_COMPOUND, Product::TYPE_COLLECTION];

        return [
            'protocol_label' => [
                'name' => 'Small label above collection name',
                'description' => 'Shown in the accent colour above the collection name and on collection cards.',
                'type' => Text::class,
                'types' => $collection,
            ],
            'subtitle' => [
                'name' => 'Short description',
                'description' => 'Shown above the compound name, on its product card, and as its row in every collection\'s What\'s Included table.',
                'type' => Text::class,
                'types' => $compound,
            ],
            'tagline' => [
                'name' => 'Tagline',
                'description' => 'Shown under the collection name, on collection cards, and on the homepage feature.',
                'type' => Text::class,
                'types' => $collection,
            ],
            'dose' => [
                'name' => 'Dose label',
                'description' => 'Short strength label such as "20mg". Shown on product cards and behind the image when a product has no photo.',
                'type' => Text::class,
                'types' => $compound,
            ],
            'summary' => [
                'name' => 'Summary',
                'description' => 'Collections: the main description on the collection page and cards. Compounds: the description search engines and link previews use for the page.',
                'type' => Textarea::class,
                'types' => $both,
            ],
            'overview' => [
                'name' => 'Main compound description',
                'description' => 'The paragraph beside the product image on the compound page.',
                'type' => Textarea::class,
                'types' => $compound,
            ],
            'research_info' => [
                'name' => 'Research background',
                'description' => 'Shown below the buying options. Leave empty to hide the section.',
                'type' => Textarea::class,
                'types' => $compound,
            ],
            'storage' => [
                'name' => 'Storage and handling',
                'description' => 'Shown below the buying options. Leave empty to hide the section.',
                'type' => Textarea::class,
                'types' => $compound,
            ],
            'highlights' => [
                'name' => 'Highlights',
                'description' => 'The checkmark list on the compound page. Type an item and press Enter; drag to reorder.',
                'type' => TextList::class,
                'types' => $compound,
            ],
            'pillars' => [
                'name' => 'Collection highlight pills',
                'description' => 'The short pills shown under the collection description (the first four also appear on collection cards). Type an item and press Enter; drag to reorder.',
                'type' => TextList::class,
                'types' => $collection,
            ],
            'accent' => [
                'name' => 'Accent colour',
                'description' => 'The colour used for this product\'s labels, glow and buttons, matching the vial artwork.',
                'type' => Dropdown::class,
                'types' => $both,
                'configuration' => [
                    'lookups' => collect(config('theme.accents'))
                        ->keys()
                        ->map(fn (string $key) => ['label' => ucfirst($key), 'value' => $key])
                        ->values()
                        ->all(),
                ],
            ],
            'display_order' => [
                'name' => 'Display order',
                'description' => 'Lower numbers appear first on the Shop and Research Collections listings and the homepage.',
                'type' => Number::class,
                'types' => $both,
            ],
        ];
    }

    /**
     * Create the group and attributes if missing and map each attribute to
     * the product types it applies to. Safe to run repeatedly.
     */
    public static function ensure(): void
    {
        $morph = Product::morphName();

        $group = AttributeGroup::query()->firstOrCreate(
            ['attributable_type' => $morph, 'handle' => self::GROUP_HANDLE],
            ['name' => ['en' => 'Website Page'], 'position' => 2],
        );

        $types = ProductType::query()
            ->whereIn('name', [Product::TYPE_COMPOUND, Product::TYPE_COLLECTION])
            ->get()
            ->keyBy('name');

        foreach (array_values(array_keys(self::definitions())) as $position => $handle) {
            $definition = self::definitions()[$handle];

            $attribute = Attribute::query()->firstOrCreate(
                ['attribute_type' => $morph, 'handle' => $handle],
                [
                    'attribute_group_id' => $group->id,
                    'position' => $position + 1,
                    'name' => ['en' => $definition['name']],
                    'description' => ['en' => $definition['description']],
                    'section' => 'main',
                    'type' => $definition['type'],
                    'required' => false,
                    'default_value' => null,
                    'configuration' => $definition['configuration'] ?? [],
                    'system' => false,
                    'searchable' => true,
                    'filterable' => false,
                ],
            );

            foreach ($definition['types'] as $typeName) {
                $types->get($typeName)?->mappedAttributes()->syncWithoutDetaching([$attribute->id]);
            }
        }
    }
}
