<?php

namespace Tests\Unit;

use App\Support\SpecialPromotion;
use PHPUnit\Framework\TestCase;

class SpecialPromotionTest extends TestCase
{
    public function test_it_classifies_special_promotions_from_name_or_code(): void
    {
        $this->assertSame('meal_karyawan', SpecialPromotion::classify('MEAL KARYAWAN'));
        $this->assertSame('meal_karyawan', SpecialPromotion::classify('MEAL02'));
        $this->assertSame('compliment', SpecialPromotion::classify('Compliment'));
        $this->assertSame('compliment', SpecialPromotion::classify('CMP01'));
        $this->assertNull(SpecialPromotion::classify('Valentine'));
    }

    public function test_it_filters_special_sales_types_from_runtime_options(): void
    {
        $salesTypes = [
            'regular' => 'Regular',
            'gofood' => 'GoFood',
            'meal_karyawan' => 'Meal Karyawan',
            'compliment' => 'Compliment',
        ];

        $filtered = SpecialPromotion::filterRuntimeSalesTypes($salesTypes);

        $this->assertSame([
            'regular' => 'Regular',
            'gofood' => 'GoFood',
        ], $filtered);
    }
}
