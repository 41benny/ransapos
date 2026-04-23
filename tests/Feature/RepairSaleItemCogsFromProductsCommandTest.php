<?php

namespace Tests\Feature;

use App\Models\BomDetail;
use App\Models\BomHeader;
use App\Models\CashSession;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepairSaleItemCogsFromProductsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        foreach (glob(storage_path('app/private/repairs/sale_item_cogs_from_products_*.json')) ?: [] as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    public function test_command_recalculates_zero_cogs_from_product_bom(): void
    {
        [$user, $outlet, $cashSession, $category] = $this->createBaseData();

        $component = Product::factory()->create([
            'category_id' => $category->id,
            'created_by' => $user->id,
            'sku' => '000217',
            'name' => 'Siaw May Ayam',
            'unit' => 'PCS',
            'product_type' => 'raw_material',
            'purchase_price' => 1700,
            'selling_price' => 0,
        ]);

        $frozen = Product::factory()->create([
            'category_id' => $category->id,
            'created_by' => $user->id,
            'sku' => 'PRD-FROZEN-SIAWMAY-AYAM-001',
            'name' => 'Frozen Siawmay Ayam',
            'unit' => 'pak',
            'product_type' => 'finished_good',
            'purchase_price' => 51000,
            'selling_price' => 90000,
        ]);

        $bom = BomHeader::query()->create([
            'product_id' => $frozen->id,
            'name' => 'Resep Frozen Siawmay Ayam',
            'source_type' => 'bundle',
            'is_active' => true,
        ]);

        BomDetail::query()->create([
            'bom_id' => $bom->id,
            'component_product_id' => $component->id,
            'quantity' => 30,
            'uom' => 'PCS',
        ]);

        $sale = Sale::query()->create([
            'invoice_number' => 'INV-FRZ-1',
            'outlet_id' => $outlet->id,
            'cash_session_id' => $cashSession->id,
            'user_id' => $user->id,
            'sale_date' => '2026-04-20',
            'sales_type' => 'regular',
            'subtotal' => 90000,
            'discount_type' => 'none',
            'discount_value' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'service_charge_amount' => 0,
            'rounding_amount' => 0,
            'total_amount' => 90000,
            'status' => 'completed',
        ]);

        $saleItem = SaleItem::query()->create([
            'sale_id' => $sale->id,
            'product_id' => $frozen->id,
            'product_name' => $frozen->name,
            'product_sku' => $frozen->sku,
            'quantity' => 1,
            'unit_price' => 90000,
            'discount_amount' => 0,
            'subtotal' => 90000,
            'cogs' => 0,
        ]);

        $this->artisan('repair:sale-item-cogs-from-products --date-from=2026-04-01 --date-to=2026-04-30 --product-like=Frozen')
            ->expectsOutputToContain('"status": "dry-run"')
            ->assertExitCode(0);

        $this->assertSame('0.00', $saleItem->fresh()->cogs);

        $this->artisan('repair:sale-item-cogs-from-products --apply --date-from=2026-04-01 --date-to=2026-04-30 --product-like=Frozen')
            ->expectsOutputToContain('"status": "applied"')
            ->assertExitCode(0);

        $this->assertSame('51000.00', $saleItem->fresh()->cogs);
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
