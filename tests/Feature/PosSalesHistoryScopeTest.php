<?php

namespace Tests\Feature;

use App\Models\CashSession;
use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Riwayat POS harus mengikuti kepemilikan shift, bukan `sales.user_id`. Transaksi backdate
 * diinput oleh admin tapi tetap milik shift kasir tempat penjualannya terjadi.
 */
class PosSalesHistoryScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_shows_backdated_sale_input_by_admin_in_cashier_own_shift(): void
    {
        $outlet = Outlet::factory()->create(['tax_rate' => 0, 'service_charge_rate' => 0]);
        $kasirRole = Role::firstOrCreate(['name' => 'kasir'], ['display_name' => 'Kasir']);
        $cashier = User::factory()->create(['outlet_id' => $outlet->id, 'role_id' => $kasirRole->id]);
        $admin = User::factory()->create(['outlet_id' => $outlet->id, 'role_id' => $kasirRole->id]);
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'product_type' => 'service',
            'is_sellable' => true,
            'selling_price' => 12000,
        ]);
        $method = PaymentMethod::create(['code' => 'QRIS_BANK', 'name' => 'QRIS Bank', 'is_active' => true]);

        $shift = CashSession::create([
            'session_number' => 'CS-001',
            'session_type' => 'regular',
            'outlet_id' => $outlet->id,
            'user_id' => $cashier->id,
            'opening_balance' => 0,
            'opened_at' => today()->setTime(10, 0),
            'status' => 'open',
        ]);

        // Transaksi yang diinput admin, tapi menempel ke shift milik kasir.
        $sale = app(SaleService::class)->createSale([
            'outlet_id' => $outlet->id,
            'cash_session_id' => $shift->id,
            'user_id' => $admin->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 12000, 'discount_amount' => 0]],
            'payment_method_id' => $method->id,
            'payment_amount' => 12000,
            'sale_date' => today()->toDateString(),
            'is_backdated' => true,
        ]);

        $this->assertEquals($admin->id, $sale->user_id);

        $this->actingAs($cashier)
            ->get(route('pos.sales.history'))
            ->assertSuccessful()
            ->assertSee($sale->invoice_number);
    }

    public function test_history_hides_sales_from_another_cashier_shift(): void
    {
        $outlet = Outlet::factory()->create(['tax_rate' => 0, 'service_charge_rate' => 0]);
        $kasirRole = Role::firstOrCreate(['name' => 'kasir'], ['display_name' => 'Kasir']);
        $cashier = User::factory()->create(['outlet_id' => $outlet->id, 'role_id' => $kasirRole->id]);
        $otherCashier = User::factory()->create(['outlet_id' => $outlet->id, 'role_id' => $kasirRole->id]);
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'product_type' => 'service',
            'is_sellable' => true,
            'selling_price' => 12000,
        ]);
        $method = PaymentMethod::create(['code' => 'CASH', 'name' => 'Cash', 'is_active' => true]);

        $otherShift = CashSession::create([
            'session_number' => 'CS-002',
            'session_type' => 'regular',
            'outlet_id' => $outlet->id,
            'user_id' => $otherCashier->id,
            'opening_balance' => 0,
            'opened_at' => today()->setTime(10, 0),
            'status' => 'open',
        ]);

        $sale = app(SaleService::class)->createSale([
            'outlet_id' => $outlet->id,
            'cash_session_id' => $otherShift->id,
            'user_id' => $otherCashier->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 12000, 'discount_amount' => 0]],
            'payment_method_id' => $method->id,
            'payment_amount' => 12000,
            'sale_date' => today()->toDateString(),
        ]);

        $this->actingAs($cashier)
            ->get(route('pos.sales.history'))
            ->assertSuccessful()
            ->assertDontSee($sale->invoice_number);
    }
}
