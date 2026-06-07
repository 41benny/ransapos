<?php

namespace Tests\Feature;

use App\Models\CashSession;
use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiPaymentSaleFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_can_be_paid_with_cash_and_qris(): void
    {
        $user = User::create([
            'name' => 'Kasir Split',
            'email' => 'kasir-split@example.com',
            'password' => bcrypt('secret'),
        ]);

        $outlet = Outlet::create([
            'name' => 'Outlet Split',
            'code' => 'OUT-SPLIT',
            'tax_rate' => 0,
            'service_charge_rate' => 0,
        ]);

        $category = ProductCategory::create([
            'code' => 'FOOD',
            'name' => 'Food',
        ]);

        $product = Product::create([
            'sku' => 'MENU-SPLIT-1',
            'name' => 'Menu Split',
            'category_id' => $category->id,
            'unit' => 'pcs',
            'purchase_price' => 0,
            'selling_price' => 250000,
            'product_type' => 'service',
            'min_stock' => 0,
            'is_active' => true,
            'is_sellable' => true,
            'is_pos_available' => true,
        ]);

        $cashSession = CashSession::create([
            'session_number' => 'CS-SPLIT-1',
            'outlet_id' => $outlet->id,
            'user_id' => $user->id,
            'opening_balance' => 100000,
            'expected_balance' => 100000,
            'total_sales' => 0,
            'total_cash' => 0,
            'total_non_cash' => 0,
            'opened_at' => now(),
            'status' => 'open',
        ]);

        $cash = PaymentMethod::create([
            'code' => 'CASH',
            'name' => 'Cash',
            'is_active' => true,
        ]);

        $qris = PaymentMethod::create([
            'code' => 'QRIS',
            'name' => 'QRIS',
            'is_active' => true,
        ]);

        $sale = app(SaleService::class)->createSale([
            'outlet_id' => $outlet->id,
            'cash_session_id' => $cashSession->id,
            'user_id' => $user->id,
            'discount_type' => 'none',
            'discount_value' => 0,
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 250000,
                'discount_amount' => 0,
            ]],
            'payments' => [
                [
                    'payment_method_id' => $cash->id,
                    'amount' => 50000,
                    'tendered_amount' => 50000,
                ],
                [
                    'payment_method_id' => $qris->id,
                    'amount' => 200000,
                    'reference_number' => 'QR-123',
                ],
            ],
        ])->load('payments.paymentMethod');

        $this->assertSame(250000.0, (float) $sale->total_amount);
        $this->assertCount(2, $sale->payments);
        $this->assertSame(50000.0, (float) $sale->payments[0]->amount);
        $this->assertSame(200000.0, (float) $sale->payments[1]->amount);
        $this->assertSame('QR-123', $sale->payments[1]->reference_number);

        $cashSession->refresh();
        $this->assertSame(250000.0, (float) $cashSession->total_sales);
        $this->assertSame(50000.0, (float) $cashSession->total_cash);
        $this->assertSame(200000.0, (float) $cashSession->total_non_cash);
        $this->assertSame(150000.0, (float) $cashSession->expected_balance);
    }
}

