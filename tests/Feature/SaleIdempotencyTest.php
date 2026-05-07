<?php

namespace Tests\Feature;

use App\Models\CashSession;
use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Sale;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_idempotency_key_returns_existing_sale_without_creating_duplicate(): void
    {
        $user = User::create([
            'name' => 'Tester',
            'email' => 'tester@example.com',
            'password' => bcrypt('secret'),
        ]);
        $outlet = Outlet::create(['name' => 'Outlet 1', 'code' => 'OUT1']);
        $category = ProductCategory::create(['code' => 'FOOD', 'name' => 'Food']);
        $product = Product::create([
            'sku' => 'MENU-1',
            'name' => 'Menu Test',
            'category_id' => $category->id,
            'unit' => 'pcs',
            'purchase_price' => 0,
            'selling_price' => 10000,
            'product_type' => 'service',
            'min_stock' => 0,
            'is_active' => true,
        ]);
        $cashSession = CashSession::create([
            'session_number' => 'CS-IDEMPOTENCY-1',
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
        $paymentMethod = PaymentMethod::create(['code' => 'CASH', 'name' => 'Cash']);

        $payload = [
            'idempotency_key' => 'order-token-1',
            'outlet_id' => $outlet->id,
            'cash_session_id' => $cashSession->id,
            'user_id' => $user->id,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 10000,
                'discount_amount' => 0,
            ]],
            'payment_method_id' => $paymentMethod->id,
            'payment_amount' => 10000,
        ];

        $service = app(SaleService::class);

        $firstSale = $service->createSale($payload);
        $secondSale = $service->createSale($payload);

        $this->assertSame($firstSale->id, $secondSale->id);
        $this->assertSame(1, Sale::count());
        $this->assertSame((float) $firstSale->total_amount, (float) $cashSession->fresh()->total_sales);
    }
}
