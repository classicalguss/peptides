<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StackComponent extends Model
{
    protected $guarded = [];

    public function stack(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'stack_product_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'component_product_id');
    }

    public function tierQuantities(): HasMany
    {
        return $this->hasMany(StackTierQuantity::class);
    }

    /**
     * Vials of this compound in the given collection size. Zero means the
     * compound is not part of that size.
     */
    public function quantityForTier(StackTier $tier): int
    {
        return (int) ($this->tierQuantities->firstWhere('stack_tier_id', $tier->id)?->quantity ?? 0);
    }
}
