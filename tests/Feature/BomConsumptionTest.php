<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\ProductCategory;
use App\Models\Product;
use App\Models\Outlet;
use App\Models\Stock;
use App\Models\BomHeader;
use App\Models\BomDetail;
use App\Services\SaleService;
use App\Models\SaleItem;
use App\Models\StockMutation;

class BomConsumptionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_consumes_raw_materials_instead_of_finished_good_stock_when_bom_active()
    {
        // User, Category & Outlet & Cash Session
        $user = \App\Models\User::create(['name' => 'Tester', 'email' => 'tester@example.com', 'password' => bcrypt('secret')]);
        $category = ProductCategory::create(['code' => 'FOOD', 'name' => 'Food']);
        $outlet = Outlet::create(['name' => 'Outlet 1', 'code' => 'OUT1']);
        $cashSession = \App\Models\CashSession::create([
            'session_number' => 'CS-TEST-1',
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
        $paymentMethod = \App\Models\PaymentMethod::create(['code' => 'CASH', 'name' => 'Cash']);

        // Raw materials
        $rice = Product::create([
            'sku' => 'RM-RICE', 'name' => 'Beras', 'category_id' => $category->id,
            'unit' => 'kg', 'purchase_price' => 10000, 'selling_price' => 0,
            'product_type' => 'raw_material', 'min_stock' => 0, 'is_active' => true,
        ]);
        $egg = Product::create([
            'sku' => 'RM-EGG', 'name' => 'Telur', 'category_id' => $category->id,
            'unit' => 'pcs', 'purchase_price' => 2000, 'selling_price' => 0,
            'product_type' => 'raw_material', 'min_stock' => 0, 'is_active' => true,
        ]);

        // Finished good
        $friedRice = Product::create([
            'sku' => 'FG-FRIEDRICE', 'name' => 'Nasi Goreng', 'category_id' => $category->id,
            'unit' => 'plate', 'purchase_price' => 0, 'selling_price' => 25000,
            'product_type' => 'finished_good', 'min_stock' => 0, 'is_active' => true,
        ]);

        // Stocks
        Stock::create(['product_id' => $rice->id, 'outlet_id' => $outlet->id, 'quantity' => 10, 'last_mutation_at' => now()]);
        Stock::create(['product_id' => $egg->id, 'outlet_id' => $outlet->id, 'quantity' => 50, 'last_mutation_at' => now()]);
        Stock::create(['product_id' => $friedRice->id, 'outlet_id' => $outlet->id, 'quantity' => 5, 'last_mutation_at' => now()]);

        // BOM (1 plate uses 0.2 kg rice & 1 egg)
        $bom = BomHeader::create(['product_id' => $friedRice->id, 'name' => 'Resep Nasi Goreng', 'is_active' => true]);
        BomDetail::create(['bom_id' => $bom->id, 'component_product_id' => $rice->id, 'quantity' => 0.2]);
        BomDetail::create(['bom_id' => $bom->id, 'component_product_id' => $egg->id, 'quantity' => 1]);

        // Perform sale of 3 plates
        $service = app(SaleService::class);
        $sale = $service->createSale([
            'outlet_id' => $outlet->id,
            'cash_session_id' => $cashSession->id,
            'user_id' => $user->id,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'items' => [
                [
                    'product_id' => $friedRice->id,
                    'quantity' => 3,
                    'unit_price' => 25000,
                    'discount_amount' => 0,
                ],
            ],
            'payment_method_id' => 1,
            'payment_amount' => 75000,
        ]);

        // Assert finished good stock unchanged
        $friedRiceStock = Stock::where('product_id', $friedRice->id)->where('outlet_id', $outlet->id)->first();
        $this->assertEquals(5, $friedRiceStock->quantity, 'Finished good stock should not decrease');

        // Assert raw material stocks decreased
        $riceStock = Stock::where('product_id', $rice->id)->where('outlet_id', $outlet->id)->first();
        $eggStock = Stock::where('product_id', $egg->id)->where('outlet_id', $outlet->id)->first();
        $this->assertEquals(10 - (0.2 * 3), (float)$riceStock->quantity, 'Rice stock should decrease by BOM qty * sale qty');
        $this->assertEquals(50 - (1 * 3), (float)$eggStock->quantity, 'Egg stock should decrease by BOM qty * sale qty');

        // Assert stock mutations created for components
        $componentMutationCount = StockMutation::where('reference_type', 'sale')->where('reference_id', $sale->id)->count();
        $this->assertEquals(2, $componentMutationCount, 'Should create mutations for each raw material component');
    }

    /** @test */
    public function it_rejects_sale_when_component_stock_insufficient()
    {
        $user = \App\Models\User::create(['name' => 'Tester', 'email' => 'tester2@example.com', 'password' => bcrypt('secret')]);
        $category = ProductCategory::create(['code' => 'FOOD', 'name' => 'Food']);
        $outlet = Outlet::create(['name' => 'Outlet 1', 'code' => 'OUT1']);
        $cashSession = \App\Models\CashSession::create([
            'session_number' => 'CS-TEST-2',
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
        $paymentMethod = \App\Models\PaymentMethod::create(['code' => 'CASH', 'name' => 'Cash']);

        $salt = Product::create([
            'sku' => 'RM-SALT', 'name' => 'Garam', 'category_id' => $category->id,
            'unit' => 'gram', 'purchase_price' => 50, 'selling_price' => 0,
            'product_type' => 'raw_material', 'min_stock' => 0, 'is_active' => true,
        ]);

        $soup = Product::create([
            'sku' => 'FG-SOUP', 'name' => 'Sup', 'category_id' => $category->id,
            'unit' => 'bowl', 'purchase_price' => 0, 'selling_price' => 20000,
            'product_type' => 'finished_good', 'min_stock' => 0, 'is_active' => true,
        ]);

        Stock::create(['product_id' => $salt->id, 'outlet_id' => $outlet->id, 'quantity' => 0.5, 'last_mutation_at' => now()]); // only 0.5 gram
        Stock::create(['product_id' => $soup->id, 'outlet_id' => $outlet->id, 'quantity' => 10, 'last_mutation_at' => now()]);

        $bom = BomHeader::create(['product_id' => $soup->id, 'name' => 'Resep Sup', 'is_active' => true]);
        BomDetail::create(['bom_id' => $bom->id, 'component_product_id' => $salt->id, 'quantity' => 1]); // needs 1 gram per bowl

        $service = app(SaleService::class);

        $this->expectException(\Exception::class);
        $service->createSale([
            'outlet_id' => $outlet->id,
            'cash_session_id' => $cashSession->id,
            'user_id' => $user->id,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'items' => [
                [
                    'product_id' => $soup->id,
                    'quantity' => 1,
                    'unit_price' => 20000,
                    'discount_amount' => 0,
                ],
            ],
            'payment_method_id' => 1,
            'payment_amount' => 20000,
        ]);
    }
}
