<?php

namespace App\Support;

use DateTimeInterface;

final class MerchantCommission
{
    public const RATE = 0.20;

    public const EFFECTIVE_DATE = '2026-07-01';

    public const SALES_TYPES = ['GO_FOOD', 'GRAB_FOOD', 'SHOPEE_FOOD'];

    public const PAYMENT_METHOD_CODES = ['GO_FOOD', 'GRAB_FOOD', 'SHOPEE_FOOD'];

    public static function appliesTo(?string $salesType): bool
    {
        $normalized = strtolower(preg_replace('/[^a-z0-9]/i', '', trim((string) $salesType)) ?? '');

        return in_array($normalized, ['gofood', 'grabfood', 'shopeefood'], true);
    }

    public static function appliesToPaymentMethod(?string $paymentMethodCode, ?string $paymentMethodName = null): bool
    {
        return self::appliesTo($paymentMethodCode) || self::appliesTo($paymentMethodName);
    }

    public static function salesTypeForPaymentMethod(?string $paymentMethodCode, ?string $paymentMethodName = null): ?string
    {
        $value = preg_replace('/[^a-z0-9]/i', '', (string) ($paymentMethodCode ?: $paymentMethodName)) ?? '';

        return match (strtolower($value)) {
            'gofood' => 'GO_FOOD',
            'grabfood' => 'GRAB_FOOD',
            'shopeefood' => 'SHOPEE_FOOD',
            default => null,
        };
    }

    public static function constrainSalesQuery($query, string $salesTable = 'sales'): void
    {
        $query->whereDate("{$salesTable}.sale_date", '>=', self::EFFECTIVE_DATE)
            ->where(function ($merchantQuery) use ($salesTable) {
                $merchantQuery->whereIn("{$salesTable}.sales_type", self::SALES_TYPES)
                    ->orWhereExists(function ($paymentQuery) use ($salesTable) {
                        $paymentQuery->selectRaw('1')
                            ->from('payments as merchant_payments')
                            ->join('payment_methods as merchant_methods', 'merchant_methods.id', '=', 'merchant_payments.payment_method_id')
                            ->whereColumn('merchant_payments.sale_id', "{$salesTable}.id")
                            ->whereIn('merchant_methods.code', self::PAYMENT_METHOD_CODES);
                    });
            });
    }

    public static function paymentExistsSql(string $saleIdColumn): string
    {
        $codes = "'" . implode("','", self::PAYMENT_METHOD_CODES) . "'";

        return "EXISTS (SELECT 1 FROM payments merchant_payments INNER JOIN payment_methods merchant_methods ON merchant_methods.id = merchant_payments.payment_method_id WHERE merchant_payments.sale_id = {$saleIdColumn} AND UPPER(merchant_methods.code) IN ({$codes}))";
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
