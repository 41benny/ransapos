<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class SalesType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'channel_type',
        'default_payment_method_id',
        'is_active',
        'sort_order',
    ];

    /* ------------------------------------------------------------------
     | Relations
     | ------------------------------------------------------------------*/

    public function defaultPaymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'default_payment_method_id');
    }

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

    public function scopeOnline(Builder $query): Builder
    {
        return $query->where('channel_type', 'online');
    }

    public function scopeOffline(Builder $query): Builder
    {
        return $query->where('channel_type', 'offline');
    }

    /* ------------------------------------------------------------------
     | Instance Helpers
     | ------------------------------------------------------------------*/

    public function isOnline(): bool
    {
        return $this->channel_type === 'online';
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
     * Daftar kode (lowercase) yang termasuk kanal online, ber-cache 60 detik.
     * Dipakai untuk memicu logika khusus penjualan online (mis. input harga manual).
     *
     * @return array<int, string>
     */
    public static function onlineCodes(): array
    {
        return Cache::remember('sales_types.online_codes', 60, function () {
            return static::query()
                ->online()
                ->pluck('code')
                ->all();
        });
    }

    /**
     * Apakah kode sales_type tertentu termasuk kanal online.
     */
    public static function isOnlineCode(?string $code): bool
    {
        if ($code === null || $code === '') {
            return false;
        }

        return in_array($code, static::onlineCodes(), true);
    }

    /**
     * Flush cache saat data berubah.
     */
    public static function flushCache(): void
    {
        Cache::forget('sales_types.price_levels');
        Cache::forget('sales_types.online_codes');
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
