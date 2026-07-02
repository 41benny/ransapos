<?php

namespace App\Support;

final class MerchantCommission
{
    public const RATE = 0.20;

    public const SALES_TYPES = ['GO_FOOD', 'GRAB_FOOD', 'SHOPEE_FOOD'];

    public static function appliesTo(?string $salesType): bool
    {
        $normalized = strtolower(preg_replace('/[^a-z0-9]/i', '', trim((string) $salesType)) ?? '');

        return in_array($normalized, ['gofood', 'grabfood', 'shopeefood'], true);
    }

    public static function amount(float|int|string|null $saleAmount, ?string $salesType): float
    {
        return self::appliesTo($salesType) ? (float) $saleAmount * self::RATE : 0.0;
    }
}
