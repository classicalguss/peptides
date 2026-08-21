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

    public const STATUS_FAIL = 'fail';

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

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAIL;
    }

    /**
     * Admin-editable label shown in place of batch details for any non-pass
     * status.
     */
    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_TESTING => site_text('labs.testing_label'),
            self::STATUS_FAIL => site_text('labs.failed_label'),
            default => site_text('labs.pending_label'),
        };
    }

    /**
     * Optional explanatory sentence for a non-pass status.
     */
    public function statusNote(): ?string
    {
        return match ($this->status) {
            self::STATUS_TESTING => site_text('labs.testing_note'),
            self::STATUS_FAIL => site_text('labs.failed_note'),
            default => null,
        };
    }

    public function pdfUrl(): ?string
    {
        return $this->pdf_path ? Storage::disk('public')->url($this->pdf_path) : null;
    }
}
