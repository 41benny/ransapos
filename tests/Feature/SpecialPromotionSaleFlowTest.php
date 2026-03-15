<?php

namespace Tests\Feature;

use App\Models\CashSession;
use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Promotion;
use App\Models\PromotionCategoryRule;
use App\Models\SaleItem;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpecialPromotionSaleFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_special_promotion_keeps_promotion_as_source_of_truth_and_normalizes_sales_type(): void
    {
        $user = User::create([
            'name' => 'Kasir Test',
            'email' => 'kasir-special@example.com',
            'password' => bcrypt('secret'),
        ]);

        $outlet = Outlet::create([
            'name' => 'Outlet Test',
            'code' => 'OUT-TEST',
            'tax_rate' => 0,
            'service_charge_rate' => 0,
        ]);

        $category = ProductCategory::create([
            'code' => 'FOOD',
            'name' => 'Food',
        ]);

        $product = Product::create([
            'sku' => 'SKU-SPECIAL-1',
            'name' => 'Siaw May Ayam',
            'category_id' => $category->id,
            'unit' => 'pcs',
            'purchase_price' => 5000,
            'selling_price' => 15000,
            'product_type' => 'finished_good',
            'min_stock' => 0,
            'is_active' => true,
            'is_sellable' => true,
            'is_pos_available' => true,
        ]);

        $cashSession = CashSession::create([
            'session_number' => 'CS-SPECIAL-1',
            'outlet_id' => $outlet->id,
            'user_id' => $user->id,
            'opening_balance' => 0,
            'expected_balance' => 0,
            'total_sales' => 0,
            'total_cash' => 0,
            'total_non_cash' => 0,
            'opened_at' => now(),
            'status' => 'open',
        ]);

        $paymentMethod = PaymentMethod::create([
            'code' => 'CASH',
            'name' => 'Cash',
            'is_active' => true,
        ]);

        $promotion = Promotion::create([
            'name' => 'MEAL KARYAWAN',
            'code' => 'MEAL02',
            'outlet_id' => $outlet->id,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'is_active' => true,
        ]);

        PromotionCategoryRule::create([
            'promotion_id' => $promotion->id,
            'product_category_id' => $category->id,
            'discount_percent' => 100,
        ]);

        $sale = app(SaleService::class)->createSale([
            'outlet_id' => $outlet->id,
            'cash_session_id' => $cashSession->id,
            'user_id' => $user->id,
            'sales_type' => 'meal_karyawan',
            'promotion_id' => $promotion->id,
            'discount_type' => 'none',
            'discount_value' => 0,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 15000,
                    'discount_amount' => 0,
                ],
            ],
            'payment_method_id' => $paymentMethod->id,
            'payment_amount' => 0,
        ]);

        /** @var SaleItem $saleItem */
        $saleItem = SaleItem::query()->where('sale_id', $sale->id)->firstOrFail();

        $this->assertSame('regular', $sale->sales_type);
        $this->assertSame($promotion->id, $sale->promotion_id);
        $this->assertSame(0.0, (float) $sale->discount_amount);
        $this->assertSame(15000.0, (float) $saleItem->discount_amount);
        $this->assertSame(0.0, (float) $sale->total_amount);
    }
}
