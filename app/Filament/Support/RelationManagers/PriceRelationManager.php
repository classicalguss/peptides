<?php

namespace App\Filament\Support\RelationManagers;

use App\Filament\Support\Concerns\RoundsEnteredPrices;
use Filament\Tables\Table;
use Lunar\Admin\Support\RelationManagers\PriceRelationManager as LunarPriceRelationManager;

/**
 * Lunar's "Tiered pricing" table, with the quantity-break price stored to the
 * exact cent the admin entered. See {@see RoundsEnteredPrices}.
 */
class PriceRelationManager extends LunarPriceRelationManager
{
    use RoundsEnteredPrices;

    public function table(Table $table): Table
    {
        return $this->roundEnteredPrices(parent::table($table));
    }
}
