<?php

namespace Tests\Feature;

use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Stock;
use App\Models\StockMutation;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockRunningBalanceBackdateTest extends TestCase
{
    use RefreshDatabase;

    protected StockService $stockService;
    protected User $user;
    protected Outlet $outlet;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stockService = app(StockService::class);
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $category = ProductCategory::create([
            'code' => 'RAW',
            'name' => 'Raw Material',
        ]);

        $this->outlet = Outlet::create([
            'name' => 'Gudang Test',
            'code' => 'GDG-T',
        ]);

        $this->product = Product::create([
            'sku' => 'RM-BACKDATE',
            'name' => 'Bahan Backdate',
            'category_id' => $category->id,
            'unit' => 'pcs',
            'purchase_price' => 10000,
            'selling_price' => 15000,
            'product_type' => 'raw_material',
            'min_stock' => 0,
            'is_active' => true,
        ]);

        Stock::create([
            'product_id' => $this->product->id,
            'outlet_id' => $this->outlet->id,
            'quantity' => 10,
            'last_mutation_at' => now(),
        ]);

        StockMutation::create([
            'product_id' => $this->product->id,
            'outlet_id' => $this->outlet->id,
            'mutation_type' => 'adjustment',
            'quantity' => 10,
            'unit_cost' => 10000,
            'total_cost' => 100000,
            'stock_before' => 0,
            'stock_after' => 10,
            'reference_type' => 'stock_opname',
            'reference_id' => null,
            'mutation_date' => '2026-03-10',
            'notes' => 'Saldo awal',
            'created_by' => $this->user->id,
        ]);
    }

    public function test_backdated_stock_mutation_recalculates_future_running_balance(): void
    {
        $this->stockService->addPurchaseStock(
            productId: $this->product->id,
            outletId: $this->outlet->id,
            quantity: 5,
            purchaseId: 101,
            userId: $this->user->id,
            unitPrice: 10000,
            mutationDate: '2026-03-12'
        );

        $this->stockService->reduceSaleStock(
            productId: $this->product->id,
            outletId: $this->outlet->id,
            quantity: 3,
            saleId: 201,
            userId: $this->user->id,
            notes: 'Jual 14 Maret',
            mutationDate: '2026-03-14'
        );

        $this->stockService->addPurchaseStock(
            productId: $this->product->id,
            outletId: $this->outlet->id,
            quantity: 2,
            purchaseId: 102,
            userId: $this->user->id,
            unitPrice: 10000,
            mutationDate: '2026-03-13'
        );

        $rows = StockMutation::query()
            ->where('product_id', $this->product->id)
            ->where('outlet_id', $this->outlet->id)
            ->orderBy('mutation_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $this->assertCount(4, $rows);

        $this->assertSame(0.0, (float) $rows[0]->stock_before);
        $this->assertSame(10.0, (float) $rows[0]->stock_after);

        $this->assertSame(10.0, (float) $rows[1]->stock_before);
        $this->assertSame(15.0, (float) $rows[1]->stock_after);

        $this->assertSame(15.0, (float) $rows[2]->stock_before);
        $this->assertSame(17.0, (float) $rows[2]->stock_after);

        $this->assertSame(17.0, (float) $rows[3]->stock_before);
        $this->assertSame(14.0, (float) $rows[3]->stock_after);

        $stock = Stock::query()
            ->where('product_id', $this->product->id)
            ->where('outlet_id', $this->outlet->id)
            ->firstOrFail();

        $this->assertSame(14.0, (float) $stock->quantity);
    }
}
