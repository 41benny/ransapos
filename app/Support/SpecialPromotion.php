<?php

namespace App\Support;

class SpecialPromotion
{
    /**
     * @var array<string, string>
     */
    private const CANONICAL_TYPES = [
        'meal karyawan' => 'meal_karyawan',
        'meal_karyawan' => 'meal_karyawan',
        'meal02' => 'meal_karyawan',
        'compliment' => 'compliment',
        'cmp01' => 'compliment',
    ];

    /**
     * @return array<int, string>
     */
    public static function blockedRuntimeSalesTypes(): array
    {
        return ['meal_karyawan', 'compliment'];
    }

    /**
     * @param array<string, string> $salesTypes
     * @return array<string, string>
     */
    public static function filterRuntimeSalesTypes(array $salesTypes): array
    {
        $filtered = [];

        foreach ($salesTypes as $key => $label) {
            if (self::isSpecialSalesType((string) $key)) {
                continue;
            }

            $filtered[$key] = $label;
        }

        return $filtered;
    }

    public static function classify(?string ...$values): ?string
    {
        foreach ($values as $value) {
            $normalized = self::normalize($value);

            if ($normalized !== '' && isset(self::CANONICAL_TYPES[$normalized])) {
                return self::CANONICAL_TYPES[$normalized];
            }
        }

        return null;
    }

    public static function isSpecialSalesType(?string $salesType): bool
    {
        return self::classify($salesType) !== null;
    }

    public static function formatLabel(?string $value): string
    {
        $type = self::classify($value) ?? self::normalize($value);

        return match ($type) {
            'meal_karyawan' => 'Meal karyawan',
            'compliment' => 'Compliment',
            default => ucfirst(str_replace('_', ' ', trim((string) $value))),
        };
    }

    private static function normalize(?string $value): string
    {
        $normalized = strtolower(trim((string) $value));
        if ($normalized === '') {
            return '';
        }

        $normalized = preg_replace('/[^a-z0-9]+/', ' ', $normalized) ?? '';

        return trim($normalized);
    }
}
