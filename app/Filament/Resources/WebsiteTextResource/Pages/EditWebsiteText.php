<?php

namespace App\Filament\Resources\WebsiteTextResource\Pages;

use App\Filament\Resources\WebsiteTextResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditWebsiteText extends EditRecord
{
    protected static string $resource = WebsiteTextResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('preview')
                ->label('Preview Page')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn (): ?string => $this->record->route_name ? route($this->record->route_name) : null)
                ->openUrlInNewTab()
                ->visible(fn (): bool => filled($this->record->route_name)),
            Actions\Action::make('reset')
                ->label('Restore Original Text')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('This replaces the current wording with the original wording that shipped with the website.')
                ->action(function (): void {
                    $this->record->update(['value' => $this->record->default_value]);
                    $this->refreshFormData(['value']);

                    Notification::make()
                        ->title('Original wording restored')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
