<?php

namespace App\Filament\Extensions;

use App\Filament\Support\Concerns\RoundsEnteredPrices;
use App\Filament\Support\Pages\ManageProductPricing;
use App\Filament\Support\RelationManagers\PriceRelationManager;
use App\Models\Product;
use App\Models\StackTier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lunar\Admin\Filament\Resources\ProductResource\Pages\ManageProductPricing as LunarManageProductPricing;
use Lunar\Admin\Support\Extending\ResourceExtension;
use Lunar\Admin\Support\RelationManagers\PriceRelationManager as LunarPriceRelationManager;

/**
 * Adds the Research Collection controls (contents and size names) to Lunar's
 * product edit screen. Page copy itself is edited through the "Website Page"
 * attribute group, which Lunar renders with the rest of the attributes.
 */
class ProductResourceExtension extends ResourceExtension
{
    public function extendForm(Form $form): Form
    {
        return $form->schema([
            ...$form->getComponents(),

            Forms\Components\Section::make('What\'s Included table')
                ->description('The compounds in this collection and how many vials of each every collection size contains. A count of 0 shows the compound as not included in that size. Prices and savings follow the compounds\' own prices. Add, remove or reorder items freely, then save.')
                ->statePath('included_items')
                ->visible(fn (?Model $record): bool => static::isStack($record))
                ->schema(function (?Model $record): array {
                    $tierInputs = static::tierQuantityInputs($record);

                    return [
                        Forms\Components\Repeater::make('components')
                            ->label('Included items')
                            ->addActionLabel('Add compound')
                            ->reorderableWithButtons()
                            ->itemLabel(fn (array $state): ?string => static::compoundOptions()[$state['component_product_id'] ?? null] ?? 'New item')
                            ->schema([
                                Forms\Components\Hidden::make('id'),
                                Forms\Components\Select::make('component_product_id')
                                    ->label('Compound')
                                    ->options(fn (): array => static::compoundOptions())
                                    ->searchable()
                                    ->required()
                                    ->distinct()
                                    ->live()
                                    ->helperText('Its description comes from the compound\'s own Short description.'),
                                Forms\Components\Fieldset::make('Vials per collection size')
                                    ->schema($tierInputs)
                                    ->columns(max(1, min(3, count($tierInputs)))),
                            ])
                            ->columns(1)
                            ->columnSpanFull(),
                    ];
                }),

            Forms\Components\Section::make('Collection Sizes')
                ->description('The names shown for each quantity tier of this collection (e.g. "HP" + "Core"). Pricing and stock stay in the standard variant controls below.')
                ->statePath('collection_sizes')
                ->visible(fn (?Model $record): bool => static::isStack($record))
                ->schema([
                    Forms\Components\Repeater::make('tiers')
                        ->label('Sizes')
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->schema([
                            Forms\Components\Hidden::make('id'),
                            Forms\Components\TextInput::make('code')
                                ->label('Short code')
                                ->maxLength(10)
                                ->required(),
                            Forms\Components\TextInput::make('label')
                                ->label('Name')
                                ->maxLength(30)
                                ->required(),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    /**
     * Swap in our pricing page, which keeps quantity break prices at the
     * exact cents entered. See {@see RoundsEnteredPrices}.
     *
     * @param  array<string, mixed>  $pages
     * @return array<string, mixed>
     */
    public function extendPages(array $pages): array
    {
        $pages['pricing'] = ManageProductPricing::route('/{record}/pricing');

        return $pages;
    }

    /**
     * @param  array<int, class-string>  $pages
     * @return array<int, class-string>
     */
    public function extendSubNavigation(array $pages): array
    {
        return array_map(
            fn (string $page): string => $page === LunarManageProductPricing::class
                ? ManageProductPricing::class
                : $page,
            $pages
        );
    }

    /**
     * @param  array<int, mixed>  $managers
     * @return array<int, mixed>
     */
    public function getRelations(array $managers): array
    {
        return array_map(
            fn (mixed $manager): mixed => $manager === LunarPriceRelationManager::class
                ? PriceRelationManager::class
                : $manager,
            $managers
        );
    }

    public function extendTable(Table $table): Table
    {
        return $table
            ->columns([
                ...array_values($table->getColumns()),
                Tables\Columns\TextColumn::make('website_page_text')
                    ->label('Website description')
                    ->state(fn (Model $record): string => (string) ($record instanceof Product
                        ? ($record->isStack() ? $record->summary : $record->overview)
                        : ''))
                    ->placeholder('No website page text')
                    ->limit(80)
                    ->wrap()
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->where('attribute_data', 'like', '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%')),
            ])
            ->searchPlaceholder('Search product name or website wording');
    }

    /**
     * One vial-count input per collection size, so every size states its
     * contents explicitly (sizes no longer scale the base count).
     *
     * @return array<int, Forms\Components\TextInput>
     */
    private static function tierQuantityInputs(?Model $record): array
    {
        if (! static::isStack($record)) {
            return [];
        }

        return StackTier::query()
            ->where('product_id', $record->getKey())
            ->orderBy('position')
            ->get()
            ->map(fn (StackTier $tier): Forms\Components\TextInput => Forms\Components\TextInput::make("quantities.{$tier->id}")
                ->label("{$tier->code} — {$tier->label}")
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->default(1)
                ->required())
            ->all();
    }

    /**
     * Every individual compound (and supply) that can be part of a
     * collection, keyed by product id.
     *
     * @return array<int, string>
     */
    private static function compoundOptions(): array
    {
        static $options = null;

        return $options ??= Product::query()
            ->where('product_type_id', Product::typeId(Product::TYPE_COMPOUND))
            ->get()
            ->mapWithKeys(fn (Product $product) => [$product->id => (string) $product->translateAttribute('name')])
            ->sort()
            ->all();
    }

    private static function isStack(?Model $record): bool
    {
        return $record instanceof Product && $record->isStack();
    }
}
