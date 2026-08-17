<?php

namespace App\Filament\Resources\WebsiteTextResource\Pages;

use App\Filament\Resources\WebsiteTextResource;
use Filament\Resources\Pages\ListRecords;

class ListWebsiteTexts extends ListRecords
{
    protected static string $resource = WebsiteTextResource::class;

    public function getSubheading(): ?string
    {
        return 'Find wording by page or section, or search for the exact words you see on the website. Product names and product-specific descriptions are under Catalog → Products.';
    }
}
