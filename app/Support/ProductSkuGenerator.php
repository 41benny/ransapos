<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Str;

class ProductSkuGenerator
{
    public static function generate(?string $name, bool $isBundle = false, ?int $ignoreProductId = null): string
    {
        $prefix = $isBundle ? 'BND' : 'PRD';
        $slug = strtoupper((string) Str::of((string) $name)->slug('-'));
        $slug = preg_replace('/[^A-Z0-9-]/', '', $slug ?? '') ?: '';
        $slug = trim($slug, '-');

        $base = $prefix;
        if ($slug !== '') {
            $maxSlugLength = 50 - strlen($prefix) - 5;
            $base .= '-' . Str::limit($slug, max($maxSlugLength, 0), '');
        }

        $base = rtrim(substr($base, 0, 45), '-');
        if ($base === '') {
            $base = $prefix;
        }

        $attempt = 1;

        do {
            $suffix = str_pad((string) $attempt, 3, '0', STR_PAD_LEFT);
            $sku = substr($base, 0, 46) . '-' . $suffix;

            $exists = Product::query()
                ->when($ignoreProductId, fn ($query) => $query->where('id', '!=', $ignoreProductId))
                ->where('sku', $sku)
                ->exists();

            if (!$exists) {
                return $sku;
            }

            $attempt++;
        } while ($attempt <= 9999);

        return $prefix . '-' . now()->format('YmdHis');
    }
}
