<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WebsiteListResource\Pages;
use App\Models\WebsiteListItem;
use App\Support\WebsiteList;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class WebsiteListResource extends Resource
{
    protected static ?string $model = WebsiteListItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?string $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'Website Lists';

    protected static ?string $modelLabel = 'list item';

    protected static ?string $pluralModelLabel = 'Website Lists';

    protected static ?int $navigationSort = 2;

    protected static bool $isGloballySearchable = true;

    protected static ?string $recordTitleAttribute = 'heading';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Which list')
                    ->schema([
                        Forms\Components\Select::make('list_key')
                            ->label('List')
                            ->options(WebsiteList::labels())
                            ->required()
                            ->live()
                            ->disabledOn('edit')
                            ->dehydrated()
                            ->searchable(),
                        Forms\Components\Placeholder::make('where')
                            ->label('Where it appears')
                            ->content(fn (Get $get): string => WebsiteList::definitions()[$get('list_key')]['location_hint'] ?? 'Choose a list to see where its items appear.'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Item')
                    ->description('Plain text only. New items are added to the end of the list; drag rows on the list page to reorder.')
                    ->schema([
                        Forms\Components\TextInput::make('extra')
                            ->label(fn (Get $get): string => static::fieldLabel($get('list_key'), 'extra'))
                            ->visible(fn (Get $get): bool => static::usesField($get('list_key'), 'extra'))
                            ->required(fn (Get $get): bool => static::usesField($get('list_key'), 'extra'))
                            ->maxLength(255),
                        Forms\Components\TextInput::make('heading')
                            ->label(fn (Get $get): string => static::fieldLabel($get('list_key'), 'heading'))
                            ->visible(fn (Get $get): bool => static::usesField($get('list_key'), 'heading'))
                            ->required(fn (Get $get): bool => static::usesField($get('list_key'), 'heading'))
                            ->maxLength(255),
                        Forms\Components\Textarea::make('body')
                            ->label(fn (Get $get): string => static::fieldLabel($get('list_key'), 'body'))
                            ->visible(fn (Get $get): bool => static::usesField($get('list_key'), 'body'))
                            ->required(fn (Get $get): bool => static::usesField($get('list_key'), 'body'))
                            ->rows(4)
                            ->maxLength(5000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        $labels = WebsiteList::labels();

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->orderBy('list_key')->orderBy('sort_order'))
            ->columns([
                Tables\Columns\TextColumn::make('extra')
                    ->label('Figure')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('heading')
                    ->label('Heading')
                    ->searchable()
                    ->wrap()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('body')
                    ->label('Text')
                    ->searchable()
                    ->limit(110)
                    ->wrap()
                    ->tooltip(fn (WebsiteListItem $record): ?string => $record->body),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last changed')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('list_key')
                    ->label('Show one list')
                    ->options($labels),
            ])
            ->groups([
                Group::make('list_key')
                    ->label('List')
                    ->getTitleFromRecordUsing(fn (WebsiteListItem $record): string => $labels[$record->list_key] ?? $record->list_key)
                    ->getDescriptionFromRecordUsing(fn (WebsiteListItem $record): ?string => WebsiteList::definitions()[$record->list_key]['location_hint'] ?? null)
                    ->collapsible(),
            ])
            ->defaultGroup('list_key')
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(50)
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->searchPlaceholder('Search headings or text')
            ->persistSearchInSession();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['heading', 'body'];
    }

    public static function getGlobalSearchResultTitle($record): string
    {
        return $record->heading ?: Str::limit((string) $record->body, 60);
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return ['List' => WebsiteList::labels()[$record->list_key] ?? $record->list_key];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWebsiteListItems::route('/'),
            'create' => Pages\CreateWebsiteListItem::route('/create'),
            'edit' => Pages\EditWebsiteListItem::route('/{record}/edit'),
        ];
    }

    private static function usesField(?string $listKey, string $field): bool
    {
        return $listKey !== null && array_key_exists($field, WebsiteList::definitions()[$listKey]['fields'] ?? []);
    }

    private static function fieldLabel(?string $listKey, string $field): string
    {
        return WebsiteList::definitions()[$listKey]['fields'][$field] ?? ucfirst($field);
    }
}
