<?php

namespace Tests\Feature;

use App\Models\CashSession;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosReportResilienceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Outlet $outlet;
    protected CashSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();

        $role = Role::query()->where('name', 'superadmin')->firstOrFail();

        $this->outlet = Outlet::create([
            'name' => 'POS Outlet',
            'code' => 'POS-OUT',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'role_id' => $role->id,
            'outlet_id' => $this->outlet->id,
            'is_active' => true,
        ]);

        $this->actingAs($this->user);

        $this->session = CashSession::create([
            'session_number' => 'CS-POS-0001',
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'opening_balance' => 100000,
            'expected_balance' => 0,
            'difference' => 0,
            'total_sales' => 0,
            'total_cash' => 0,
            'total_non_cash' => 0,
            'opened_at' => now()->subHour(),
            'status' => 'open',
        ]);
    }

    public function test_pos_dashboard_handles_sales_without_payments(): void
    {
        $sale = $this->createCompletedSaleWithoutPayments();

        $response = $this->get(route('pos.dashboard'));

        $response->assertOk();
        $response->assertSee($sale->invoice_number);
    }

    public function test_pos_sales_history_handles_sales_without_payments(): void
    {
        $sale = $this->createCompletedSaleWithoutPayments();

        $response = $this->get(route('pos.sales.history'));

        $response->assertOk();
        $response->assertSee($sale->invoice_number);
    }

    public function test_pos_sales_print_handles_sales_without_payments(): void
    {
        $sale = $this->createCompletedSaleWithoutPayments();

        $response = $this->get(route('pos.sales.print', $sale));

        $response->assertOk();
        $response->assertSee($sale->invoice_number);
    }

    protected function createCompletedSaleWithoutPayments(): Sale
    {
        $product = Product::factory()->create([
            'created_by' => $this->user->id,
        ]);

        $sale = Sale::create([
            'invoice_number' => 'INV-POS-0001',
            'outlet_id' => $this->outlet->id,
            'cash_session_id' => $this->session->id,
            'user_id' => $this->user->id,
            'sale_date' => now()->toDateString(),
            'sales_type' => 'regular',
            'subtotal' => 25000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 25000,
            'status' => 'completed',
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'quantity' => 1,
            'unit_price' => 25000,
            'discount_amount' => 0,
            'subtotal' => 25000,
            'cogs' => 0,
        ]);

        return $sale;
    }
}
