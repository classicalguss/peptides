<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class WebsiteListItem extends Model
{
    protected $fillable = [
        'list_key',
        'sort_order',
        'heading',
        'body',
        'extra',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('website-list.items'));
        static::deleted(fn () => Cache::forget('website-list.items'));
    }
}
