<?php

namespace Tests\Unit;

use App\Models\Product;
use PHPUnit\Framework\TestCase;

class ProductPriceLevelTest extends TestCase
{
    public function test_it_resolves_price_levels_case_insensitively_for_default_and_outlet_prices(): void
    {
        $product = new Product([
            'selling_price' => 15000,
            'price_levels' => [
                'regular' => 15000,
                'MEMBER_2' => [
                    'default' => 7500,
                    'outlets' => [
                        '1' => 7000,
                    ],
                ],
            ],
        ]);

        $this->assertSame(7000.0, $product->getPriceByLevelAndOutlet('MEMBER_2', 1));
        $this->assertSame(7000.0, $product->getPriceByLevelAndOutlet('member_2', 1));
        $this->assertSame(7500.0, $product->getPriceByLevelAndOutlet('MEMBER_2', 9));
    }

    public function test_it_falls_back_to_selling_price_when_level_does_not_exist(): void
    {
        $product = new Product([
            'selling_price' => 15000,
            'price_levels' => [
                'regular' => 15000,
            ],
        ]);

        $this->assertSame(15000.0, $product->getPriceByLevelAndOutlet('MEMBER_9', 1));
    }
}
