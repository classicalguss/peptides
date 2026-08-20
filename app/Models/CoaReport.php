<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Lunar\Models\Product;

class CoaReport extends Model
{
    public const STATUS_PASS = 'pass';

    public const STATUS_TESTING = 'testing';

    public const STATUS_PENDING = 'pending';

    protected $guarded = [];

    protected $casts = [
        'tested_on' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isPass(): bool
    {
        return $this->status === self::STATUS_PASS;
    }

    public function isTesting(): bool
    {
        return $this->status === self::STATUS_TESTING;
    }

    public function pdfUrl(): ?string
    {
        return $this->pdf_path ? Storage::disk('public')->url($this->pdf_path) : null;
    }
}
