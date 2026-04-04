<?php

namespace Tests\Feature;

use App\Models\CashSession;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMutation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepairSaleItemCogsFromStockCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        foreach (glob(storage_path('app/private/repairs/sale_item_cogs_repair_*.json')) ?: [] as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    public function test_command_dry_run_and_apply_sync_sale_item_cogs_from_stock_mutations(): void
    {
        [$user, $outlet, $cashSession, $category] = $this->createBaseData();

        $menuNasi = Product::factory()->create([
            'category_id' => $category->id,
            'created_by' => $user->id,
            'sku' => 'FG-NASI-AYAM',
            'name' => 'Nasi ayam lada hitam',
            'unit' => 'PCS',
            'product_type' => 'finished_good',
            'purchase_price' => 0,
            'selling_price' => 29000,
        ]);

        $menuBubur = Product::factory()->create([
            'category_id' => $category->id,
            'created_by' => $user->id,
            'sku' => 'FG-BUBUR',
            'name' => 'Bubur ayam',
            'unit' => 'PCS',
            'product_type' => 'finished_good',
            'purchase_price' => 0,
            'selling_price' => 19600,
        ]);

        $menuService = Product::factory()->create([
            'category_id' => $category->id,
            'created_by' => $user->id,
            'sku' => 'SRV-ES-TEH',
            'name' => 'Es teh',
            'unit' => 'PCS',
            'product_type' => 'service',
            'purchase_price' => 0,
            'selling_price' => 8000,
        ]);

        $rice = Product::factory()->create([
            'category_id' => $category->id,
            'created_by' => $user->id,
            'sku' => 'RM-NASI',
            'name' => 'Nasi',
            'unit' => 'GR',
            'product_type' => 'raw_material',
            'purchase_price' => 16.3,
            'selling_price' => 0,
        ]);

        $chicken = Product::factory()->create([
            'category_id' => $category->id,
            'created_by' => $user->id,
            'sku' => 'RM-AYAM',
            'name' => 'Ayam',
            'unit' => 'GR',
            'product_type' => 'raw_material',
            'purchase_price' => 42,
            'selling_price' => 0,
        ]);

        $saleA = Sale::query()->create([
            'invoice_number' => 'INV-12755',
            'outlet_id' => $outlet->id,
            'cash_session_id' => $cashSession->id,
            'user_id' => $user->id,
            'sale_date' => '2026-03-27',
            'sales_type' => 'dine_in',
            'subtotal' => 124000,
            'discount_type' => 'none',
            'discount_value' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'service_charge_amount' => 0,
            'rounding_amount' => 0,
            'total_amount' => 124000,
            'status' => 'completed',
        ]);

        $saleB = Sale::query()->create([
            'invoice_number' => 'INV-12756',
            'outlet_id' => $outlet->id,
            'cash_session_id' => $cashSession->id,
            'user_id' => $user->id,
            'sale_date' => '2026-03-28',
            'sales_type' => 'dine_in',
            'subtotal' => 19600,
            'discount_type' => 'none',
            'discount_value' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'service_charge_amount' => 0,
            'rounding_amount' => 0,
            'total_amount' => 19600,
            'status' => 'completed',
        ]);

        $saleItemNasi = SaleItem::query()->create([
            'sale_id' => $saleA->id,
            'product_id' => $menuNasi->id,
            'product_name' => 'Nasi ayam lada hitam',
            'product_sku' => $menuNasi->sku,
            'quantity' => 4,
            'unit_price' => 29000,
            'discount_amount' => 0,
            'subtotal' => 116000,
            'cogs' => 78633.24,
        ]);

        $saleItemService = SaleItem::query()->create([
            'sale_id' => $saleA->id,
            'product_id' => $menuService->id,
            'product_name' => 'Es teh',
            'product_sku' => $menuService->sku,
            'quantity' => 1,
            'unit_price' => 8000,
            'discount_amount' => 0,
            'subtotal' => 8000,
            'cogs' => 0,
        ]);

        $saleItemBubur = SaleItem::query()->create([
            'sale_id' => $saleB->id,
            'product_id' => $menuBubur->id,
            'product_name' => 'Bubur ayam',
            'product_sku' => $menuBubur->sku,
            'quantity' => 1,
            'unit_price' => 19600,
            'discount_amount' => 0,
            'subtotal' => 19600,
            'cogs' => 19600.55,
        ]);

        StockMutation::unguarded(function () use ($rice, $chicken, $outlet, $user, $saleA, $saleB): void {
            StockMutation::query()->create([
                'product_id' => $rice->id,
                'outlet_id' => $outlet->id,
                'mutation_type' => 'out',
                'quantity' => -1000,
                'unit_cost' => 16.3,
                'total_cost' => 16300,
                'stock_before' => 30000,
                'stock_after' => 29000,
                'reference_type' => 'sale',
                'reference_id' => $saleA->id,
                'mutation_date' => '2026-03-27',
                'notes' => 'Penjualan: Nasi ayam lada hitam',
                'created_by' => $user->id,
            ]);

            StockMutation::query()->create([
                'product_id' => $chicken->id,
                'outlet_id' => $outlet->id,
                'mutation_type' => 'out',
                'quantity' => -227.88,
                'unit_cost' => 41.78,
                'total_cost' => 9521.24,
                'stock_before' => 5000,
                'stock_after' => 4772.12,
                'reference_type' => 'sale',
                'reference_id' => $saleA->id,
                'mutation_date' => '2026-03-27',
                'notes' => 'Penjualan: Nasi ayam lada hitam',
                'created_by' => $user->id,
            ]);

            StockMutation::query()->create([
                'product_id' => $rice->id,
                'outlet_id' => $outlet->id,
                'mutation_type' => 'out',
                'quantity' => -150,
                'unit_cost' => 16.3,
                'total_cost' => 2445,
                'stock_before' => 29000,
                'stock_after' => 28850,
                'reference_type' => 'sale',
                'reference_id' => $saleB->id,
                'mutation_date' => '2026-03-28',
                'notes' => 'Penjualan: Bubur ayam',
                'created_by' => $user->id,
            ]);

            StockMutation::query()->create([
                'product_id' => $chicken->id,
                'outlet_id' => $outlet->id,
                'mutation_type' => 'out',
                'quantity' => -60,
                'unit_cost' => 41.43,
                'total_cost' => 2485.55,
                'stock_before' => 4772.12,
                'stock_after' => 4712.12,
                'reference_type' => 'sale',
                'reference_id' => $saleB->id,
                'mutation_date' => '2026-03-28',
                'notes' => 'Penjualan: Bubur ayam',
                'created_by' => $user->id,
            ]);
        });

        $this->artisan('repair:sale-item-cogs-from-stock --outlet-id=6 --date-from=2026-03-27 --date-to=2026-04-04')
            ->expectsOutputToContain('"status": "dry-run"')
            ->assertExitCode(0);

        $this->assertSame('78633.24', $saleItemNasi->fresh()->cogs);
        $this->assertSame('19600.55', $saleItemBubur->fresh()->cogs);
        $this->assertSame('0.00', $saleItemService->fresh()->cogs);

        $this->artisan('repair:sale-item-cogs-from-stock --apply --outlet-id=6 --date-from=2026-03-27 --date-to=2026-04-04')
            ->expectsOutputToContain('"status": "applied"')
            ->assertExitCode(0);

        $this->assertSame('25821.24', $saleItemNasi->fresh()->cogs);
        $this->assertSame('4930.55', $saleItemBubur->fresh()->cogs);
        $this->assertSame('0.00', $saleItemService->fresh()->cogs);
    }

    public function test_command_aborts_when_sale_item_keys_are_duplicated(): void
    {
        [$user, $outlet, $cashSession, $category] = $this->createBaseData();

        $menu = Product::factory()->create([
            'category_id' => $category->id,
            'created_by' => $user->id,
            'sku' => 'FG-NASI-DUP',
            'name' => 'Nasi campur',
            'unit' => 'PCS',
            'product_type' => 'finished_good',
            'purchase_price' => 0,
            'selling_price' => 35000,
        ]);

        $component = Product::factory()->create([
            'category_id' => $category->id,
            'created_by' => $user->id,
            'sku' => 'RM-NASI-DUP',
            'name' => 'Nasi',
            'unit' => 'GR',
            'product_type' => 'raw_material',
            'purchase_price' => 16.3,
            'selling_price' => 0,
        ]);

        $sale = Sale::query()->create([
            'invoice_number' => 'INV-DUP-1',
            'outlet_id' => $outlet->id,
            'cash_session_id' => $cashSession->id,
            'user_id' => $user->id,
            'sale_date' => '2026-03-27',
            'sales_type' => 'dine_in',
            'subtotal' => 70000,
            'discount_type' => 'none',
            'discount_value' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'service_charge_amount' => 0,
            'rounding_amount' => 0,
            'total_amount' => 70000,
            'status' => 'completed',
        ]);

        SaleItem::query()->create([
            'sale_id' => $sale->id,
            'product_id' => $menu->id,
            'product_name' => 'Nasi campur',
            'product_sku' => $menu->sku,
            'quantity' => 1,
            'unit_price' => 35000,
            'discount_amount' => 0,
            'subtotal' => 35000,
            'cogs' => 50000,
        ]);

        SaleItem::query()->create([
            'sale_id' => $sale->id,
            'product_id' => $menu->id,
            'product_name' => 'Nasi campur',
            'product_sku' => $menu->sku,
            'quantity' => 1,
            'unit_price' => 35000,
            'discount_amount' => 0,
            'subtotal' => 35000,
            'cogs' => 50000,
        ]);

        StockMutation::unguarded(function () use ($component, $outlet, $user, $sale): void {
            StockMutation::query()->create([
                'product_id' => $component->id,
                'outlet_id' => $outlet->id,
                'mutation_type' => 'out',
                'quantity' => -300,
                'unit_cost' => 16.3,
                'total_cost' => 4890,
                'stock_before' => 3000,
                'stock_after' => 2700,
                'reference_type' => 'sale',
                'reference_id' => $sale->id,
                'mutation_date' => '2026-03-27',
                'notes' => 'Penjualan: Nasi campur',
                'created_by' => $user->id,
            ]);
        });

        $this->artisan('repair:sale-item-cogs-from-stock --outlet-id=6 --date-from=2026-03-27 --date-to=2026-04-04')
            ->expectsOutputToContain('sale_items duplikat')
            ->assertExitCode(1);
    }

    private function createBaseData(): array
    {
        $user = User::factory()->create([
            'id' => 1,
            'is_active' => true,
        ]);

        $outlet = Outlet::factory()->create([
            'id' => 6,
            'code' => 'OUT006',
            'name' => 'Outlet Audit',
        ]);

        $cashSession = CashSession::create([
            'session_number' => 'CS-AUDIT-1',
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

        $category = ProductCategory::factory()->create([
            'code' => 'AUDIT',
            'name' => 'Audit',
        ]);

        return [$user, $outlet, $cashSession, $category];
    }
}
