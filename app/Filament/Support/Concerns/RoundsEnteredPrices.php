<?php

namespace App\Filament\Support\Concerns;

use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Table;
use Lunar\Models\Currency;

/**
 * Lunar's pricing tables turn the entered price into minor units with
 * `(int) ($price * $factor)`. Because "79.99" * 100 is 7998.999… in floating
 * point, the cast truncates and the price is stored a cent short ($79.98).
 * These helpers re-do the conversion with rounding, so the price the admin
 * types is the price the storefront shows and the cart charges.
 */
trait RoundsEnteredPrices
{
    /**
     * Replace the create/edit price conversion on a Lunar pricing table.
     *
     * @param  array<string, mixed>  $defaults  Extra data the replaced conversion set.
     */
    protected function roundEnteredPrices(Table $table, array $defaults = []): Table
    {
        foreach ($table->getFlatActions() as $action) {
            if ($action instanceof CreateAction || $action instanceof EditAction) {
                $action->mutateFormDataUsing(
                    fn (array $data): array => static::priceInMinorUnits([...$data, ...$defaults])
                );
            }
        }

        return $table;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected static function priceInMinorUnits(array $data): array
    {
        $factor = (int) Currency::find($data['currency_id'])->factor;

        foreach (['price', 'compare_price'] as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $data[$field] = blank($data[$field])
                ? null
                : (int) round((float) $data[$field] * $factor);
        }

        return $data;
    }
}
