<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vials of one compound included in one collection size (tier).
 */
class StackTierQuantity extends Model
{
    protected $guarded = [];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function tier(): BelongsTo
    {
        return $this->belongsTo(StackTier::class, 'stack_tier_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(StackComponent::class, 'stack_component_id');
    }
}
