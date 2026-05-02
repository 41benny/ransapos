<?php

namespace Tests\Unit\Services;

use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockMutation;
use App\Models\StockTransfer;
use App\Models\User;
use App\Services\InventoryMutationJournalExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryMutationJournalExportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_monthly_rows_generates_direct_transfer_rows_without_transit_account(): void
    {
        $user = User::factory()->create();
        $category = ProductCategory::factory()->create();
        $product = $this->createProduct($category->id, $user->id);
        $central = Outlet::factory()->create(['name' => 'Central', 'code' => 'CTR', 'is_active' => true]);
        $pahoman = Outlet::factory()->create(['name' => 'Pahoman', 'code' => 'PHM', 'is_active' => true]);
        $transfer = $this->createTransfer($central->id, $pahoman->id, '2026-04-20', 'received', $user->id);

        $this->createMutation($product->id, $central->id, 'transfer_out', -2, 50000, '2026-04-20', 'stock_transfer', $transfer->id);
        $this->createMutation($product->id, $pahoman->id, 'transfer_in', 2, 50000, '2026-04-21', 'stock_transfer', $transfer->id);
        $this->createMutation($product->id, $pahoman->id, 'out', -1, 25000, '2026-04-22', 'sale', $transfer->id);
        $this->createMutation($product->id, $pahoman->id, 'in', 1, 25000, '2026-04-23', 'purchase', $transfer->id);

        $rows = app(InventoryMutationJournalExportService::class)->buildMonthlyRows('2026-04');

        $this->assertCount(2, $rows);
        $this->assertFalse(collect($rows)->contains(fn ($row) => $row['NO_AKUN'] === 1117999));
        $this->assertTrue(collect($rows)->contains(fn ($row) => $row['NO_AKUN'] === 1117006 && $row['J_MUTASI'] === 'D' && (float) $row['J_JUMLAH'] === 50000.0));
        $this->assertTrue(collect($rows)->contains(fn ($row) => $row['NO_AKUN'] === 1117001 && $row['J_MUTASI'] === 'K' && (float) $row['J_JUMLAH'] === 50000.0));
        $this->assertSame(
            (float) collect($rows)->sum(fn ($row) => (float) ($row['D'] ?? 0)),
            (float) collect($rows)->sum(fn ($row) => (float) ($row['K'] ?? 0))
        );
    }

    public function test_build_monthly_rows_maps_transfer_to_transmart_inventory_account(): void
    {
        $user = User::factory()->create();
        $category = ProductCategory::factory()->create();
        $product = $this->createProduct($category->id, $user->id);
        $central = Outlet::factory()->create(['name' => 'Central', 'code' => 'CTR', 'is_active' => true]);
        $transmart = Outlet::factory()->create(['name' => 'Transmart', 'code' => 'TRM', 'is_active' => true]);
        $transfer = $this->createTransfer($central->id, $transmart->id, '2026-04-20', 'received', $user->id);

        $this->createMutation($product->id, $central->id, 'transfer_out', -2, 88000, '2026-04-20', 'stock_transfer', $transfer->id);
        $this->createMutation($product->id, $transmart->id, 'transfer_in', 2, 88000, '2026-04-20', 'stock_transfer', $transfer->id);

        $rows = app(InventoryMutationJournalExportService::class)->buildMonthlyRows('2026-04', [$transmart->id]);

        $this->assertCount(2, $rows);
        $this->assertTrue(collect($rows)->contains(fn ($row) => $row['NO_AKUN'] === 1117011 && $row['J_MUTASI'] === 'D' && $row['KET 2'] === 'Persediaan Barang Dagang Transmart'));
        $this->assertTrue(collect($rows)->contains(fn ($row) => $row['NO_AKUN'] === 1117001 && $row['J_MUTASI'] === 'K' && $row['KET 2'] === 'Persediaan Barang Dagang Central'));
        $this->assertFalse(collect($rows)->contains(fn ($row) => $row['NO_AKUN'] === 1117999));
    }

    public function test_build_monthly_rows_ignores_later_transfer_in_to_avoid_duplicate_direct_transfer_journal(): void
    {
        $user = User::factory()->create();
        $category = ProductCategory::factory()->create();
        $product = $this->createProduct($category->id, $user->id);
        $central = Outlet::factory()->create(['name' => 'Central', 'code' => 'CTR', 'is_active' => true]);
        $pahoman = Outlet::factory()->create(['name' => 'Pahoman', 'code' => 'PHM', 'is_active' => true]);
        $transfer = $this->createTransfer($central->id, $pahoman->id, '2026-04-30', 'received', $user->id);

        $this->createMutation($product->id, $central->id, 'transfer_out', -3, 75000, '2026-04-30', 'stock_transfer', $transfer->id);
        $this->createMutation($product->id, $pahoman->id, 'transfer_in', 3, 75000, '2026-05-01', 'stock_transfer', $transfer->id);

        $aprilRows = app(InventoryMutationJournalExportService::class)->buildMonthlyRows('2026-04');
        $mayRows = app(InventoryMutationJournalExportService::class)->buildMonthlyRows('2026-05');

        $this->assertCount(2, $aprilRows);
        $this->assertCount(0, $mayRows);
        $this->assertSame(1117006, $aprilRows[0]['NO_AKUN']);
        $this->assertSame('D', $aprilRows[0]['J_MUTASI']);
        $this->assertSame(1117001, $aprilRows[1]['NO_AKUN']);
        $this->assertSame('K', $aprilRows[1]['J_MUTASI']);
    }

    public function test_build_monthly_rows_handles_positive_and_negative_transfer_adjustments(): void
    {
        $user = User::factory()->create();
        $category = ProductCategory::factory()->create();
        $product = $this->createProduct($category->id, $user->id);
        $central = Outlet::factory()->create(['name' => 'Central', 'code' => 'CTR', 'is_active' => true]);
        $pahoman = Outlet::factory()->create(['name' => 'Pahoman', 'code' => 'PHM', 'is_active' => true]);
        $transfer = $this->createTransfer($central->id, $pahoman->id, '2026-04-20', 'received', $user->id);

        $this->createMutation($product->id, $central->id, 'adjustment', 1, 20000, '2026-04-20', 'stock_transfer', $transfer->id);
        $this->createMutation($product->id, $central->id, 'adjustment', -1, 30000, '2026-04-21', 'stock_transfer', $transfer->id);
        $this->createMutation($product->id, $central->id, 'adjustment', 1, 0, '2026-04-22', 'stock_transfer', $transfer->id);

        $rows = app(InventoryMutationJournalExportService::class)->buildMonthlyRows('2026-04', [$pahoman->id]);

        $this->assertCount(4, $rows);
        $this->assertTrue(collect($rows)->contains(fn ($row) => $row['NO_AKUN'] === 1117001 && $row['J_MUTASI'] === 'D' && (float) $row['J_JUMLAH'] === 20000.0));
        $this->assertTrue(collect($rows)->contains(fn ($row) => $row['NO_AKUN'] === 1117006 && $row['J_MUTASI'] === 'K' && (float) $row['J_JUMLAH'] === 20000.0));
        $this->assertTrue(collect($rows)->contains(fn ($row) => $row['NO_AKUN'] === 1117006 && $row['J_MUTASI'] === 'D' && (float) $row['J_JUMLAH'] === 30000.0));
        $this->assertTrue(collect($rows)->contains(fn ($row) => $row['NO_AKUN'] === 1117001 && $row['J_MUTASI'] === 'K' && (float) $row['J_JUMLAH'] === 30000.0));
    }

    private function createProduct(int $categoryId, int $userId): Product
    {
        return Product::query()->create([
            'sku' => 'PRD-MUT-' . uniqid(),
            'name' => 'Produk Mutasi Persediaan',
            'category_id' => $categoryId,
            'unit' => 'pcs',
            'product_type' => 'finished_good',
            'purchase_price' => 25000,
            'selling_price' => 50000,
            'min_stock' => 0,
            'is_active' => true,
            'created_by' => $userId,
        ]);
    }

    private function createMutation(
        int $productId,
        int $outletId,
        string $mutationType,
        float $quantity,
        float $totalCost,
        string $mutationDate,
        string $referenceType = 'stock_transfer',
        int $referenceId = 1
    ): StockMutation {
        return StockMutation::query()->create([
            'product_id' => $productId,
            'outlet_id' => $outletId,
            'mutation_type' => $mutationType,
            'quantity' => $quantity,
            'unit_cost' => $quantity != 0 ? $totalCost / abs($quantity) : 0,
            'total_cost' => $totalCost,
            'stock_before' => 10,
            'stock_after' => 10 + $quantity,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'mutation_date' => $mutationDate,
            'notes' => 'Test mutation',
        ]);
    }

    private function createTransfer(int $fromOutletId, int $toOutletId, string $transferDate, string $status, int $userId): StockTransfer
    {
        return StockTransfer::query()->create([
            'transfer_number' => 'TRF-' . uniqid(),
            'from_outlet_id' => $fromOutletId,
            'to_outlet_id' => $toOutletId,
            'transfer_date' => $transferDate,
            'status' => $status,
            'created_by' => $userId,
        ]);
    }
}
