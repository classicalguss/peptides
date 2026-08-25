<?php

namespace App\Filament\Support\Pages;

use App\Filament\Support\RelationManagers\PriceRelationManager;
use Lunar\Admin\Filament\Resources\ProductResource\RelationManagers\CustomerGroupPricingRelationManager;
use Lunar\Admin\Filament\Resources\ProductVariantResource\Pages\ManageVariantPricing as LunarManageVariantPricing;

/**
 * Lunar's variant pricing page, using our tiered pricing table so quantity
 * breaks keep the exact cents the admin entered.
 */
class ManageVariantPricing extends LunarManageVariantPricing
{
    public function getRelationManagers(): array
    {
        return [
            CustomerGroupPricingRelationManager::class,
            PriceRelationManager::class,
        ];
    }
}
