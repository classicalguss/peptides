<?php

namespace App\Filament\Resources\ProductPageTextResource\Pages;

use App\Filament\Resources\ProductPageTextResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductPageText extends EditRecord
{
    protected static string $resource = ProductPageTextResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record->isStack()) {
            $data['stack_benefits'] = $data['benefits'] ?? [];
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->record->isStack()) {
            $data['benefits'] = $data['stack_benefits'] ?? [];
            unset($data['stack_benefits']);
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('preview')
                ->label('Preview Product Page')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn (): string => route($this->record->isStack() ? 'stack' : 'compound', $this->record->handle))
                ->openUrlInNewTab(),
            Actions\Action::make('catalog')
                ->label('Name, Price & Images')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('gray')
                ->url(fn (): string => ProductPageTextResource::productCatalogUrl($this->record)),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
