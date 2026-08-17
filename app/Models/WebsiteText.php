<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class WebsiteText extends Model
{
    protected $fillable = [
        'key',
        'page',
        'section',
        'label',
        'location_hint',
        'route_name',
        'default_value',
        'value',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('website-text.values'));
        static::deleted(fn () => Cache::forget('website-text.values'));
    }
}
