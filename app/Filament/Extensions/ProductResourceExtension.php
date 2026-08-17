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
                                ->label('Small label above product name')
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
                                ->label('Main collection description')
                                ->rows(4)
                                ->maxLength(5000)
                                ->visible(fn (?Model $record): bool => static::isStack($record))
                                ->dehydratedWhenHidden(false)
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
                            Forms\Components\Textarea::make('dosage')
                                ->label('Reference range in literature')
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
                            Forms\Components\Repeater::make('benefits')
                                ->label('Benefit boxes')
                                ->schema([
                                    Forms\Components\TextInput::make('title')->required()->maxLength(255),
                                    Forms\Components\TextInput::make('detail')->maxLength(255),
                                ])
                                ->columns(2)
                                ->visible(fn (?Model $record): bool => ! static::isStack($record))
                                ->dehydratedWhenHidden(false)
                                ->columnSpanFull(),
                            Forms\Components\Repeater::make('stack_benefits')
                                ->label('Key benefits list')
                                ->simple(Forms\Components\TextInput::make('text')->required()->maxLength(1000))
                                ->visible(fn (?Model $record): bool => static::isStack($record))
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
                            Forms\Components\Repeater::make('audience')
                                ->label('Who it is for list')
                                ->simple(Forms\Components\TextInput::make('text')->required()->maxLength(1000))
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
            'dosage',
            'storage',
            'benefits',
            'highlights',
            'pillars',
            'audience',
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
