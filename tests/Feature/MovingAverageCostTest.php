<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\ProductCategory;
use App\Models\Product;
use App\Models\ProductCost;
use App\Models\Outlet;
use App\Models\Stock;
use App\Models\StockMutation;
use App\Models\BomHeader;
use App\Models\BomDetail;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\SaleItem;
use App\Models\User;
use App\Services\CostService;
use App\Services\PurchaseService;
use App\Services\SaleService;
use App\Services\StockService;

class MovingAverageCostTest extends TestCase
{
    use RefreshDatabase;

    protected CostService $costService;
    protected Outlet $outlet;
    protected ProductCategory $category;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->costService = app(CostService::class);
        $this->user = User::create(['name' => 'Tester', 'email' => 'tester@test.com', 'password' => bcrypt('secret')]);
        $this->category = ProductCategory::create(['code' => 'RAW', 'name' => 'Raw Materials']);
        $this->outlet = Outlet::create(['name' => 'Outlet 1', 'code' => 'OUT1']);
    }

    /** @test */
    public function avg_cost_equals_purchase_price_on_first_receive()
    {
        $product = $this->createProduct('Gula', 15000);

        // Simulasi receive purchase
        $this->costService->updateAvgCostOnReceive(
            productId: $product->id,
            outletId: $this->outlet->id,
            receivedQty: 10,
            unitPrice: 15000,
        );

        // Buat stok dulu untuk getAvgCost
        Stock::create(['product_id' => $product->id, 'outlet_id' => $this->outlet->id, 'quantity' => 10, 'last_mutation_at' => now()]);

        $avgCost = $this->costService->getAvgCost($product->id, $this->outlet->id);
        $this->assertEquals(15000, $avgCost, 'First receive: avg cost should equal purchase price');
    }

    /** @test */
    public function avg_cost_calculates_weighted_average_on_second_receive()
    {
        $product = $this->createProduct('Gula', 15000);

        // Receive pertama: 10 kg @ Rp 15.000
        Stock::create(['product_id' => $product->id, 'outlet_id' => $this->outlet->id, 'quantity' => 10, 'last_mutation_at' => now()]);
        $this->costService->updateAvgCostOnReceive(
            productId: $product->id,
            outletId: $this->outlet->id,
            receivedQty: 10,
            unitPrice: 15000,
        );

        // Receive kedua: 10 kg @ Rp 18.000
        $stock = Stock::where('product_id', $product->id)->where('outlet_id', $this->outlet->id)->first();
        $stock->update(['quantity' => 20]); // Simulasi stok bertambah
        $this->costService->updateAvgCostOnReceive(
            productId: $product->id,
            outletId: $this->outlet->id,
            receivedQty: 10,
            unitPrice: 18000,
        );

        // Expected: ((10 × 15000) + (10 × 18000)) / 20 = 330000 / 20 = 16500
        $avgCost = $this->costService->getAvgCost($product->id, $this->outlet->id);
        $this->assertEquals(16500, $avgCost, 'Weighted average: (10*15000 + 10*18000) / 20 = 16500');
    }

    /** @test */
    public function cogs_uses_avg_cost_for_raw_material()
    {
        $product = $this->createProduct('Gula', 15000);

        // Setup avg cost di product_costs
        ProductCost::create([
            'product_id' => $product->id,
            'outlet_id' => $this->outlet->id,
            'avg_cost' => 16500,
            'last_calculated_at' => now(),
        ]);

        $cogs = $this->costService->calculateItemCogs($product, 5, $this->outlet->id);

        // COGS = avg_cost × quantity = 16500 × 5 = 82500
        $this->assertEquals(82500, $cogs, 'COGS should use avg_cost from product_costs, not purchase_price');
    }

    /** @test */
    public function cogs_uses_component_avg_costs_for_bom()
    {
        $rice = $this->createProduct('Beras', 12000);
        $egg = $this->createProduct('Telur', 2500);

        $friedRice = Product::create([
            'sku' => 'FG-FRIEDRICE',
            'name' => 'Nasi Goreng',
            'category_id' => $this->category->id,
            'unit' => 'plate',
            'purchase_price' => 0,
            'selling_price' => 25000,
            'product_type' => 'finished_good',
            'min_stock' => 0,
            'is_active' => true,
        ]);

        // BOM: 1 plate = 0.15 kg beras + 2 telur
        $bom = BomHeader::create(['product_id' => $friedRice->id, 'name' => 'Resep Nasi Goreng', 'is_active' => true]);
        BomDetail::create(['bom_id' => $bom->id, 'component_product_id' => $rice->id, 'quantity' => 0.15]);
        BomDetail::create(['bom_id' => $bom->id, 'component_product_id' => $egg->id, 'quantity' => 2]);

        // Setup avg cost komponen (berbeda dari purchase_price)
        ProductCost::create(['product_id' => $rice->id, 'outlet_id' => $this->outlet->id, 'avg_cost' => 13000, 'last_calculated_at' => now()]);
        ProductCost::create(['product_id' => $egg->id, 'outlet_id' => $this->outlet->id, 'avg_cost' => 2800, 'last_calculated_at' => now()]);

        // Refresh model agar EagerLoad BOM
        $friedRice = Product::with(['bomHeader' => fn($q) => $q->where('is_active', true)->with('details.component')])->find($friedRice->id);

        $cogs = $this->costService->calculateItemCogs($friedRice, 4, $this->outlet->id);

        // Beras: 0.15 × 4 × 13000 = 7800
        // Telur: 2 × 4 × 2800 = 22400
        // Total = 30200
        $expected = (0.15 * 4 * 13000) + (2 * 4 * 2800);
        $this->assertEquals($expected, $cogs, 'BOM COGS should use component avg_costs from product_costs');
    }

    /** @test */
    public function cogs_falls_back_to_purchase_price_when_no_product_costs_record()
    {
        $product = $this->createProduct('Gula', 15000);

        // Tidak ada record di product_costs
        $cogs = $this->costService->calculateItemCogs($product, 5, $this->outlet->id);

        // Fallback: purchase_price × quantity = 15000 × 5 = 75000
        $this->assertEquals(75000, $cogs, 'Without product_costs record, should fallback to purchase_price');
    }

    /** @test */
    public function service_product_always_zero_cogs()
    {
        $service = Product::create([
            'sku' => 'SVC-01',
            'name' => 'Layanan Antar',
            'category_id' => $this->category->id,
            'unit' => 'trip',
            'purchase_price' => 5000,
            'selling_price' => 10000,
            'product_type' => 'service',
            'min_stock' => 0,
            'is_active' => true,
        ]);

        $cogs = $this->costService->calculateItemCogs($service, 3, $this->outlet->id);
        $this->assertEquals(0, $cogs, 'Service product COGS should always be 0');
    }

    /** @test */
    public function multi_outlet_avg_costs_are_independent()
    {
        $product = $this->createProduct('Gula', 15000);
        $outlet2 = Outlet::create(['name' => 'Outlet 2', 'code' => 'OUT2']);

        // Outlet 1: avg cost 16000
        ProductCost::create(['product_id' => $product->id, 'outlet_id' => $this->outlet->id, 'avg_cost' => 16000, 'last_calculated_at' => now()]);

        // Outlet 2: avg cost 17500
        ProductCost::create(['product_id' => $product->id, 'outlet_id' => $outlet2->id, 'avg_cost' => 17500, 'last_calculated_at' => now()]);

        $cost1 = $this->costService->getAvgCost($product->id, $this->outlet->id);
        $cost2 = $this->costService->getAvgCost($product->id, $outlet2->id);

        $this->assertEquals(16000, $cost1, 'Outlet 1 avg cost');
        $this->assertEquals(17500, $cost2, 'Outlet 2 avg cost');
        $this->assertNotEquals($cost1, $cost2, 'Multi-outlet avg costs should be independent');
    }

    /** @test */
    public function full_flow_receive_then_sale_uses_avg_cost()
    {
        $product = $this->createProduct('Gula', 15000);
        $cashSession = \App\Models\CashSession::create([
            'session_number' => 'CS-TEST-AVG',
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'opening_balance' => 0,
            'expected_balance' => 0,
            'total_sales' => 0,
            'total_cash' => 0,
            'total_non_cash' => 0,
            'opened_at' => now(),
            'status' => 'open',
        ]);
        $paymentMethod = \App\Models\PaymentMethod::create(['code' => 'CASH', 'name' => 'Cash']);

        // 1. Buat purchase
        $purchase = Purchase::create([
            'purchase_number' => 'PO-TEST-001',
            'outlet_id' => $this->outlet->id,
            'supplier_id' => \App\Models\Supplier::create(['name' => 'Supplier 1', 'code' => 'SUP1', 'contact_person' => 'SP'])->id,
            'purchase_date' => now()->toDateString(),
            'status' => 'draft',
            'subtotal' => 180000,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 180000,
            'payment_status' => 'pending',
            'created_by' => $this->user->id,
        ]);
        PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'unit_price' => 18000, // Beda dari purchase_price master (15000)
            'discount_amount' => 0,
            'subtotal' => 180000,
        ]);

        // 2. Receive purchase (ini akan trigger addPurchaseStock + updateAvgCostOnReceive)
        $this->actingAs($this->user);
        $purchaseService = app(PurchaseService::class);
        $purchaseService->receivePurchase($purchase->fresh());

        // 3. Verifikasi avg cost = 18000 (receive pertama, qty sebelumnya = 0)
        $avgCost = $this->costService->getAvgCost($product->id, $this->outlet->id);
        $this->assertEquals(18000, $avgCost, 'After first receive, avg cost should equal purchase unit price');

        // 4. Sale menggunakan avg cost
        $saleService = app(SaleService::class);
        $sale = $saleService->createSale([
            'outlet_id' => $this->outlet->id,
            'cash_session_id' => $cashSession->id,
            'user_id' => $this->user->id,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 3,
                    'unit_price' => 25000,
                    'discount_amount' => 0,
                ],
            ],
            'payment_method_id' => $paymentMethod->id,
            'payment_amount' => 75000,
        ]);

        $saleItem = $sale->items->first();
        // COGS = avg_cost × qty = 18000 × 3 = 54000 (bukan 15000 × 3 = 45000)
        $this->assertEquals(54000, (float) $saleItem->cogs, 'Sale COGS should use avg_cost (18000), not master purchase_price (15000)');
    }

    /** @test */
    public function stock_adjustment_records_cost_snapshot_for_nominal_reporting()
    {
        $product = $this->createProduct('Minyak', 10000);

        Stock::create([
            'product_id' => $product->id,
            'outlet_id' => $this->outlet->id,
            'quantity' => 10,
            'last_mutation_at' => now(),
        ]);

        ProductCost::create([
            'product_id' => $product->id,
            'outlet_id' => $this->outlet->id,
            'avg_cost' => 12500,
            'last_calculated_at' => now(),
        ]);

        $this->actingAs($this->user);
        app(StockService::class)->adjustStock(
            productId: $product->id,
            outletId: $this->outlet->id,
            newQuantity: 7,
            notes: 'Opname koreksi',
            userId: $this->user->id
        );

        $mutation = StockMutation::where('product_id', $product->id)
            ->where('outlet_id', $this->outlet->id)
            ->where('mutation_type', 'adjustment')
            ->latest('id')
            ->firstOrFail();

        $this->assertEquals(-3.0, (float) $mutation->quantity);
        $this->assertEquals(12500.0, (float) $mutation->unit_cost);
        $this->assertEquals(37500.0, (float) $mutation->total_cost);
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
