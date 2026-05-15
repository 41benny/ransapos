<?php

namespace Tests\Unit\Services;

use App\Models\CashSession;
use App\Models\Outlet;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Services\SalesJournalExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesJournalExportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_monthly_rows_generates_sales_rows_before_hpp_can_be_appended(): void
    {
        $user = User::factory()->create();
        $category = ProductCategory::factory()->create();
        $outlet = Outlet::factory()->create([
            'name' => 'Pahoman',
            'code' => 'PHM',
            'is_active' => true,
        ]);

        $cash = PaymentMethod::query()->create(['code' => 'CASH', 'name' => 'Cash', 'is_active' => true]);
        $qris = PaymentMethod::query()->create(['code' => 'QRIS', 'name' => 'QRIS', 'is_active' => true]);

        $product = Product::query()->create([
            'sku' => 'PRD-SALES-JOURNAL-001',
            'name' => 'Produk Jurnal Sales',
            'category_id' => $category->id,
            'unit' => 'pcs',
            'product_type' => 'finished_good',
            'purchase_price' => 10000,
            'selling_price' => 100000,
            'min_stock' => 0,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $session = CashSession::query()->create([
            'session_number' => 'CS-PHM-SALES-001',
            'outlet_id' => $outlet->id,
            'user_id' => $user->id,
            'opening_balance' => 0,
            'expected_balance' => 0,
            'difference' => 0,
            'total_sales' => 0,
            'total_cash' => 0,
            'total_non_cash' => 0,
            'opened_at' => '2026-02-01 08:00:00',
            'status' => 'open',
        ]);

        $sale = Sale::query()->create([
            'invoice_number' => 'INV-SALES-JOURNAL-001',
            'outlet_id' => $outlet->id,
            'cash_session_id' => $session->id,
            'user_id' => $user->id,
            'sale_date' => '2026-02-10',
            'sales_type' => 'regular',
            'subtotal' => 90000,
            'discount_type' => 'fixed',
            'discount_value' => 5000,
            'discount_amount' => 5000,
            'tax_amount' => 9000,
            'service_charge_amount' => 0,
            'rounding_amount' => 0,
            'total_amount' => 94000,
            'status' => 'completed',
        ]);

        SaleItem::query()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'product_name' => 'Produk Jurnal Sales',
            'product_sku' => 'PRD-SALES-JOURNAL',
            'quantity' => 1,
            'unit_price' => 100000,
            'discount_amount' => 10000,
            'subtotal' => 90000,
            'cogs' => 50000,
        ]);

        Payment::query()->create(['sale_id' => $sale->id, 'payment_method_id' => $cash->id, 'amount' => 50000]);
        Payment::query()->create(['sale_id' => $sale->id, 'payment_method_id' => $qris->id, 'amount' => 44000]);

        $rows = app(SalesJournalExportService::class)->buildMonthlyRows('2026-02', [$outlet->id]);

        $this->assertSame('SALES', $rows[0]['STATUS']);
        $this->assertSame('SALPHM0226', $rows[0]['_VOUCHER']);
        $this->assertSame(4101005, $rows[0]['NO_AKUN']);
        $this->assertSame('K', $rows[0]['J_MUTASI']);
        $this->assertSame(100000.0, (float) $rows[0]['J_JUMLAH']);
        $this->assertTrue(collect($rows)->contains(fn ($row) => $row['NO_AKUN'] === 5103005 && (float) $row['J_JUMLAH'] === 15000.0));
        $this->assertTrue(collect($rows)->contains(fn ($row) => $row['NO_AKUN'] === 1102006 && (float) $row['J_JUMLAH'] === 50000.0));
        $this->assertTrue(collect($rows)->contains(fn ($row) => $row['NO_AKUN'] === 1102015 && (float) $row['J_JUMLAH'] === 44000.0));
    }

    public function test_build_monthly_rows_maps_transmart_sales_to_4101011(): void
    {
        $user = User::factory()->create();
        $category = ProductCategory::factory()->create();
        $outlet = Outlet::factory()->create([
            'name' => 'Transmart',
            'code' => 'TRM',
            'is_active' => true,
        ]);

        $cash = PaymentMethod::query()->create(['code' => 'CASH', 'name' => 'Cash', 'is_active' => true]);
        $product = Product::query()->create([
            'sku' => 'PRD-SALES-JOURNAL-TRM',
            'name' => 'Produk Jurnal Transmart',
            'category_id' => $category->id,
            'unit' => 'pcs',
            'product_type' => 'finished_good',
            'purchase_price' => 10000,
            'selling_price' => 100000,
            'min_stock' => 0,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $session = CashSession::query()->create([
            'session_number' => 'CS-TRM-SALES-001',
            'outlet_id' => $outlet->id,
            'user_id' => $user->id,
            'opening_balance' => 0,
            'expected_balance' => 0,
            'difference' => 0,
            'total_sales' => 0,
            'total_cash' => 0,
            'total_non_cash' => 0,
            'opened_at' => '2026-02-01 08:00:00',
            'status' => 'open',
        ]);

        $sale = Sale::query()->create([
            'invoice_number' => 'INV-SALES-JOURNAL-TRM',
            'outlet_id' => $outlet->id,
            'cash_session_id' => $session->id,
            'user_id' => $user->id,
            'sale_date' => '2026-02-10',
            'sales_type' => 'regular',
            'subtotal' => 100000,
            'discount_type' => 'none',
            'discount_value' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'service_charge_amount' => 0,
            'rounding_amount' => 0,
            'total_amount' => 100000,
            'status' => 'completed',
        ]);

        SaleItem::query()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'product_name' => 'Produk Jurnal Transmart',
            'product_sku' => 'PRD-SALES-JOURNAL-TRM',
            'quantity' => 1,
            'unit_price' => 100000,
            'discount_amount' => 0,
            'subtotal' => 100000,
            'cogs' => 50000,
        ]);

        Payment::query()->create(['sale_id' => $sale->id, 'payment_method_id' => $cash->id, 'amount' => 100000]);

        $rows = app(SalesJournalExportService::class)->buildMonthlyRows('2026-02', [$outlet->id]);

        $this->assertSame('SALTRM0226', $rows[0]['_VOUCHER']);
        $this->assertSame(4101011, $rows[0]['NO_AKUN']);
        $this->assertSame('PENJUALAN TRANSMART', $rows[0]['KET 2']);
        $this->assertFalse(collect($rows)->contains(fn ($row) => $row['NO_AKUN'] === 4101010));
    }

    public function test_build_monthly_rows_maps_central_plaza_to_cp_not_ctr(): void
    {
        $user = User::factory()->create();
        $category = ProductCategory::factory()->create();
        $outlet = Outlet::factory()->create([
            'name' => 'Domesteak CENTRAL PLAZA',
            'code' => 'OUT02',
            'is_active' => true,
        ]);

        $cash = PaymentMethod::query()->create(['code' => 'CASH', 'name' => 'Cash', 'is_active' => true]);
        $product = Product::query()->create([
            'sku' => 'PRD-SALES-JOURNAL-CP',
            'name' => 'Produk Jurnal CP',
            'category_id' => $category->id,
            'unit' => 'pcs',
            'product_type' => 'finished_good',
            'purchase_price' => 10000,
            'selling_price' => 100000,
            'min_stock' => 0,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $session = CashSession::query()->create([
            'session_number' => 'CS-CP-SALES-001',
            'outlet_id' => $outlet->id,
            'user_id' => $user->id,
            'opening_balance' => 0,
            'expected_balance' => 0,
            'difference' => 0,
            'total_sales' => 0,
            'total_cash' => 0,
            'total_non_cash' => 0,
            'opened_at' => '2026-02-01 08:00:00',
            'status' => 'open',
        ]);

        $sale = Sale::query()->create([
            'invoice_number' => 'INV-SALES-JOURNAL-CP',
            'outlet_id' => $outlet->id,
            'cash_session_id' => $session->id,
            'user_id' => $user->id,
            'sale_date' => '2026-02-10',
            'sales_type' => 'regular',
            'subtotal' => 100000,
            'discount_type' => 'none',
            'discount_value' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'service_charge_amount' => 0,
            'rounding_amount' => 0,
            'total_amount' => 100000,
            'status' => 'completed',
        ]);

        SaleItem::query()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'product_name' => 'Produk Jurnal CP',
            'product_sku' => 'PRD-SALES-JOURNAL-CP',
            'quantity' => 1,
            'unit_price' => 100000,
            'discount_amount' => 0,
            'subtotal' => 100000,
            'cogs' => 50000,
        ]);

        Payment::query()->create(['sale_id' => $sale->id, 'payment_method_id' => $cash->id, 'amount' => 100000]);

        $rows = app(SalesJournalExportService::class)->buildMonthlyRows('2026-02', [$outlet->id]);

        $this->assertSame('SALCP0226', $rows[0]['_VOUCHER']);
        $this->assertSame(4101001, $rows[0]['NO_AKUN']);
        $this->assertSame('Penjualan CP', $rows[0]['KET 2']);
        $this->assertFalse(collect($rows)->contains(fn ($row) => $row['_VOUCHER'] === 'SALCTR0226' || $row['NO_AKUN'] === 4101010));
    }
}

