<?php

namespace App\Filament\Resources\WebsiteListResource\Pages;

use App\Filament\Resources\WebsiteListResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWebsiteListItems extends ListRecords
{
    protected static string $resource = WebsiteListResource::class;

    public function getSubheading(): ?string
    {
        return 'Repeating content such as FAQ entries, trust-bar promises and checklist lines. Add or remove items freely and drag rows to reorder them; the website adjusts automatically.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Add item'),
        ];
    }
}
