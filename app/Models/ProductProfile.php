<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Lunar\Models\Product;

class ProductProfile extends Model
{
    public const KIND_COMPOUND = 'compound';

    public const KIND_STACK = 'stack';

    protected $guarded = [];

    protected $casts = [
        'benefits' => 'array',
        'highlights' => 'array',
        'pillars' => 'array',
        'audience' => 'array',
        'faq' => 'array',
        'save_up_to' => 'float',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(StackComponent::class, 'stack_product_id', 'product_id');
    }

    public function isStack(): bool
    {
        return $this->kind === self::KIND_STACK;
    }

    public function accentHex(): string
    {
        return config("theme.accents.{$this->accent}.hex", config('theme.brand.gold'));
    }

    public function accentGlow(): string
    {
        return config("theme.accents.{$this->accent}.glow", config('theme.brand.gold_deep'));
    }

    /**
     * Inline CSS custom properties consumed by the accent-* utilities.
     */
    public function accentStyle(): string
    {
        return "--accent: {$this->accentHex()}; --accent-glow: {$this->accentGlow()};";
    }
}
