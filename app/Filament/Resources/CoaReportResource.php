<?php

namespace App\Filament\Resources;

use App\Filament\Forms\Components\StatusPicker;
use App\Filament\Resources\CoaReportResource\Pages;
use App\Models\CoaReport;
use App\Models\ProductProfile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Lunar\Models\Product;

class CoaReportResource extends Resource
{
    protected static ?string $model = CoaReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'Lab Reports (COAs)';

    protected static ?string $modelLabel = 'lab report';

    protected static ?string $pluralModelLabel = 'Lab Reports';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product')
                    ->description('The product this certificate belongs to, and how it is listed on the Lab Reports page.')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label('Product')
                            ->options(fn (?Model $record): array => static::productOptions($record))
                            ->placeholder(fn (?Model $record): string => static::productOptions($record) === [] ? 'No products without a batch record' : 'Select a product')
                            ->disabled(fn (?Model $record, string $operation): bool => $operation === 'edit' || static::productOptions($record) === [])
                            ->required()
                            ->live()
                            ->dehydrated()
                            ->afterStateUpdated(function (Forms\Set $set, ?string $state): void {
                                if ($state) {
                                    $set('product_label', Product::find($state)?->translateAttribute('name'));
                                }
                            })
                            ->helperText(fn (?Model $record, string $operation): string => match (true) {
                                $operation === 'edit' => 'Each product has one current batch; to change a batch, edit its fields below.',
                                static::productOptions($record) === [] => 'Every compound already has a batch record — edit it from the Lab Reports list. To add a brand-new compound, create it under Catalog → Products first, then come back here. Research collections are never listed: their pages show the batches of the compounds they contain.',
                                default => 'Only compounds without a batch record are listed (each has one current batch). Research collections are never listed: their pages show the batches of the compounds they contain.',
                            }),
                        Forms\Components\TextInput::make('product_label')
                            ->label('Product name shown on the site')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label('Publication status')
                                    ->options([
                                        CoaReport::STATUS_PASS => 'Pass — published',
                                        CoaReport::STATUS_UNPUBLISHED => 'Not published',
                                    ])
                                    ->required()
                                    ->live()
                                    ->helperText('"Pass" shows batch details, purity and the COA. "Not published" shows the status message instead.'),
                                StatusPicker::make('status_label')
                                    ->label('Status shown on the website')
                                    ->placeholder('Set a status')
                                    ->colorField('status_color')
                                    ->colors(CoaReport::COLORS)
                                    ->helperText('Click the status to edit its wording and pick a colour.')
                                    ->visible(fn (Get $get): bool => $get('status') === CoaReport::STATUS_UNPUBLISHED)
                                    ->required(fn (Get $get): bool => $get('status') === CoaReport::STATUS_UNPUBLISHED)
                                    ->rule('max:255'),
                                Forms\Components\Hidden::make('status_color')
                                    ->default(CoaReport::DEFAULT_STATUS_COLOR)
                                    ->rule('in:'.implode(',', array_keys(CoaReport::COLORS))),
                            ])
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('status_note')
                            ->label('Status note (optional)')
                            ->placeholder('e.g. Updated analytical documentation will be published upon completion of testing.')
                            ->rows(2)
                            ->maxLength(1000)
                            ->visible(fn (Get $get): bool => $get('status') === CoaReport::STATUS_UNPUBLISHED)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Current batch and certificate')
                    ->description('Shown on the Lab Reports page and in the Current Batch box on the product page. All fields are required for a passing batch.')
                    ->schema([
                        Forms\Components\TextInput::make('batch_number')
                            ->label('Batch number')
                            ->maxLength(255)
                            ->required(fn (Get $get): bool => $get('status') === CoaReport::STATUS_PASS),
                        Forms\Components\DatePicker::make('tested_on')
                            ->label('Analysis date')
                            ->required(fn (Get $get): bool => $get('status') === CoaReport::STATUS_PASS),
                        Forms\Components\TextInput::make('purity')
                            ->label('HPLC purity result')
                            ->placeholder('e.g. 99.91%')
                            ->maxLength(255)
                            ->required(fn (Get $get): bool => $get('status') === CoaReport::STATUS_PASS),
                        Forms\Components\TextInput::make('lab_name')
                            ->label('Laboratory')
                            ->maxLength(255)
                            ->required(fn (Get $get): bool => $get('status') === CoaReport::STATUS_PASS),
                        Forms\Components\FileUpload::make('pdf_path')
                            ->label('Certificate PDF')
                            ->disk('public')
                            ->directory('coa')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(10240)
                            ->downloadable()
                            ->openable()
                            ->required(fn (Get $get): bool => $get('status') === CoaReport::STATUS_PASS)
                            ->helperText('Uploading a new file replaces the certificate linked from the website.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->orderBy('product_label'))
            ->columns([
                Tables\Columns\TextColumn::make('product_label')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('batch_number')
                    ->label('Batch')
                    ->placeholder('—')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tested_on')
                    ->label('Analysis date')
                    ->date('M j, Y')
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('purity')
                    ->label('HPLC purity')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state, CoaReport $record): string => $record->isPass() ? 'Pass' : $record->statusLabel())
                    ->color(fn (string $state, CoaReport $record): string|array => $record->isPass() ? 'success' : Color::hex($record->statusColor())),
                Tables\Columns\IconColumn::make('pdf_path')
                    ->label('COA attached')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\Action::make('view_coa')
                    ->label('View COA')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (CoaReport $record): ?string => $record->pdfUrl())
                    ->openUrlInNewTab()
                    ->visible(fn (CoaReport $record): bool => filled($record->pdf_path)),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoaReports::route('/'),
            'create' => Pages\CreateCoaReport::route('/create'),
            'edit' => Pages\EditCoaReport::route('/{record}/edit'),
        ];
    }

    /**
     * Products that can be given a batch record: on create, only those
     * without one; on edit, the record's own product.
     *
     * @return array<int, string>
     */
    private static function productOptions(?Model $record): array
    {
        $taken = CoaReport::query()
            ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))
            ->pluck('product_id')
            ->filter()
            ->all();

        // Research collections never carry their own batch; their pages list
        // the batches of the compounds they contain.
        $collections = ProductProfile::query()->where('kind', 'stack')->pluck('product_id')->all();

        return Product::query()
            ->whereNotIn('id', [...$taken, ...$collections])
            ->get()
            ->mapWithKeys(fn (Product $product) => [$product->id => (string) $product->translateAttribute('name')])
            ->sort()
            ->all();
    }
}
