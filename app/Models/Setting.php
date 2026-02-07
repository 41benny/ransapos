<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    public $incrementing = false;
    protected $primaryKey = 'key';
    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
    ];

    protected static function cacheKey(string $key): string
    {
        return 'settings:' . $key;
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        if (!Schema::hasTable('settings')) {
            return $default;
        }

        return Cache::remember(static::cacheKey($key), 60, function () use ($key, $default) {
            $value = static::query()->where('key', $key)->value('value');
            return $value === null ? $default : $value;
        });
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        $value = static::getValue($key, null);

        if ($value === null) {
            return $default;
        }

        $filtered = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $filtered ?? $default;
    }

    public static function setValue(string $key, mixed $value): void
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value]
        );

        Cache::forget(static::cacheKey($key));
    }
}
