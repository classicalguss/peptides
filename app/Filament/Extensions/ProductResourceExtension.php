<?php

namespace App\Filament\Extensions;

use App\Models\ProductProfile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lunar\Admin\Support\Extending\ResourceExtension;

class ProductResourceExtension extends ResourceExtension
{
    public function extendForm(Form $form): Form
    {
        return $form->schema([
            ...$form->getComponents(),
            Forms\Components\Section::make('Website Page Text')
                ->description('Edit the descriptions, research wording, benefits, and FAQs shown on this product’s storefront page. Name, price, images, and stock remain in the standard product controls.')
                ->statePath('page_text')
                ->visible(fn (?Model $record): bool => (bool) static::profile($record))
                ->schema([
                    Forms\Components\Placeholder::make('page_type')
                        ->label('Storefront page type')
                        ->content(fn (?Model $record): string => static::isStack($record) ? 'Research collection' : 'Individual compound'),

                    Forms\Components\Section::make('Top of product page')
                        ->description('The short labels and main paragraphs beside the product image.')
                        ->schema([
                            Forms\Components\TextInput::make('subtitle')
                                ->label('Short description')
                                ->helperText('Shown above the product name, and as this product\'s row in every collection\'s What\'s Included table.')
                                ->maxLength(255)
                                ->visible(fn (?Model $record): bool => ! static::isStack($record))
                                ->dehydratedWhenHidden(false),
                            Forms\Components\TextInput::make('protocol_label')
                                ->label('Small label above collection name')
                                ->maxLength(255)
                                ->visible(fn (?Model $record): bool => static::isStack($record))
                                ->dehydratedWhenHidden(false),
                            Forms\Components\TextInput::make('tagline')
                                ->label('Tagline')
                                ->maxLength(255)
                                ->visible(fn (?Model $record): bool => static::isStack($record))
                                ->dehydratedWhenHidden(false),
                            Forms\Components\Textarea::make('summary')
                                ->label(fn (?Model $record): string => static::isStack($record)
                                    ? 'Main collection description'
                                    : 'Search result description')
                                ->helperText(fn (?Model $record): string => static::isStack($record)
                                    ? 'Shown on the collection page, on collection cards, and in search results.'
                                    : 'Used by search engines and link previews for this product\'s page.')
                                ->rows(4)
                                ->maxLength(5000)
                                ->columnSpanFull(),
                            Forms\Components\Textarea::make('overview')
                                ->label('Main compound description')
                                ->rows(4)
                                ->maxLength(5000)
                                ->visible(fn (?Model $record): bool => ! static::isStack($record))
                                ->dehydratedWhenHidden(false)
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
                                ->visible(fn (?Model $record): bool => ! static::isStack($record))
                                ->dehydratedWhenHidden(false),
                            Forms\Components\Textarea::make('storage')
                                ->label('Storage and handling')
                                ->rows(5)
                                ->maxLength(10000)
                                ->visible(fn (?Model $record): bool => ! static::isStack($record))
                                ->dehydratedWhenHidden(false)
                                ->columnSpanFull(),
                            Forms\Components\Repeater::make('highlights')
                                ->label('Highlights')
                                ->simple(Forms\Components\TextInput::make('text')->required()->maxLength(1000))
                                ->visible(fn (?Model $record): bool => ! static::isStack($record))
                                ->dehydratedWhenHidden(false)
                                ->columnSpanFull(),
                            Forms\Components\Repeater::make('pillars')
                                ->label('Collection highlight pills')
                                ->simple(Forms\Components\TextInput::make('text')->required()->maxLength(255))
                                ->visible(fn (?Model $record): bool => static::isStack($record))
                                ->dehydratedWhenHidden(false)
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
                ]),

            Forms\Components\Section::make('What\'s Included table')
                ->description('The rows of the included-items table on this collection page. Vial counts per tier are calculated automatically from the base count.')
                ->statePath('included_items')
                ->visible(fn (?Model $record): bool => static::isStack($record))
                ->schema([
                    Forms\Components\Repeater::make('components')
                        ->label('Included items')
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->schema([
                            Forms\Components\Hidden::make('id'),
                            Forms\Components\TextInput::make('name')
                                ->label('Item')
                                ->disabled()
                                ->dehydrated(false),
                            Forms\Components\TextInput::make('short_description')
                                ->label('Short description (edited on the compound\'s own page)')
                                ->disabled()
                                ->dehydrated(false),
                            Forms\Components\TextInput::make('base_quantity')
                                ->label('Vials in the base collection size')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(100)
                                ->required(),
                        ])
                        ->columns(3)
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
                    ->state(function (Model $record): string {
                        $profile = static::profile($record);

                        return (string) ($profile?->isStack() ? $profile->summary : $profile?->overview);
                    })
                    ->placeholder('No storefront page text')
                    ->limit(80)
                    ->wrap()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('profile', function (Builder $profileQuery) use ($search) {
                            $profileQuery->where(function (Builder $textQuery) use ($search) {
                                foreach (static::searchableColumns() as $column) {
                                    $textQuery->orWhere($column, 'like', "%{$search}%");
                                }
                            });
                        });
                    }),
            ])
            ->searchPlaceholder('Search product name or website wording');
    }

    /**
     * @return array<int, string>
     */
    public static function searchableColumns(): array
    {
        return [
            'subtitle',
            'tagline',
            'protocol_label',
            'summary',
            'overview',
            'research_info',
            'storage',
            'highlights',
            'pillars',
            'faq',
        ];
    }

    private static function isStack(?Model $record): bool
    {
        return (bool) static::profile($record)?->isStack();
    }

    private static function profile(?Model $record): ?ProductProfile
    {
        if ($record instanceof ProductProfile) {
            return $record;
        }

        return $record?->getRelationValue('profile');
    }
}
