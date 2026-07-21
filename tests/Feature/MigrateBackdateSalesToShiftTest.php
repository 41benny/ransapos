<?php

namespace Tests\Feature;

use App\Models\CashSession;
use App\Models\CashSessionPackagingClosing;
use App\Models\CashSessionPackagingOpening;
use App\Models\Outlet;
use App\Models\PackagingItem;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPackagingMapping;
use App\Models\Sale;
use App\Models\User;
use App\Services\PackagingService;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MigrateBackdateSalesToShiftTest extends TestCase
{
    use RefreshDatabase;

    private array $ctx;

    /**
     * Skenario Kemiling 20 Juli: shift kasir menjual 8 cup dan ditutup dengan kas pas,
     * lalu satu transaksi menyusul lewat sesi koreksi backdate.
     */
    private function seedScenario(): array
    {
        $admin = User::factory()->create();
        $cashier = User::factory()->create();
        $outlet = Outlet::factory()->create(['code' => 'MBK2', 'tax_rate' => 0, 'service_charge_rate' => 0]);
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'product_type' => 'service',
            'is_sellable' => true,
            'selling_price' => 12000,
        ]);
        $method = PaymentMethod::create(['code' => 'QRIS_BANK', 'name' => 'QRIS Bank', 'is_active' => true]);
        $date = today()->subDay();

        $cup = PackagingItem::create(['name' => 'Cup', 'unit' => 'pcs', 'is_active' => true, 'sort_order' => 1]);
        ProductPackagingMapping::create([
            'product_id' => $product->id,
            'packaging_item_id' => $cup->id,
            'qty_per_product' => 1,
            'is_active' => true,
        ]);

        $shift = CashSession::create([
            'session_number' => 'CS-MBK2-001',
            'session_type' => 'regular',
            'outlet_id' => $outlet->id,
            'user_id' => $cashier->id,
            'opening_balance' => 250000,
            'expected_balance' => 250000,
            'opened_at' => $date->copy()->setTime(10, 20),
            'status' => 'open',
        ]);

        CashSessionPackagingOpening::create([
            'cash_session_id' => $shift->id,
            'packaging_item_id' => $cup->id,
            'opening_qty' => 60,
            'source_last_closing_qty' => 60,
            'is_manual_corrected' => false,
            'created_by' => $cashier->id,
        ]);

        app(SaleService::class)->createSale([
            'outlet_id' => $outlet->id,
            'cash_session_id' => $shift->id,
            'user_id' => $cashier->id,
            'items' => [['product_id' => $product->id, 'quantity' => 8, 'unit_price' => 12000, 'discount_amount' => 0]],
            'payment_method_id' => $method->id,
            'payment_amount' => 96000,
            'sale_date' => $date->toDateString(),
        ]);

        app(PackagingService::class)->saveClosings($shift, [$cup->id => 52]);

        $shift->refresh();
        $shift->update([
            'actual_balance' => $shift->expected_balance,
            'difference' => 0,
            'closed_at' => $date->copy()->setTime(21, 32),
            'status' => 'closed',
        ]);

        // Sesi koreksi backdate berisi transaksi yang lupa diinput.
        $backdateSession = CashSession::create([
            'session_number' => 'CS-BACKDATE-MBK2-U' . $admin->id,
            'session_type' => 'backdate_correction',
            'business_date' => $date->toDateString(),
            'outlet_id' => $outlet->id,
            'user_id' => $admin->id,
            'opening_balance' => 0,
            'expected_balance' => 0,
            'opened_at' => $date->copy()->startOfDay(),
            'status' => 'open',
        ]);

        $backdateSale = app(SaleService::class)->createSale([
            'outlet_id' => $outlet->id,
            'cash_session_id' => $backdateSession->id,
            'user_id' => $admin->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 12000, 'discount_amount' => 0]],
            'payment_method_id' => $method->id,
            'payment_amount' => 12000,
            'sale_date' => $date->toDateString(),
            'is_backdated' => true,
        ]);

        return compact('shift', 'backdateSession', 'backdateSale', 'cup', 'outlet', 'product', 'method', 'admin', 'date');
    }

    private function cupClosing(int $sessionId, int $cupId): ?CashSessionPackagingClosing
    {
        return CashSessionPackagingClosing::where('cash_session_id', $sessionId)
            ->where('packaging_item_id', $cupId)
            ->first();
    }

    public function test_dry_run_reports_changes_without_touching_data(): void
    {
        ['shift' => $shift, 'backdateSession' => $backdateSession, 'backdateSale' => $sale, 'cup' => $cup] = $this->seedScenario();

        $salesBefore = (float) $shift->fresh()->total_sales;

        $this->artisan('backdate-sales:migrate-to-shift')
            ->assertSuccessful();

        // Tidak ada yang berubah setelah simulasi.
        $this->assertEquals($backdateSession->id, $sale->fresh()->cash_session_id);
        $this->assertEquals($salesBefore, (float) $shift->fresh()->total_sales);
        $this->assertEquals('open', $backdateSession->fresh()->status);
        $this->assertEquals(8, (float) $this->cupClosing($shift->id, $cup->id)->estimated_sales_used_qty);
        $this->assertEquals(0, (float) $this->cupClosing($shift->id, $cup->id)->difference_qty);
    }

    public function test_execute_moves_sale_recomputes_packaging_and_freezes_cash(): void
    {
        ['shift' => $shift, 'backdateSession' => $backdateSession, 'backdateSale' => $sale, 'cup' => $cup] = $this->seedScenario();

        $before = $shift->fresh();
        $expectedBefore = (float) $before->expected_balance;
        $salesBefore = (float) $before->total_sales;
        $nonCashBefore = (float) $before->total_non_cash;

        $this->artisan('backdate-sales:migrate-to-shift --apply')
            ->assertSuccessful();

        $sale->refresh();
        $after = $shift->fresh();

        // Transaksi menempel ke shift asli.
        $this->assertEquals($shift->id, $sale->cash_session_id);

        // Rekap penjualan ikut naik, saldo kas shift tertutup dibekukan.
        $this->assertEquals($salesBefore + 12000, (float) $after->total_sales);
        $this->assertEquals($nonCashBefore + 12000, (float) $after->total_non_cash);
        $this->assertEquals($expectedBefore, (float) $after->expected_balance);
        $this->assertEquals(0, (float) $after->difference);

        // Selisih packaging yang tadinya tersembunyi kini muncul.
        $closing = $this->cupClosing($shift->id, $cup->id);
        $this->assertEquals(52, (float) $closing->closing_physical_qty);
        $this->assertEquals(9, (float) $closing->estimated_sales_used_qty);
        $this->assertEquals(-1, (float) $closing->difference_qty);

        // Sesi koreksi kosong ditutup.
        $emptied = $backdateSession->fresh();
        $this->assertEquals('closed', $emptied->status);
        $this->assertSame(0, Sale::where('cash_session_id', $emptied->id)->count());
    }

    public function test_ambiguous_date_is_skipped_until_mapped(): void
    {
        $ctx = $this->seedScenario();

        // Shift kedua di tanggal yang sama membuat tujuan jadi ambigu.
        $second = CashSession::create([
            'session_number' => 'CS-MBK2-002',
            'session_type' => 'regular',
            'outlet_id' => $ctx['outlet']->id,
            'user_id' => User::factory()->create()->id,
            'opening_balance' => 0,
            'opened_at' => $ctx['date']->copy()->setTime(6, 0),
            'closed_at' => $ctx['date']->copy()->setTime(9, 0),
            'status' => 'closed',
        ]);

        $this->artisan('backdate-sales:migrate-to-shift --apply')->assertSuccessful();
        $this->assertEquals($ctx['backdateSession']->id, $ctx['backdateSale']->fresh()->cash_session_id);

        // Dengan --map, tujuan ditentukan eksplisit.
        $this->artisan('backdate-sales:migrate-to-shift --apply --map=' . $ctx['backdateSession']->id . ':' . $second->id)
            ->assertSuccessful();
        $this->assertEquals($second->id, $ctx['backdateSale']->fresh()->cash_session_id);
    }

    public function test_cancelled_sale_moves_without_double_correcting_cash(): void
    {
        $ctx = $this->seedScenario();

        app(SaleService::class)->cancelSale($ctx['backdateSale']->id, 'Salah input');

        $shiftSalesBefore = (float) $ctx['shift']->fresh()->total_sales;

        $this->artisan('backdate-sales:migrate-to-shift --apply')->assertSuccessful();

        $this->assertEquals($ctx['shift']->id, $ctx['backdateSale']->fresh()->cash_session_id);

        // Nilainya sudah dikurangkan saat pembatalan, jadi shift tujuan tidak boleh ikut naik.
        $this->assertEquals($shiftSalesBefore, (float) $ctx['shift']->fresh()->total_sales);

        // Transaksi batal tidak dihitung ke estimasi packaging.
        $this->assertEquals(8, (float) $this->cupClosing($ctx['shift']->id, $ctx['cup']->id)->estimated_sales_used_qty);
    }
}

