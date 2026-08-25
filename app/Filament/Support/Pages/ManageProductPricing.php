<?php

namespace App\Filament\Support\Pages;

use App\Filament\Support\RelationManagers\PriceRelationManager;
use Lunar\Admin\Filament\Resources\ProductResource\Pages\ManageProductPricing as LunarManageProductPricing;
use Lunar\Admin\Filament\Resources\ProductResource\RelationManagers\CustomerGroupPricingRelationManager;

/**
 * Lunar's product pricing page, using our tiered pricing table so quantity
 * breaks keep the exact cents the admin entered.
 */
class ManageProductPricing extends LunarManageProductPricing
{
    public function getRelationManagers(): array
    {
        return [
            CustomerGroupPricingRelationManager::make([
                'ownerRecord' => $this->getOwnerRecord(),
            ]),
            PriceRelationManager::make([
                'ownerRecord' => $this->getOwnerRecord(),
            ]),
        ];
    }
}
