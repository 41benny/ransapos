<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class SalesType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /* ------------------------------------------------------------------
     | Scopes
     | ------------------------------------------------------------------*/

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /* ------------------------------------------------------------------
     | Static Helpers
     | ------------------------------------------------------------------*/

    /**
     * Return associative array ['code' => 'name'] untuk backward-compatible
     * replacement dari config('sales.price_levels').
     *
     * Cached selama 60 detik agar tidak query tiap request.
     *
     * @return array<string, string>
     */
    public static function priceLevels(): array
    {
        return Cache::remember('sales_types.price_levels', 60, function () {
            return static::query()
                ->active()
                ->ordered()
                ->pluck('name', 'code')
                ->all();
        });
    }

    /**
     * Flush cache saat data berubah.
     */
    public static function flushCache(): void
    {
        Cache::forget('sales_types.price_levels');
    }

    /* ------------------------------------------------------------------
     | Lifecycle Hooks
     | ------------------------------------------------------------------*/

    protected static function booted(): void
    {
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
    }
}
