<?php

namespace App\Filament\Extensions;

use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lunar\Admin\Support\Extending\ResourceExtension;

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
                ->description('The compounds in this collection and how many vials of each the base size contains. Larger sizes multiply these counts automatically; prices and savings follow the compounds\' own prices. Add, remove or reorder items freely, then save.')
                ->statePath('included_items')
                ->visible(fn (?Model $record): bool => static::isStack($record))
                ->schema([
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
                            Forms\Components\TextInput::make('base_quantity')
                                ->label('Vials in the base collection size')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(100)
                                ->default(1)
                                ->required(),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                ]),

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
