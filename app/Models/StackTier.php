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
        'price' => 'integer',
        'subscribe_price' => 'integer',
        'supply_days' => 'integer',
        'save_percent' => 'float',
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
}
