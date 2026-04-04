<?php

namespace Tests\Feature;

use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductCost;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Stock;
use App\Models\StockMutation;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepairPurchaseHppByQuantityCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        foreach (glob(storage_path('app/private/repairs/purchase_hpp_repair_*.json')) ?: [] as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    public function test_command_dry_run_and_apply_update_purchase_and_stock_cost_data(): void
    {
        $user = User::factory()->create(['id' => 1, 'is_active' => true]);
        $outlet = Outlet::factory()->create(['id' => 6, 'code' => 'OUT006', 'name' => 'Outlet Audit']);
        $supplier = Supplier::factory()->create(['id' => 4, 'code' => 'SUP004', 'name' => 'Supplier Audit']);
        $product = Product::factory()->create([
            'id' => 156,
            'sku' => '000182',
            'name' => 'Nasi',
            'unit' => 'GR',
            'product_type' => 'raw_material',
            'purchase_price' => 16.54,
            'created_by' => $user->id,
        ]);

        Purchase::unguarded(function () use ($outlet, $supplier, $user): void {
            Purchase::query()->create([
                'id' => 112,
                'purchase_number' => 'PO-112',
                'outlet_id' => $outlet->id,
                'supplier_id' => $supplier->id,
                'purchase_date' => '2026-03-15',
                'status' => 'received',
                'received_at' => '2026-03-27 15:31:47',
                'received_by' => $user->id,
                'subtotal' => 163000,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => 163000,
                'payment_status' => 'paid',
                'created_by' => $user->id,
            ]);

            Purchase::query()->create([
                'id' => 113,
                'purchase_number' => 'PO-113',
                'outlet_id' => $outlet->id,
                'supplier_id' => $supplier->id,
                'purchase_date' => '2026-03-19',
                'status' => 'received',
                'received_at' => '2026-03-27 15:37:24',
                'received_by' => $user->id,
                'subtotal' => 326000,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => 326000,
                'payment_status' => 'paid',
                'created_by' => $user->id,
            ]);
        });

        PurchaseItem::unguarded(function () use ($product): void {
            PurchaseItem::query()->create([
                'id' => 603,
                'purchase_id' => 112,
                'product_id' => $product->id,
                'quantity' => 1000,
                'unit_price' => 163,
                'discount_amount' => 0,
                'subtotal' => 163000,
            ]);

            PurchaseItem::query()->create([
                'id' => 614,
                'purchase_id' => 113,
                'product_id' => $product->id,
                'quantity' => 2000,
                'unit_price' => 163,
                'discount_amount' => 0,
                'subtotal' => 326000,
            ]);
        });

        Stock::query()->create([
            'product_id' => $product->id,
            'outlet_id' => $outlet->id,
            'quantity' => 2860,
            'last_mutation_at' => '2026-03-27 10:02:27',
        ]);

        ProductCost::query()->create([
            'product_id' => $product->id,
            'outlet_id' => $outlet->id,
            'avg_cost' => 163,
            'last_calculated_at' => '2026-03-27 08:37:24',
        ]);

        StockMutation::unguarded(function () use ($product, $outlet, $user): void {
            StockMutation::query()->create([
                'id' => 1,
                'product_id' => $product->id,
                'outlet_id' => $outlet->id,
                'mutation_type' => 'adjustment',
                'quantity' => -100,
                'unit_cost' => 17,
                'total_cost' => 1700,
                'stock_before' => 0,
                'stock_after' => -100,
                'reference_type' => 'stock_opname',
                'reference_id' => null,
                'mutation_date' => '2026-03-26',
                'notes' => 'Saldo awal',
                'created_by' => $user->id,
                'created_at' => '2026-03-26 23:59:00',
                'updated_at' => '2026-03-26 23:59:00',
            ]);

            StockMutation::query()->create([
                'id' => 11,
                'product_id' => $product->id,
                'outlet_id' => $outlet->id,
                'mutation_type' => 'out',
                'quantity' => -10,
                'unit_cost' => 17,
                'total_cost' => 170,
                'stock_before' => -100,
                'stock_after' => -110,
                'reference_type' => 'sale',
                'reference_id' => 12001,
                'mutation_date' => '2026-03-27',
                'notes' => 'Penjualan sebelum purchase',
                'created_by' => $user->id,
                'created_at' => '2026-03-27 07:00:00',
                'updated_at' => '2026-03-27 07:00:00',
            ]);

            StockMutation::query()->create([
                'id' => 20,
                'product_id' => $product->id,
                'outlet_id' => $outlet->id,
                'mutation_type' => 'in',
                'quantity' => 1000,
                'unit_cost' => 163,
                'total_cost' => 163000,
                'stock_before' => -110,
                'stock_after' => 890,
                'reference_type' => 'purchase',
                'reference_id' => 112,
                'mutation_date' => '2026-03-27',
                'notes' => 'Pembelian',
                'created_by' => $user->id,
                'created_at' => '2026-03-27 08:31:47',
                'updated_at' => '2026-03-27 08:31:47',
            ]);

            StockMutation::query()->create([
                'id' => 30,
                'product_id' => $product->id,
                'outlet_id' => $outlet->id,
                'mutation_type' => 'in',
                'quantity' => 2000,
                'unit_cost' => 163,
                'total_cost' => 326000,
                'stock_before' => 890,
                'stock_after' => 2890,
                'reference_type' => 'purchase',
                'reference_id' => 113,
                'mutation_date' => '2026-03-27',
                'notes' => 'Pembelian',
                'created_by' => $user->id,
                'created_at' => '2026-03-27 08:37:24',
                'updated_at' => '2026-03-27 08:37:24',
            ]);

            StockMutation::query()->create([
                'id' => 40,
                'product_id' => $product->id,
                'outlet_id' => $outlet->id,
                'mutation_type' => 'out',
                'quantity' => -30,
                'unit_cost' => 163,
                'total_cost' => 4890,
                'stock_before' => 2890,
                'stock_after' => 2860,
                'reference_type' => 'sale',
                'reference_id' => 12755,
                'mutation_date' => '2026-03-27',
                'notes' => 'Penjualan sesudah purchase',
                'created_by' => $user->id,
                'created_at' => '2026-03-27 10:02:27',
                'updated_at' => '2026-03-27 10:02:27',
            ]);
        });

        $this->artisan('repair:purchase-hpp-by-qty')
            ->expectsOutputToContain('"status": "dry-run"')
            ->assertExitCode(0);

        $this->assertDatabaseHas('purchase_items', [
            'id' => 603,
            'quantity' => 1000,
            'unit_price' => 163,
        ]);

        $this->artisan('repair:purchase-hpp-by-qty --apply')
            ->expectsOutputToContain('"status": "applied"')
            ->assertExitCode(0);

        $purchaseItem112 = PurchaseItem::query()->findOrFail(603);
        $purchaseItem113 = PurchaseItem::query()->findOrFail(614);
        $purchaseMutation112 = StockMutation::query()->findOrFail(20);
        $purchaseMutation113 = StockMutation::query()->findOrFail(30);
        $saleMutation = StockMutation::query()->findOrFail(40);
        $productCost = ProductCost::query()->where('product_id', 156)->where('outlet_id', 6)->firstOrFail();
        $stock = Stock::query()->where('product_id', 156)->where('outlet_id', 6)->firstOrFail();

        $this->assertSame('10000.00', $purchaseItem112->quantity);
        $this->assertSame('16.30', $purchaseItem112->unit_price);
        $this->assertSame('163000.00', $purchaseItem112->subtotal);

        $this->assertSame('20000.00', $purchaseItem113->quantity);
        $this->assertSame('16.30', $purchaseItem113->unit_price);
        $this->assertSame('326000.00', $purchaseItem113->subtotal);

        $this->assertSame('10000.00', $purchaseMutation112->quantity);
        $this->assertSame('16.30', $purchaseMutation112->unit_cost);
        $this->assertSame('163000.00', $purchaseMutation112->total_cost);
        $this->assertSame('-110.00', $purchaseMutation112->stock_before);
        $this->assertSame('9890.00', $purchaseMutation112->stock_after);

        $this->assertSame('20000.00', $purchaseMutation113->quantity);
        $this->assertSame('16.30', $purchaseMutation113->unit_cost);
        $this->assertSame('326000.00', $purchaseMutation113->total_cost);
        $this->assertSame('9890.00', $purchaseMutation113->stock_before);
        $this->assertSame('29890.00', $purchaseMutation113->stock_after);

        $this->assertSame('16.30', $saleMutation->unit_cost);
        $this->assertSame('489.00', $saleMutation->total_cost);
        $this->assertSame('29890.00', $saleMutation->stock_before);
        $this->assertSame('29860.00', $saleMutation->stock_after);

        $this->assertSame('16.3000', $productCost->avg_cost);
        $this->assertSame('29860.00', $stock->quantity);

        $this->assertNotEmpty(glob(storage_path('app/private/repairs/purchase_hpp_repair_*.json')) ?: []);
    }
}
