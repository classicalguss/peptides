<?php

namespace App\Filament\Resources\CoaReportResource\Pages;

use App\Filament\Resources\CoaReportResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCoaReport extends CreateRecord
{
    protected static string $resource = CoaReportResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
