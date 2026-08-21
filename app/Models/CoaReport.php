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

    public const DEFAULT_STATUS_COLOR = 'gray';

    /**
     * Predefined status colours. One hex per colour, used identically on the
     * storefront and in the admin preview, so what the admin picks is what
     * visitors see.
     *
     * @var array<string, array{label: string, hex: string}>
     */
    public const COLORS = [
        'gray' => ['label' => 'Grey', 'hex' => '#9ca3af'],
        'blue' => ['label' => 'Blue', 'hex' => '#60a5fa'],
        'green' => ['label' => 'Green', 'hex' => '#4ade80'],
        'yellow' => ['label' => 'Yellow', 'hex' => '#fbbf24'],
        'red' => ['label' => 'Red', 'hex' => '#f87171'],
        'purple' => ['label' => 'Purple', 'hex' => '#c084fc'],
    ];

    /** Storefront panel background, used behind the admin preview pill. */
    public const PREVIEW_BACKGROUND = '#100e16';

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
     * Storefront hex for the predefined colour chosen in the admin.
     */
    public function statusColor(): string
    {
        return (self::COLORS[$this->status_color] ?? self::COLORS[self::DEFAULT_STATUS_COLOR])['hex'];
    }

    /**
     * Inline style for the status pill: tinted background, solid text, and a
     * faint ring, all derived from the chosen colour.
     */
    public function statusStyle(): string
    {
        return self::pillStyle($this->statusColor());
    }

    /**
     * The one formula for a status pill — shared by the storefront and the
     * admin preview.
     */
    public static function pillStyle(string $hex): string
    {
        return "background-color: {$hex}1a; color: {$hex}; box-shadow: inset 0 0 0 1px {$hex}4d;";
    }

    public function pdfUrl(): ?string
    {
        return $this->pdf_path ? Storage::disk('public')->url($this->pdf_path) : null;
    }
}
