<?php

namespace App\Filament\Extensions;

use App\Filament\Support\Concerns\RoundsEnteredPrices;
use Filament\Tables\Table;
use Lunar\Admin\Support\Extending\RelationManagerExtension;

/**
 * Keeps customer group prices at the exact cents entered, the same way the
 * tiered pricing table does. See {@see RoundsEnteredPrices}.
 */
class CustomerGroupPricingRelationManagerExtension extends RelationManagerExtension
{
    use RoundsEnteredPrices;

    public function extendTable(Table $table): Table
    {
        return $this->roundEnteredPrices($table, defaults: ['min_quantity' => 1]);
    }
}
