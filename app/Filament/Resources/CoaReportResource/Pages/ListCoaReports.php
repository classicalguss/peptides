<?php

namespace App\Filament\Resources\CoaReportResource\Pages;

use App\Filament\Resources\CoaReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCoaReports extends ListRecords
{
    protected static string $resource = CoaReportResource::class;

    public function getSubheading(): ?string
    {
        return 'Batch numbers, analysis results, and certificate PDFs shown on the Lab Reports page and on each product page. Upload a new certificate here whenever a new batch is tested.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Add product batch'),
        ];
    }
}
