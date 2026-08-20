<?php

namespace App\Filament\Resources\CoaReportResource\Pages;

use App\Filament\Resources\CoaReportResource;
use Filament\Resources\Pages\EditRecord;

class EditCoaReport extends EditRecord
{
    protected static string $resource = CoaReportResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
