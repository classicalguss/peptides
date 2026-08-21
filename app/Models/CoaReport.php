<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Lunar\Models\Product;

class CoaReport extends Model
{
    public const STATUS_PASS = 'pass';

    public const STATUS_UNPUBLISHED = 'unpublished';

    public const COLORS = ['amber' => 'Amber', 'red' => 'Red', 'gray' => 'Grey'];

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

    /**
     * Wording shown in place of batch details while a batch is unpublished.
     */
    public function statusLabel(): string
    {
        return $this->status_label ?: 'Not yet published';
    }

    public function statusNote(): ?string
    {
        return $this->status_note ?: null;
    }

    public function pdfUrl(): ?string
    {
        return $this->pdf_path ? Storage::disk('public')->url($this->pdf_path) : null;
    }
}
