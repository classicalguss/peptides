<?php

namespace App\Filament\Resources\WebsiteListResource\Pages;

use App\Filament\Resources\WebsiteListResource;
use App\Support\WebsiteList;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWebsiteListItem extends EditRecord
{
    protected static string $resource = WebsiteListResource::class;

    protected function getHeaderActions(): array
    {
        $route = WebsiteList::definitions()[$this->record->list_key]['route_name'] ?? null;

        return [
            Actions\Action::make('preview')
                ->label('Preview Page')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn (): ?string => $route ? route($route) : null)
                ->openUrlInNewTab()
                ->visible(fn (): bool => filled($route)),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
