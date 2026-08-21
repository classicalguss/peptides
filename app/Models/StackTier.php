<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Models\Product;
use Lunar\Models\ProductVariant;

class StackTier extends Model
{
    protected $guarded = [];

    protected $casts = [
        'supply_days' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function multiplier(): int
    {
        return (int) max(1, round($this->supply_days / 40));
    }

    /**
     * The tier's price in cents, read from its Lunar variant — the same
     * price the cart charges — so repricing in the standard admin screen
     * is reflected everywhere.
     */
    public function priceValue(): int
    {
        $prices = $this->variant?->prices ?? collect();

        return (int) ($prices->where('min_quantity', 1)->min('price.value')
            ?? $prices->min('price.value')
            ?? 0);
    }
}
