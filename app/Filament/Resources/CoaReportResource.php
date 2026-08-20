<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CoaReportResource\Pages;
use App\Models\CoaReport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CoaReportResource extends Resource
{
    protected static ?string $model = CoaReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'Lab Reports (COAs)';

    protected static ?string $modelLabel = 'lab report';

    protected static ?string $pluralModelLabel = 'Lab Reports';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product')
                    ->description('The product this certificate belongs to, and how it is listed on the Lab Reports page.')
                    ->schema([
                        Forms\Components\TextInput::make('product_label')
                            ->label('Product name shown on the site')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('status')
                            ->label('Testing status')
                            ->options([
                                CoaReport::STATUS_PASS => 'Pass — certificate published',
                                CoaReport::STATUS_TESTING => 'Additional testing in progress',
                                CoaReport::STATUS_PENDING => 'Documentation pending',
                            ])
                            ->required()
                            ->live()
                            ->helperText('Only "Pass" shows batch details, a purity figure, and the COA on the website. The other two show a status message and hide everything else.'),
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
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        CoaReport::STATUS_PASS => 'Pass',
                        CoaReport::STATUS_TESTING => 'Testing in progress',
                        default => 'Documentation pending',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        CoaReport::STATUS_PASS => 'success',
                        CoaReport::STATUS_TESTING => 'warning',
                        default => 'gray',
                    }),
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
            'edit' => Pages\EditCoaReport::route('/{record}/edit'),
        ];
    }
}
