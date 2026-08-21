<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PolicyResource\Pages;
use App\Models\Policy;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PolicyResource extends Resource
{
    protected static ?string $model = Policy::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'Policies';

    protected static ?string $modelLabel = 'policy';

    protected static ?int $navigationSort = 4;

    protected static bool $isGloballySearchable = true;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Page')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Page title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('slug')
                            ->label('Web address')
                            ->prefix(url('/policies').'/')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Policy text')
                    ->description('Headings, paragraphs and bullet lists are supported. Changes appear on the website as soon as you save.')
                    ->schema([
                        Forms\Components\RichEditor::make('body')
                            ->label('')
                            ->required()
                            ->toolbarButtons(['h2', 'h3', 'bold', 'italic', 'bulletList', 'orderedList', 'link', 'undo', 'redo'])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->orderBy('sort_order'))
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Policy')->searchable(),
                Tables\Columns\TextColumn::make('slug')->label('Web address')->formatStateUsing(fn (string $state): string => '/policies/'.$state),
                Tables\Columns\TextColumn::make('updated_at')->label('Last changed')->since()->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View page')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Policy $record): string => route('policy', $record->slug))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
            ])
            ->paginated(false);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'body'];
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
            'index' => Pages\ListPolicies::route('/'),
            'edit' => Pages\EditPolicy::route('/{record}/edit'),
        ];
    }
}
