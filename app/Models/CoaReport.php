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
     * Predefined status colours: the admin swatch (light), the text/ring
     * colour used in the admin pill, and the storefront accent.
     *
     * @var array<string, array{label: string, swatch: string, text: string, site: string}>
     */
    public const COLORS = [
        'gray' => ['label' => 'Grey', 'swatch' => '#e5e7eb', 'text' => '#374151', 'site' => '#9ca3af'],
        'blue' => ['label' => 'Blue', 'swatch' => '#dbeafe', 'text' => '#1d4ed8', 'site' => '#60a5fa'],
        'green' => ['label' => 'Green', 'swatch' => '#dcfce7', 'text' => '#15803d', 'site' => '#4ade80'],
        'yellow' => ['label' => 'Yellow', 'swatch' => '#fef3c7', 'text' => '#b45309', 'site' => '#fbbf24'],
        'red' => ['label' => 'Red', 'swatch' => '#fee2e2', 'text' => '#b91c1c', 'site' => '#f87171'],
        'purple' => ['label' => 'Purple', 'swatch' => '#f3e8ff', 'text' => '#7e22ce', 'site' => '#c084fc'],
    ];

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
        return (self::COLORS[$this->status_color] ?? self::COLORS[self::DEFAULT_STATUS_COLOR])['site'];
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
