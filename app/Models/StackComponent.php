<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StackComponent extends Model
{
    protected $guarded = [];

    protected $casts = [
        'base_quantity' => 'integer',
    ];

    public function stack(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'stack_product_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'component_product_id');
    }
}
