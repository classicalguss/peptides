<?php

namespace App\Filament\Resources\WebsiteListResource\Pages;

use App\Filament\Resources\WebsiteListResource;
use App\Models\WebsiteListItem;
use Filament\Resources\Pages\CreateRecord;

class CreateWebsiteListItem extends CreateRecord
{
    protected static string $resource = WebsiteListResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['sort_order'] = (int) WebsiteListItem::query()
            ->where('list_key', $data['list_key'])
            ->max('sort_order') + 1;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
