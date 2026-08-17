<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductPageTextResource\Pages;
use App\Models\ProductProfile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Lunar\Admin\Filament\Resources\ProductResource;

class ProductPageTextResource extends Resource
{
    protected static ?string $model = ProductProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'Product Page Text';

    protected static ?string $modelLabel = 'product page';

    protected static ?string $pluralModelLabel = 'Product Page Text';

    protected static ?int $navigationSort = 2;

    protected static bool $isGloballySearchable = false;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Product page')
                ->description('This screen changes wording only. Use Catalog → Products for the product name, price, images, stock, and categories.')
                ->schema([
                    Forms\Components\Placeholder::make('product_name')
                        ->label('Product')
                        ->content(fn (?ProductProfile $record): string => $record?->product?->translateAttribute('name') ?? 'Product'),
                    Forms\Components\Placeholder::make('page_type')
                        ->label('Page type')
                        ->content(fn (?ProductProfile $record): string => $record?->isStack() ? 'Research collection' : 'Individual compound'),
                ])
                ->columns(2),

            Forms\Components\Section::make('Top of product page')
                ->description('The short labels and main paragraphs beside the product image.')
                ->schema([
                    Forms\Components\TextInput::make('subtitle')
                        ->label('Small label above product name')
                        ->maxLength(255)
                        ->visible(fn (?ProductProfile $record): bool => ! $record?->isStack()),
                    Forms\Components\TextInput::make('protocol_label')
                        ->label('Small label above collection name')
                        ->maxLength(255)
                        ->visible(fn (?ProductProfile $record): bool => (bool) $record?->isStack()),
                    Forms\Components\TextInput::make('tagline')
                        ->label('Tagline')
                        ->maxLength(255)
                        ->visible(fn (?ProductProfile $record): bool => (bool) $record?->isStack()),
                    Forms\Components\Textarea::make('summary')
                        ->label('Main collection description')
                        ->rows(4)
                        ->maxLength(5000)
                        ->visible(fn (?ProductProfile $record): bool => (bool) $record?->isStack())
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('overview')
                        ->label('Main compound description')
                        ->rows(4)
                        ->maxLength(5000)
                        ->visible(fn (?ProductProfile $record): bool => ! $record?->isStack())
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Product claims and supporting text')
                ->description('These fields are common compliance-review targets. They appear below the buying options.')
                ->schema([
                    Forms\Components\Textarea::make('research_info')
                        ->label('Research background')
                        ->rows(5)
                        ->maxLength(10000)
                        ->visible(fn (?ProductProfile $record): bool => ! $record?->isStack()),
                    Forms\Components\Textarea::make('dosage')
                        ->label('Reference range in literature')
                        ->rows(5)
                        ->maxLength(10000)
                        ->visible(fn (?ProductProfile $record): bool => ! $record?->isStack()),
                    Forms\Components\Textarea::make('storage')
                        ->label('Storage and handling')
                        ->rows(5)
                        ->maxLength(10000)
                        ->visible(fn (?ProductProfile $record): bool => ! $record?->isStack())
                        ->columnSpanFull(),
                    Forms\Components\Repeater::make('benefits')
                        ->label(fn (?ProductProfile $record): string => $record?->isStack() ? 'Benefits' : 'Benefit boxes')
                        ->schema([
                            Forms\Components\TextInput::make('title')->required()->maxLength(255),
                            Forms\Components\TextInput::make('detail')->maxLength(255),
                        ])
                        ->columns(2)
                        ->visible(fn (?ProductProfile $record): bool => ! $record?->isStack())
                        ->columnSpanFull(),
                    Forms\Components\Repeater::make('stack_benefits')
                        ->label('Key benefits list')
                        ->simple(Forms\Components\TextInput::make('text')->required()->maxLength(1000))
                        ->visible(fn (?ProductProfile $record): bool => (bool) $record?->isStack())
                        ->columnSpanFull(),
                    Forms\Components\Repeater::make('highlights')
                        ->label('Highlights')
                        ->simple(Forms\Components\TextInput::make('text')->required()->maxLength(1000))
                        ->visible(fn (?ProductProfile $record): bool => ! $record?->isStack())
                        ->columnSpanFull(),
                    Forms\Components\Repeater::make('pillars')
                        ->label('Collection highlight pills')
                        ->simple(Forms\Components\TextInput::make('text')->required()->maxLength(255))
                        ->visible(fn (?ProductProfile $record): bool => (bool) $record?->isStack())
                        ->columnSpanFull(),
                    Forms\Components\Repeater::make('audience')
                        ->label('Who it is for list')
                        ->simple(Forms\Components\TextInput::make('text')->required()->maxLength(1000))
                        ->visible(fn (?ProductProfile $record): bool => (bool) $record?->isStack())
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Frequently asked questions')
                ->schema([
                    Forms\Components\Repeater::make('faq')
                        ->label('Questions and answers')
                        ->schema([
                            Forms\Components\TextInput::make('q')
                                ->label('Question')
                                ->required()
                                ->maxLength(1000)
                                ->columnSpanFull(),
                            Forms\Components\Textarea::make('a')
                                ->label('Answer')
                                ->required()
                                ->rows(4)
                                ->maxLength(10000)
                                ->columnSpanFull(),
                        ])
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('product')->orderBy('kind')->orderBy('position'))
            ->columns([
                Tables\Columns\TextColumn::make('product_name')
                    ->label('Product')
                    ->state(fn (ProductProfile $record): string => $record->product?->translateAttribute('name') ?? $record->handle)
                    ->description(fn (ProductProfile $record): string => $record->isStack() ? 'Research collection page' : 'Individual compound page')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('product', fn (Builder $productQuery) => $productQuery->where('attribute_data', 'like', "%{$search}%"));
                    })
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('handle', $direction)),
                Tables\Columns\TextColumn::make('kind')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => $state === ProductProfile::KIND_STACK ? 'Research collection' : 'Compound')
                    ->badge(),
                Tables\Columns\TextColumn::make('summary')
                    ->label('Current main wording')
                    ->state(fn (ProductProfile $record): string => $record->isStack() ? (string) $record->summary : (string) $record->overview)
                    ->limit(120)
                    ->wrap()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $textQuery) use ($search) {
                            foreach (['subtitle', 'tagline', 'protocol_label', 'summary', 'overview', 'research_info', 'dosage', 'storage', 'benefits', 'highlights', 'pillars', 'audience', 'faq'] as $column) {
                                $textQuery->orWhere($column, 'like', "%{$search}%");
                            }
                        });
                    }),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last changed')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kind')
                    ->label('Product page type')
                    ->options([
                        ProductProfile::KIND_STACK => 'Research collections',
                        ProductProfile::KIND_COMPOUND => 'Individual compounds',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (ProductProfile $record): string => route($record->isStack() ? 'stack' : 'compound', $record->handle))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make()->label('Edit text'),
            ])
            ->searchPlaceholder('Search product name or any wording on its page')
            ->searchDebounce('250ms')
            ->persistSearchInSession();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductPageTexts::route('/'),
            'edit' => Pages\EditProductPageText::route('/{record}/edit'),
        ];
    }

    public static function productCatalogUrl(ProductProfile $record): string
    {
        return ProductResource::getUrl('edit', ['record' => $record->product_id]);
    }
}
