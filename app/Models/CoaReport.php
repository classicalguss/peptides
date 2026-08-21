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

    public const DEFAULT_STATUS_COLOR = '#9ca3af';

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

    /**
     * Hex colour chosen in the admin for the unpublished-status pill.
     */
    public function statusColor(): string
    {
        return preg_match('/^#[0-9a-f]{6}$/i', (string) $this->status_color) ? $this->status_color : self::DEFAULT_STATUS_COLOR;
    }

    /**
     * Inline style for the status pill: tinted background, solid text, and a
     * faint ring, all derived from the chosen colour.
     */
    public function statusStyle(): string
    {
        $color = $this->statusColor();

        return "background-color: {$color}1a; color: {$color}; box-shadow: inset 0 0 0 1px {$color}4d;";
    }

    public function pdfUrl(): ?string
    {
        return $this->pdf_path ? Storage::disk('public')->url($this->pdf_path) : null;
    }
}
