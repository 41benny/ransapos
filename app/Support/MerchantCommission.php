<?php

namespace App\Support;

use DateTimeInterface;

final class MerchantCommission
{
    public const RATE = 0.20;

    public const EFFECTIVE_DATE = '2026-07-01';

    public const SALES_TYPES = ['GO_FOOD', 'GRAB_FOOD', 'SHOPEE_FOOD'];

    public static function appliesTo(?string $salesType): bool
    {
        $normalized = strtolower(preg_replace('/[^a-z0-9]/i', '', trim((string) $salesType)) ?? '');

        return in_array($normalized, ['gofood', 'grabfood', 'shopeefood'], true);
    }

    public static function isEffective(DateTimeInterface|string|null $saleDate): bool
    {
        if ($saleDate instanceof DateTimeInterface) {
            $saleDate = $saleDate->format('Y-m-d');
        }

        return $saleDate !== null && substr(trim($saleDate), 0, 10) >= self::EFFECTIVE_DATE;
    }

    public static function amount(
        float|int|string|null $saleAmount,
        ?string $salesType,
        DateTimeInterface|string|null $saleDate = null,
    ): float
    {
        return self::appliesTo($salesType) && self::isEffective($saleDate)
            ? (float) $saleAmount * self::RATE
            : 0.0;
    }
}
