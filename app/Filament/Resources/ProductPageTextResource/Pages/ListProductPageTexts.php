<?php

namespace App\Filament\Resources\ProductPageTextResource\Pages;

use App\Filament\Resources\ProductPageTextResource;
use Filament\Resources\Pages\ListRecords;

class ListProductPageTexts extends ListRecords
{
    protected static string $resource = ProductPageTextResource::class;

    public function getSubheading(): ?string
    {
        return 'Search a product name or any wording from its page. Edit names, prices, images, and stock under Catalog → Products.';
    }
}
