<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class Policy extends Model
{
    protected $fillable = ['slug', 'title', 'body', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('policies.nav'));
        static::deleted(fn () => Cache::forget('policies.nav'));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Title + slug for every policy, in footer order. Cached: the footer is
     * on every page.
     *
     * @return Collection<int, array{slug: string, title: string}>
     */
    public static function navigation(): Collection
    {
        return collect(Cache::rememberForever(
            'policies.nav',
            fn () => static::query()->orderBy('sort_order')->get(['slug', 'title'])->map->only(['slug', 'title'])->all(),
        ));
    }
}
