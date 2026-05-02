<?php

namespace Tests\Unit\Services;

use App\Models\CashSession;
use App\Models\CoaAccount;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Services\HppJournalExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HppJournalExportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_monthly_rows_generates_balanced_rows_per_outlet(): void
    {
        $user = User::factory()->create();
        $category = ProductCategory::factory()->create();

        $outletCp = Outlet::factory()->create([
            'name' => 'CP',
            'code' => 'CP',
            'is_active' => true,
        ]);
        $outletCentral = Outlet::factory()->create([
            'name' => 'Reseller Dan Frencise',
            'code' => 'RSL',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'sku' => 'PRD-HPP-001',
            'name' => 'Produk HPP',
            'category_id' => $category->id,
            'unit' => 'pcs',
            'product_type' => 'finished_good',
            'purchase_price' => 10000,
            'selling_price' => 15000,
            'min_stock' => 0,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $cpSession = CashSession::query()->create([
            'session_number' => 'CS-CP-001',
            'outlet_id' => $outletCp->id,
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
        $centralSession = CashSession::query()->create([
            'session_number' => 'CS-CTR-001',
            'outlet_id' => $outletCentral->id,
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

        $this->seedSaleWithCogs($outletCp->id, $cpSession->id, $user->id, $product->id, '2026-02-10', 120000, 'completed');
        $this->seedSaleWithCogs($outletCentral->id, $centralSession->id, $user->id, $product->id, '2026-02-15', 80000, 'completed');
        $this->seedSaleWithCogs($outletCp->id, $cpSession->id, $user->id, $product->id, '2026-02-20', 50000, 'cancelled');

        CoaAccount::query()->create(['code' => '5101001', 'name' => 'Hpp Makanan CP', 'type' => 'expense', 'group' => 'HPP', 'is_active' => true]);
        CoaAccount::query()->create(['code' => '1117002', 'name' => 'Persediaan Barang Dagang CP', 'type' => 'asset', 'group' => 'ASET', 'is_active' => true]);
        CoaAccount::query()->create(['code' => '5101010', 'name' => 'Hpp Makanan Reseller Dan Frencise', 'type' => 'expense', 'group' => 'HPP', 'is_active' => true]);
        CoaAccount::query()->create(['code' => '1117001', 'name' => 'Persediaan Barang Dagang Central', 'type' => 'asset', 'group' => 'ASET', 'is_active' => true]);

        $service = app(HppJournalExportService::class);
        $rows = $service->buildMonthlyRows('2026-02');

        $this->assertCount(4, $rows);

        $cpRows = collect($rows)->where('_VOUCHER', 'HPPCP0226')->values();
        $this->assertCount(2, $cpRows);
        $this->assertSame(5101001, $cpRows[0]['NO_AKUN']);
        $this->assertSame(1117002, $cpRows[1]['NO_AKUN']);
        $this->assertSame(120000.0, (float) $cpRows[0]['J_JUMLAH']);

        $centralRows = collect($rows)->where('_VOUCHER', 'HPPCTR0226')->values();
        $this->assertCount(2, $centralRows);
        $this->assertSame(5101010, $centralRows[0]['NO_AKUN']);
        $this->assertSame(1117001, $centralRows[1]['NO_AKUN']);
        $this->assertSame(80000.0, (float) $centralRows[0]['J_JUMLAH']);

        $this->assertSame(
            (float) collect($rows)->where('J_MUTASI', 'D')->sum('J_JUMLAH'),
            (float) collect($rows)->where('J_MUTASI', 'K')->sum('J_JUMLAH')
        );
    }

    public function test_build_monthly_rows_supports_outlet_filter(): void
    {
        $user = User::factory()->create();
        $category = ProductCategory::factory()->create();
        $outletCp = Outlet::factory()->create([
            'name' => 'CP',
            'code' => 'CP',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'sku' => 'PRD-HPP-002',
            'name' => 'Produk HPP 2',
            'category_id' => $category->id,
            'unit' => 'pcs',
            'product_type' => 'finished_good',
            'purchase_price' => 10000,
            'selling_price' => 16000,
            'min_stock' => 0,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $session = CashSession::query()->create([
            'session_number' => 'CS-CP-002',
            'outlet_id' => $outletCp->id,
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

        $this->seedSaleWithCogs($outletCp->id, $session->id, $user->id, $product->id, '2026-02-10', 45000, 'completed');

        CoaAccount::query()->create(['code' => '5101001', 'name' => 'Hpp Makanan CP', 'type' => 'expense', 'group' => 'HPP', 'is_active' => true]);
        CoaAccount::query()->create(['code' => '1117002', 'name' => 'Persediaan Barang Dagang CP', 'type' => 'asset', 'group' => 'ASET', 'is_active' => true]);

        $service = app(HppJournalExportService::class);
        $rows = $service->buildMonthlyRows('2026-02', [$outletCp->id]);

        $this->assertCount(2, $rows);
        $this->assertSame('HPPCP0226', $rows[0]['_VOUCHER']);
        $this->assertSame(45000.0, (float) $rows[0]['J_JUMLAH']);
    }

    public function test_build_monthly_rows_uses_config_account_names_when_coa_accounts_do_not_exist(): void
    {
        $user = User::factory()->create();
        $category = ProductCategory::factory()->create();
        $outletPahoman = Outlet::factory()->create([
            'name' => 'Pahoman',
            'code' => 'PHM',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'sku' => 'PRD-HPP-004',
            'name' => 'Produk HPP 4',
            'category_id' => $category->id,
            'unit' => 'pcs',
            'product_type' => 'finished_good',
            'purchase_price' => 10000,
            'selling_price' => 16000,
            'min_stock' => 0,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $session = CashSession::query()->create([
            'session_number' => 'CS-PHM-001',
            'outlet_id' => $outletPahoman->id,
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

        $this->seedSaleWithCogs($outletPahoman->id, $session->id, $user->id, $product->id, '2026-02-10', 65000, 'completed');

        $service = app(HppJournalExportService::class);
        $rows = $service->buildMonthlyRows('2026-02', [$outletPahoman->id]);

        $this->assertCount(2, $rows);
        $this->assertSame('HPPPHM0226', $rows[0]['_VOUCHER']);
        $this->assertSame(5101005, $rows[0]['NO_AKUN']);
        $this->assertSame('Hpp Makanan Pahoman', $rows[0]['KET 2']);
        $this->assertSame(1117006, $rows[1]['NO_AKUN']);
        $this->assertSame('Persediaan Barang Dagang Pahoman', $rows[1]['KET 2']);
    }

    public function test_build_monthly_rows_maps_transmart_hpp_to_locked_accounts(): void
    {
        $user = User::factory()->create();
        $category = ProductCategory::factory()->create();
        $outletTransmart = Outlet::factory()->create([
            'name' => 'Transmart',
            'code' => 'TRM',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'sku' => 'PRD-HPP-TRM',
            'name' => 'Produk HPP Transmart',
            'category_id' => $category->id,
            'unit' => 'pcs',
            'product_type' => 'finished_good',
            'purchase_price' => 10000,
            'selling_price' => 16000,
            'min_stock' => 0,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $session = CashSession::query()->create([
            'session_number' => 'CS-TRM-HPP-001',
            'outlet_id' => $outletTransmart->id,
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

        $this->seedSaleWithCogs($outletTransmart->id, $session->id, $user->id, $product->id, '2026-02-10', 77000, 'completed');

        $rows = app(HppJournalExportService::class)->buildMonthlyRows('2026-02', [$outletTransmart->id]);

        $this->assertCount(2, $rows);
        $this->assertSame('HPPTRM0226', $rows[0]['_VOUCHER']);
        $this->assertSame(5101011, $rows[0]['NO_AKUN']);
        $this->assertSame('Hpp Makanan Transmart', $rows[0]['KET 2']);
        $this->assertSame(1117011, $rows[1]['NO_AKUN']);
        $this->assertSame('Persediaan Barang Dagang Transmart', $rows[1]['KET 2']);
        $this->assertFalse(collect($rows)->contains(fn ($row) => in_array($row['NO_AKUN'], [5101010, 1117001], true)));
    }

    public function test_build_monthly_rows_maps_central_plaza_hpp_to_cp_not_ctr(): void
    {
        $user = User::factory()->create();
        $category = ProductCategory::factory()->create();
        $outletCentralPlaza = Outlet::factory()->create([
            'name' => 'MORESTO CENTRAL PLAZA',
            'code' => 'OUT02',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'sku' => 'PRD-HPP-CP',
            'name' => 'Produk HPP CP',
            'category_id' => $category->id,
            'unit' => 'pcs',
            'product_type' => 'finished_good',
            'purchase_price' => 10000,
            'selling_price' => 16000,
            'min_stock' => 0,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $session = CashSession::query()->create([
            'session_number' => 'CS-CP-HPP-001',
            'outlet_id' => $outletCentralPlaza->id,
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

        $this->seedSaleWithCogs($outletCentralPlaza->id, $session->id, $user->id, $product->id, '2026-02-10', 66000, 'completed');

        $rows = app(HppJournalExportService::class)->buildMonthlyRows('2026-02', [$outletCentralPlaza->id]);

        $this->assertCount(2, $rows);
        $this->assertSame('HPPCP0226', $rows[0]['_VOUCHER']);
        $this->assertSame(5101001, $rows[0]['NO_AKUN']);
        $this->assertSame(1117002, $rows[1]['NO_AKUN']);
        $this->assertFalse(collect($rows)->contains(fn ($row) => in_array($row['NO_AKUN'], [5101010, 1117001], true)));
    }

    public function test_build_monthly_rows_skips_unmapped_outlet_without_failing(): void
    {
        $user = User::factory()->create();
        $category = ProductCategory::factory()->create();
        $outletUnmapped = Outlet::factory()->create([
            'name' => 'Outlet Tanpa Mapping',
            'code' => 'OTM',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'sku' => 'PRD-HPP-003',
            'name' => 'Produk HPP 3',
            'category_id' => $category->id,
            'unit' => 'pcs',
            'product_type' => 'finished_good',
            'purchase_price' => 10000,
            'selling_price' => 16000,
            'min_stock' => 0,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $session = CashSession::query()->create([
            'session_number' => 'CS-OTM-001',
            'outlet_id' => $outletUnmapped->id,
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

        $this->seedSaleWithCogs($outletUnmapped->id, $session->id, $user->id, $product->id, '2026-02-10', 50000, 'completed');

        $service = app(HppJournalExportService::class);
        $rows = $service->buildMonthlyRows('2026-02');

        $this->assertSame([], $rows);
    }

    private function seedSaleWithCogs(
        int $outletId,
        int $cashSessionId,
        int $userId,
        int $productId,
        string $saleDate,
        float $cogs,
        string $status
    ): void {
        $sale = Sale::query()->create([
            'invoice_number' => 'INV-' . uniqid(),
            'outlet_id' => $outletId,
            'cash_session_id' => $cashSessionId,
            'user_id' => $userId,
            'sale_date' => $saleDate,
            'subtotal' => 200000,
            'discount_type' => 'none',
            'discount_value' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 200000,
            'status' => $status,
        ]);

        SaleItem::query()->create([
            'sale_id' => $sale->id,
            'product_id' => $productId,
            'product_name' => 'Produk HPP',
            'product_sku' => 'PRD-HPP',
            'quantity' => 1,
            'unit_price' => 200000,
            'discount_amount' => 0,
            'subtotal' => 200000,
            'cogs' => $cogs,
        ]);
    }
}
