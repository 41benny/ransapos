<?php

namespace App\Support;

final class MerchantCommission
{
    public const RATE = 0.20;

    public const SALES_TYPES = ['gofood', 'grabfood', 'shopeefood'];

    public static function appliesTo(?string $salesType): bool
    {
        return in_array(strtolower(trim((string) $salesType)), self::SALES_TYPES, true);
    }

    public static function amount(float|int|string|null $saleAmount, ?string $salesType): float
    {
        return self::appliesTo($salesType) ? (float) $saleAmount * self::RATE : 0.0;
    }
}
