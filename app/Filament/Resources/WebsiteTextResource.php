<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WebsiteTextResource\Pages;
use App\Models\WebsiteText;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WebsiteTextResource extends Resource
{
    protected static ?string $model = WebsiteText::class;

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    protected static ?string $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'Website Text';

    protected static ?string $modelLabel = 'website text item';

    protected static ?string $pluralModelLabel = 'Website Text';

    protected static ?int $navigationSort = 1;

    protected static bool $isGloballySearchable = true;

    protected static ?string $recordTitleAttribute = 'label';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Where this text appears')
                    ->description('Use these details to confirm you are editing the right words. The page layout and design cannot be changed here.')
                    ->schema([
                        Forms\Components\TextInput::make('page')
                            ->label('Page')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('section')
                            ->label('Section on page')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('label')
                            ->label('What this text controls')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('location_hint')
                            ->label('How to find it')
                            ->disabled()
                            ->dehydrated(false)
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Edit the wording')
                    ->description('Enter plain text only. Saving changes the words, but never moves or redesigns the section.')
                    ->schema([
                        Forms\Components\Textarea::make('value')
                            ->label('Website text')
                            ->required()
                            ->rows(7)
                            ->maxLength(10000)
                            ->helperText('Tip: use the Preview Page button after saving to check the result on the website.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->orderBy('page')->orderBy('sort_order'))
            ->columns([
                Tables\Columns\TextColumn::make('page')
                    ->label('Page')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('section')
                    ->label('Section')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('label')
                    ->label('Text item')
                    ->description(fn (WebsiteText $record): ?string => $record->location_hint)
                    ->searchable()
                    ->wrap()
                    ->sortable(),
                Tables\Columns\TextColumn::make('value')
                    ->label('Current wording')
                    ->searchable()
                    ->limit(110)
                    ->wrap()
                    ->tooltip(fn (WebsiteText $record): string => $record->value),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last changed')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('page')
                    ->label('Show one page')
                    ->options(fn (): array => WebsiteText::query()
                        ->orderBy('page')
                        ->distinct()
                        ->pluck('page', 'page')
                        ->all()),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (WebsiteText $record): ?string => $record->route_name ? route($record->route_name) : null)
                    ->openUrlInNewTab()
                    ->visible(fn (WebsiteText $record): bool => filled($record->route_name)),
                Tables\Actions\EditAction::make()
                    ->label('Edit text'),
            ])
            ->groups([
                Group::make('page')->label('Page'),
            ])
            ->defaultGroup('page')
            ->searchPlaceholder('Search page, section, item name, or current wording')
            ->searchDebounce('250ms')
            ->persistSearchInSession();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['label', 'value', 'page', 'section', 'location_hint'];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Page' => $record->page,
            'Section' => $record->section,
        ];
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
            'index' => Pages\ListWebsiteTexts::route('/'),
            'edit' => Pages\EditWebsiteText::route('/{record}/edit'),
        ];
    }
}
