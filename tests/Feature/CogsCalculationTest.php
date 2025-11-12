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
use App\Models\SaleItem;
use App\Services\SaleService;

class CogsCalculationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_calculates_cogs_for_raw_material_product()
    {
        $user = \App\Models\User::create(['name' => 'Tester', 'email' => 'tester@example.com', 'password' => bcrypt('secret')]);
        $category = ProductCategory::create(['code' => 'RAW', 'name' => 'Raw Materials']);
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

        $rawMaterial = Product::create([
            'sku' => 'RM-SUGAR', 
            'name' => 'Gula', 
            'category_id' => $category->id,
            'unit' => 'kg', 
            'purchase_price' => 15000, // COGS base
            'selling_price' => 20000,
            'product_type' => 'raw_material', 
            'min_stock' => 0, 
            'is_active' => true,
        ]);

        Stock::create(['product_id' => $rawMaterial->id, 'outlet_id' => $outlet->id, 'quantity' => 100, 'last_mutation_at' => now()]);

        $service = app(SaleService::class);
        $sale = $service->createSale([
            'outlet_id' => $outlet->id,
            'cash_session_id' => $cashSession->id,
            'user_id' => $user->id,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'items' => [
                [
                    'product_id' => $rawMaterial->id,
                    'quantity' => 5, // 5 kg
                    'unit_price' => 20000,
                    'discount_amount' => 0,
                ],
            ],
            'payment_method_id' => $paymentMethod->id,
            'payment_amount' => 100000,
        ]);

        $saleItem = $sale->items->first();
        $expectedCogs = 15000 * 5; // purchase_price * quantity = 75000
        
        $this->assertEquals($expectedCogs, (float)$saleItem->cogs, 'COGS should equal purchase_price * quantity for raw materials');
    }

    /** @test */
    public function it_calculates_cogs_for_finished_good_with_bom()
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
        $paymentMethod = \App\Models\PaymentMethod::create(['code' => 'CASH2', 'name' => 'Cash']);

        // Raw materials
        $rice = Product::create([
            'sku' => 'RM-RICE', 
            'name' => 'Beras', 
            'category_id' => $category->id,
            'unit' => 'kg', 
            'purchase_price' => 12000, 
            'selling_price' => 0,
            'product_type' => 'raw_material', 
            'min_stock' => 0, 
            'is_active' => true,
        ]);
        
        $egg = Product::create([
            'sku' => 'RM-EGG', 
            'name' => 'Telur', 
            'category_id' => $category->id,
            'unit' => 'pcs', 
            'purchase_price' => 2500, 
            'selling_price' => 0,
            'product_type' => 'raw_material', 
            'min_stock' => 0, 
            'is_active' => true,
        ]);

        // Finished good
        $friedRice = Product::create([
            'sku' => 'FG-FRIEDRICE', 
            'name' => 'Nasi Goreng', 
            'category_id' => $category->id,
            'unit' => 'plate', 
            'purchase_price' => 0, 
            'selling_price' => 25000,
            'product_type' => 'finished_good', 
            'min_stock' => 0, 
            'is_active' => true,
        ]);

        Stock::create(['product_id' => $rice->id, 'outlet_id' => $outlet->id, 'quantity' => 50, 'last_mutation_at' => now()]);
        Stock::create(['product_id' => $egg->id, 'outlet_id' => $outlet->id, 'quantity' => 100, 'last_mutation_at' => now()]);

        // BOM: 1 plate = 0.15 kg rice + 2 eggs
        $bom = BomHeader::create(['product_id' => $friedRice->id, 'name' => 'Resep Nasi Goreng', 'is_active' => true]);
        BomDetail::create(['bom_id' => $bom->id, 'component_product_id' => $rice->id, 'quantity' => 0.15]);
        BomDetail::create(['bom_id' => $bom->id, 'component_product_id' => $egg->id, 'quantity' => 2]);

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
                    'quantity' => 4, // 4 plates
                    'unit_price' => 25000,
                    'discount_amount' => 0,
                ],
            ],
            'payment_method_id' => $paymentMethod->id,
            'payment_amount' => 100000,
        ]);

        $saleItem = $sale->items->first();
        
        // Expected COGS calculation:
        // Rice: 0.15 kg × 4 plates × 12000 = 7200
        // Egg: 2 pcs × 4 plates × 2500 = 20000
        // Total COGS = 27200
        $expectedCogs = (0.15 * 4 * 12000) + (2 * 4 * 2500);
        
        $this->assertEquals($expectedCogs, (float)$saleItem->cogs, 'COGS should be calculated from BOM component costs');
    }

    /** @test */
    public function it_calculates_zero_cogs_for_service_product()
    {
        $user = \App\Models\User::create(['name' => 'Tester', 'email' => 'tester3@example.com', 'password' => bcrypt('secret')]);
        $category = ProductCategory::create(['code' => 'SVC', 'name' => 'Services']);
        $outlet = Outlet::create(['name' => 'Outlet 1', 'code' => 'OUT1']);
        $cashSession = \App\Models\CashSession::create([
            'session_number' => 'CS-TEST-3',
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
        $paymentMethod = \App\Models\PaymentMethod::create(['code' => 'CASH3', 'name' => 'Cash']);

        $service = Product::create([
            'sku' => 'SVC-DELIVERY', 
            'name' => 'Layanan Antar', 
            'category_id' => $category->id,
            'unit' => 'trip', 
            'purchase_price' => 5000, // Doesn't matter for service
            'selling_price' => 10000,
            'product_type' => 'service', 
            'min_stock' => 0, 
            'is_active' => true,
        ]);

        $saleService = app(SaleService::class);
        $sale = $saleService->createSale([
            'outlet_id' => $outlet->id,
            'cash_session_id' => $cashSession->id,
            'user_id' => $user->id,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'items' => [
                [
                    'product_id' => $service->id,
                    'quantity' => 2,
                    'unit_price' => 10000,
                    'discount_amount' => 0,
                ],
            ],
            'payment_method_id' => $paymentMethod->id,
            'payment_amount' => 20000,
        ]);

        $saleItem = $sale->items->first();
        
        $this->assertEquals(0, (float)$saleItem->cogs, 'Service products should have zero COGS');
    }
}