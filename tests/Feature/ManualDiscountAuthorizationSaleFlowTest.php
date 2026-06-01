<?php

namespace Tests\Feature;

use App\Models\CashSession;
use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\SaleManualDiscount;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ManualDiscountAuthorizationSaleFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_dpos_manual_discount_is_saved_as_sales_discount_with_audit(): void
    {
        $kasirRole = Role::create([
            'name' => 'kasir',
            'display_name' => 'Kasir',
        ]);
        $managerRole = Role::create([
            'name' => 'manager',
            'display_name' => 'Manager',
        ]);

        $outlet = Outlet::create([
            'name' => 'Outlet Test',
            'code' => 'OUT-MD',
            'tax_rate' => 0,
            'service_charge_rate' => 0,
        ]);

        $cashier = User::create([
            'name' => 'Kasir Test',
            'email' => 'kasir-md@example.com',
            'password' => bcrypt('secret'),
            'role_id' => $kasirRole->id,
            'outlet_id' => $outlet->id,
            'is_active' => true,
        ]);

        $manager = User::create([
            'name' => 'Manager Test',
            'email' => 'manager-md@example.com',
            'password' => bcrypt('secret'),
            'role_id' => $managerRole->id,
            'outlet_id' => $outlet->id,
            'is_active' => true,
            'attendance_pin' => Hash::make('123456'),
        ]);

        $category = ProductCategory::create([
            'code' => 'FOOD',
            'name' => 'Food',
        ]);

        $product = Product::create([
            'sku' => 'SKU-MD-1',
            'name' => 'Menu Diskon',
            'category_id' => $category->id,
            'unit' => 'pcs',
            'purchase_price' => 0,
            'selling_price' => 100000,
            'product_type' => 'service',
            'min_stock' => 0,
            'is_active' => true,
            'is_sellable' => true,
            'is_pos_available' => true,
        ]);

        $cashSession = CashSession::create([
            'session_number' => 'CS-MD-1',
            'outlet_id' => $outlet->id,
            'user_id' => $cashier->id,
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

        $sale = app(SaleService::class)->createSale([
            'outlet_id' => $outlet->id,
            'cash_session_id' => $cashSession->id,
            'user_id' => $cashier->id,
            'discount_type' => 'none',
            'discount_value' => 0,
            'manual_discount_type' => 'percentage',
            'manual_discount_value' => 10,
            'manual_discount_authorization_pin' => '123456',
            'manual_discount_reason' => 'Kompensasi customer',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 100000,
                'discount_amount' => 0,
            ]],
            'payment_method_id' => $paymentMethod->id,
            'payment_amount' => 90000,
        ]);

        $this->assertSame('dpos_authorized', $sale->discount_source);
        $this->assertSame('percentage', $sale->discount_type);
        $this->assertSame(10.0, (float) $sale->discount_value);
        $this->assertSame(10000.0, (float) $sale->discount_amount);
        $this->assertSame(90000.0, (float) $sale->total_amount);
        $this->assertSame($manager->id, $sale->manual_discount_authorized_by);

        $audit = SaleManualDiscount::query()->where('sale_id', $sale->id)->firstOrFail();
        $this->assertSame($cashier->id, $audit->cashier_user_id);
        $this->assertSame($manager->id, $audit->authorized_by_user_id);
        $this->assertSame(10000.0, (float) $audit->discount_amount_applied);
    }
}
