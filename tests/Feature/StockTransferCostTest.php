<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use App\Models\ProductCategory;
use App\Models\Product;
use App\Models\ProductCost;
use App\Models\CashSession;
use App\Models\Outlet;
use App\Models\Stock;
use App\Models\StockMutation;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use App\Services\CostService;
use App\Services\StockTransferService;

class StockTransferCostTest extends TestCase
{
    use RefreshDatabase;

    protected CostService $costService;
    protected StockTransferService $transferService;
    protected Outlet $outletA;
    protected Outlet $outletB;
    protected ProductCategory $category;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->costService = app(CostService::class);
        $this->transferService = app(StockTransferService::class);
        $this->user = User::create(['name' => 'Tester', 'email' => 'tester@test.com', 'password' => bcrypt('secret')]);
        $this->category = ProductCategory::create(['code' => 'RAW', 'name' => 'Raw Materials']);
        $this->outletA = Outlet::create(['name' => 'Outlet A', 'code' => 'OA']);
        $this->outletB = Outlet::create(['name' => 'Outlet B', 'code' => 'OB']);
        $this->actingAs($this->user);
    }

    /** @test */
    public function transfer_out_records_sender_avg_cost()
    {
        $product = $this->createProduct('Gula', 15000);

        // Setup: avg cost outlet A = 16000, stok = 20
        ProductCost::create(['product_id' => $product->id, 'outlet_id' => $this->outletA->id, 'avg_cost' => 16000, 'last_calculated_at' => now()]);
        Stock::create(['product_id' => $product->id, 'outlet_id' => $this->outletA->id, 'quantity' => 20, 'last_mutation_at' => now()]);

        // Buat dan kirim transfer
        $transfer = $this->transferService->createTransfer([
            'from_outlet_id' => $this->outletA->id,
            'to_outlet_id' => $this->outletB->id,
            'transfer_date' => now()->toDateString(),
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5],
            ],
        ]);

        $this->transferService->sendTransfer($transfer->fresh());

        // Cek mutasi transfer_out punya cost
        $mutation = StockMutation::where('reference_type', 'stock_transfer')
            ->where('reference_id', $transfer->id)
            ->where('mutation_type', 'transfer_out')
            ->first();

        $this->assertNotNull($mutation);
        $this->assertEquals(16000, (float) $mutation->unit_cost, 'transfer_out should record sender avg cost');
        $this->assertEquals(80000, (float) $mutation->total_cost, '16000 × 5 = 80000');
    }

    /** @test */
    public function transfer_in_updates_receiver_avg_cost()
    {
        $product = $this->createProduct('Gula', 15000);

        // Setup outlet A: avg cost 16000, stok 20
        ProductCost::create(['product_id' => $product->id, 'outlet_id' => $this->outletA->id, 'avg_cost' => 16000, 'last_calculated_at' => now()]);
        Stock::create(['product_id' => $product->id, 'outlet_id' => $this->outletA->id, 'quantity' => 20, 'last_mutation_at' => now()]);

        // Setup outlet B: avg cost 14000, stok 10
        ProductCost::create(['product_id' => $product->id, 'outlet_id' => $this->outletB->id, 'avg_cost' => 14000, 'last_calculated_at' => now()]);
        Stock::create(['product_id' => $product->id, 'outlet_id' => $this->outletB->id, 'quantity' => 10, 'last_mutation_at' => now()]);

        // Buat, kirim, dan terima transfer
        $transfer = $this->transferService->createTransfer([
            'from_outlet_id' => $this->outletA->id,
            'to_outlet_id' => $this->outletB->id,
            'transfer_date' => now()->toDateString(),
            'items' => [
                ['product_id' => $product->id, 'quantity' => 10],
            ],
        ]);

        $sent = $this->transferService->sendTransfer($transfer->fresh());

        // Receive transfer (terima semua)
        $items = $sent->items->mapWithKeys(fn($i) => [$i->id => $i->quantity])->all();
        $this->transferService->receiveTransfer($sent->fresh(), $items);

        // Cek mutasi transfer_in punya cost dari outlet A (16000)
        $inMutation = StockMutation::where('reference_type', 'stock_transfer')
            ->where('reference_id', $transfer->id)
            ->where('mutation_type', 'transfer_in')
            ->first();

        $this->assertEquals(16000, (float) $inMutation->unit_cost, 'transfer_in should carry sender cost');

        // Cek avg cost outlet B: ((10 × 14000) + (10 × 16000)) / 20 = 15000
        $avgCostB = $this->costService->getAvgCost($product->id, $this->outletB->id);
        $this->assertEquals(15000, $avgCostB, 'Receiver avg cost should be weighted average');
    }

    /** @test */
    public function cancel_transit_transfer_records_cost_on_reversal()
    {
        $product = $this->createProduct('Gula', 15000);

        // Setup outlet A: avg cost 16000, stok 20
        ProductCost::create(['product_id' => $product->id, 'outlet_id' => $this->outletA->id, 'avg_cost' => 16000, 'last_calculated_at' => now()]);
        Stock::create(['product_id' => $product->id, 'outlet_id' => $this->outletA->id, 'quantity' => 20, 'last_mutation_at' => now()]);

        // Buat dan kirim transfer
        $transfer = $this->transferService->createTransfer([
            'from_outlet_id' => $this->outletA->id,
            'to_outlet_id' => $this->outletB->id,
            'transfer_date' => now()->toDateString(),
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5],
            ],
        ]);

        $sent = $this->transferService->sendTransfer($transfer->fresh());

        // Cancel in-transit transfer
        $this->transferService->cancelTransfer($sent->fresh(), 'Salah kirim');

        // Cek mutasi reversal punya cost
        $adjustMutation = StockMutation::where('reference_type', 'stock_transfer')
            ->where('reference_id', $transfer->id)
            ->where('mutation_type', 'adjustment')
            ->first();

        $this->assertNotNull($adjustMutation);
        $this->assertEquals(16000, (float) $adjustMutation->unit_cost, 'Cancel should record original cost');
        $this->assertEquals(80000, (float) $adjustMutation->total_cost, '16000 × 5 = 80000');

        // Stok harus kembali ke 20
        $stock = Stock::where('product_id', $product->id)->where('outlet_id', $this->outletA->id)->first();
        $this->assertEquals(20, (float) $stock->quantity, 'Stock should be restored');
    }

    /** @test */
    public function backdated_transfer_recalculates_sender_running_balance()
    {
        $product = $this->createProduct('Kecap', 12000);

        ProductCost::create([
            'product_id' => $product->id,
            'outlet_id' => $this->outletA->id,
            'avg_cost' => 12000,
            'last_calculated_at' => now(),
        ]);

        Stock::create([
            'product_id' => $product->id,
            'outlet_id' => $this->outletA->id,
            'quantity' => 20,
            'last_mutation_at' => now(),
        ]);

        StockMutation::create([
            'product_id' => $product->id,
            'outlet_id' => $this->outletA->id,
            'mutation_type' => 'adjustment',
            'quantity' => 20,
            'unit_cost' => 12000,
            'total_cost' => 240000,
            'stock_before' => 0,
            'stock_after' => 20,
            'reference_type' => 'stock_opname',
            'reference_id' => null,
            'mutation_date' => '2026-03-10',
            'notes' => 'Saldo awal',
            'created_by' => $this->user->id,
        ]);

        $laterTransfer = $this->transferService->createTransfer([
            'from_outlet_id' => $this->outletA->id,
            'to_outlet_id' => $this->outletB->id,
            'transfer_date' => '2026-03-15',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5],
            ],
        ]);
        $this->transferService->sendTransfer($laterTransfer->fresh());

        $backdatedTransfer = $this->transferService->createTransfer([
            'from_outlet_id' => $this->outletA->id,
            'to_outlet_id' => $this->outletB->id,
            'transfer_date' => '2026-03-14',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ]);
        $this->transferService->sendTransfer($backdatedTransfer->fresh());

        $rows = StockMutation::query()
            ->where('product_id', $product->id)
            ->where('outlet_id', $this->outletA->id)
            ->orderBy('mutation_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $this->assertCount(3, $rows);
        $this->assertEquals(20.0, (float) $rows[1]->stock_before);
        $this->assertEquals(18.0, (float) $rows[1]->stock_after);
        $this->assertEquals(18.0, (float) $rows[2]->stock_before);
        $this->assertEquals(13.0, (float) $rows[2]->stock_after);

        $stock = Stock::query()
            ->where('product_id', $product->id)
            ->where('outlet_id', $this->outletA->id)
            ->firstOrFail();

        $this->assertEquals(13.0, (float) $stock->quantity);
    }

    /** @test */
    public function correcting_received_transfer_date_updates_header_and_only_sender_transfer_out_mutation()
    {
        Carbon::setTestNow('2026-04-14 15:02:00');

        $product = $this->createProduct('Tepung', 12000);

        ProductCost::create([
            'product_id' => $product->id,
            'outlet_id' => $this->outletA->id,
            'avg_cost' => 12000,
            'last_calculated_at' => now(),
        ]);

        Stock::create([
            'product_id' => $product->id,
            'outlet_id' => $this->outletA->id,
            'quantity' => 10,
            'last_mutation_at' => now(),
        ]);

        $transfer = $this->transferService->createTransfer([
            'from_outlet_id' => $this->outletA->id,
            'to_outlet_id' => $this->outletB->id,
            'transfer_date' => '2026-04-14',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3],
            ],
        ]);

        $sent = $this->transferService->sendTransfer($transfer->fresh());
        $items = $sent->fresh()->items->mapWithKeys(fn ($item) => [$item->id => $item->quantity])->all();
        $this->transferService->receiveTransfer($sent->fresh(), $items);

        $this->transferService->correctTransferDate($transfer->fresh(), '2026-04-04');

        $updatedTransfer = $transfer->fresh();
        $transferOut = StockMutation::query()
            ->where('reference_type', 'stock_transfer')
            ->where('reference_id', $transfer->id)
            ->where('mutation_type', 'transfer_out')
            ->firstOrFail();
        $transferIn = StockMutation::query()
            ->where('reference_type', 'stock_transfer')
            ->where('reference_id', $transfer->id)
            ->where('mutation_type', 'transfer_in')
            ->firstOrFail();

        $this->assertSame('2026-04-04', $updatedTransfer->transfer_date->toDateString());
        $this->assertSame('2026-04-04', $transferOut->mutation_date->toDateString());
        $this->assertSame('2026-04-14', $transferIn->mutation_date->toDateString());

        Carbon::setTestNow();
    }

    /** @test */
    public function correcting_transfer_date_recalculates_sender_running_balance_when_date_moves_earlier()
    {
        $product = $this->createProduct('Sirup', 10000);

        ProductCost::create([
            'product_id' => $product->id,
            'outlet_id' => $this->outletA->id,
            'avg_cost' => 10000,
            'last_calculated_at' => now(),
        ]);

        Stock::create([
            'product_id' => $product->id,
            'outlet_id' => $this->outletA->id,
            'quantity' => 20,
            'last_mutation_at' => now(),
        ]);

        StockMutation::create([
            'product_id' => $product->id,
            'outlet_id' => $this->outletA->id,
            'mutation_type' => 'adjustment',
            'quantity' => 20,
            'unit_cost' => 10000,
            'total_cost' => 200000,
            'stock_before' => 0,
            'stock_after' => 20,
            'reference_type' => 'stock_opname',
            'reference_id' => null,
            'mutation_date' => '2026-03-10',
            'notes' => 'Saldo awal',
            'created_by' => $this->user->id,
        ]);

        $transfer = $this->transferService->createTransfer([
            'from_outlet_id' => $this->outletA->id,
            'to_outlet_id' => $this->outletB->id,
            'transfer_date' => '2026-03-14',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5],
            ],
        ]);
        $this->transferService->sendTransfer($transfer->fresh());

        app(\App\Services\StockService::class)->addPurchaseStock(
            productId: $product->id,
            outletId: $this->outletA->id,
            quantity: 2,
            purchaseId: 501,
            userId: $this->user->id,
            unitPrice: 10000,
            mutationDate: '2026-03-13'
        );

        $this->transferService->correctTransferDate($transfer->fresh(), '2026-03-12');

        $rows = StockMutation::query()
            ->where('product_id', $product->id)
            ->where('outlet_id', $this->outletA->id)
            ->orderBy('mutation_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $this->assertCount(3, $rows);
        $this->assertSame('2026-03-12', $rows[1]->mutation_date->toDateString());
        $this->assertSame(20.0, (float) $rows[1]->stock_before);
        $this->assertSame(15.0, (float) $rows[1]->stock_after);
        $this->assertSame(15.0, (float) $rows[2]->stock_before);
        $this->assertSame(17.0, (float) $rows[2]->stock_after);

        $stock = Stock::query()
            ->where('product_id', $product->id)
            ->where('outlet_id', $this->outletA->id)
            ->firstOrFail();

        $this->assertSame(17.0, (float) $stock->quantity);
    }

    /** @test */
    public function correcting_transfer_date_is_blocked_when_completed_sales_already_exist_after_affected_date()
    {
        $product = $this->createProduct('Minyak', 10000);

        ProductCost::create([
            'product_id' => $product->id,
            'outlet_id' => $this->outletA->id,
            'avg_cost' => 10000,
            'last_calculated_at' => now(),
        ]);

        Stock::create([
            'product_id' => $product->id,
            'outlet_id' => $this->outletA->id,
            'quantity' => 10,
            'last_mutation_at' => now(),
        ]);

        $transfer = $this->transferService->createTransfer([
            'from_outlet_id' => $this->outletA->id,
            'to_outlet_id' => $this->outletB->id,
            'transfer_date' => '2026-04-14',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ]);
        $this->transferService->sendTransfer($transfer->fresh());

        $cashSession = CashSession::create([
            'session_number' => 'CS-TEST-001',
            'outlet_id' => $this->outletA->id,
            'user_id' => $this->user->id,
            'opening_balance' => 0,
            'expected_balance' => 0,
            'difference' => 0,
            'total_sales' => 0,
            'total_cash' => 0,
            'total_non_cash' => 0,
            'opened_at' => now(),
            'status' => 'open',
        ]);

        $sale = \App\Models\Sale::create([
            'invoice_number' => 'INV-001260414-0001',
            'outlet_id' => $this->outletA->id,
            'cash_session_id' => $cashSession->id,
            'user_id' => $this->user->id,
            'sale_date' => '2026-04-15',
            'sales_type' => 'regular',
            'subtotal' => 10000,
            'discount_type' => 'none',
            'discount_value' => 0,
            'discount_amount' => 0,
            'service_charge_amount' => 0,
            'rounding_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 10000,
            'status' => 'completed',
        ]);

        StockMutation::create([
            'product_id' => $product->id,
            'outlet_id' => $this->outletA->id,
            'mutation_type' => 'out',
            'quantity' => -1,
            'unit_cost' => 10000,
            'total_cost' => 10000,
            'stock_before' => 8,
            'stock_after' => 7,
            'reference_type' => 'sale',
            'reference_id' => $sale->id,
            'mutation_date' => '2026-04-15',
            'notes' => 'Penjualan: Minyak',
            'created_by' => $this->user->id,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('sudah ada penjualan');

        $this->transferService->correctTransferDate($transfer->fresh(), '2026-04-04');
    }

    private function createProduct(string $name, float $purchasePrice): Product
    {
        return Product::create([
            'sku' => 'RM-' . strtoupper(str_replace(' ', '', $name)),
            'name' => $name,
            'category_id' => $this->category->id,
            'unit' => 'kg',
            'purchase_price' => $purchasePrice,
            'selling_price' => $purchasePrice * 1.5,
            'product_type' => 'raw_material',
            'min_stock' => 0,
            'is_active' => true,
        ]);
    }
}
