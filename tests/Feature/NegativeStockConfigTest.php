<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\ProductCategory;
use App\Models\Product;
use App\Models\Outlet;
use App\Models\Stock;
use App\Services\SaleService;

class NegativeStockConfigTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_allows_negative_stock_when_configured()
    {
        // Temporarily set config to allow negative stock
        config(['app.allow_negative_stock' => true]);

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

        $product = Product::create([
            'sku' => 'RM-LOW-STOCK', 
            'name' => 'Produk Stok Rendah', 
            'category_id' => $category->id,
            'unit' => 'pcs', 
            'purchase_price' => 10000, 
            'selling_price' => 15000,
            'product_type' => 'raw_material', 
            'min_stock' => 0, 
            'is_active' => true,
        ]);

        // Only 2 items in stock, but trying to sell 5
        Stock::create(['product_id' => $product->id, 'outlet_id' => $outlet->id, 'quantity' => 2, 'last_mutation_at' => now()]);

        $service = app(SaleService::class);
        
        // Should NOT throw exception because negative stock is allowed
        $sale = $service->createSale([
            'outlet_id' => $outlet->id,
            'cash_session_id' => $cashSession->id,
            'user_id' => $user->id,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 5, // More than available
                    'unit_price' => 15000,
                    'discount_amount' => 0,
                ],
            ],
            'payment_method_id' => $paymentMethod->id,
            'payment_amount' => 75000,
        ]);

        // Check that stock went negative
        $finalStock = Stock::where('product_id', $product->id)->where('outlet_id', $outlet->id)->first();
        $this->assertEquals(-3, (float)$finalStock->quantity, 'Stock should be allowed to go negative');
        
        $this->assertNotNull($sale, 'Sale should complete successfully even with insufficient stock');
    }

    /** @test */
    public function it_prevents_negative_stock_when_disabled()
    {
        // Ensure config prevents negative stock (default)
        config(['app.allow_negative_stock' => false]);

        $user = \App\Models\User::create(['name' => 'Tester', 'email' => 'tester2@example.com', 'password' => bcrypt('secret')]);
        $category = ProductCategory::create(['code' => 'RAW', 'name' => 'Raw Materials']);
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

        $product = Product::create([
            'sku' => 'RM-INSUFFICIENT', 
            'name' => 'Produk Stok Kurang', 
            'category_id' => $category->id,
            'unit' => 'pcs', 
            'purchase_price' => 8000, 
            'selling_price' => 12000,
            'product_type' => 'raw_material', 
            'min_stock' => 0, 
            'is_active' => true,
        ]);

        // Only 1 item in stock, trying to sell 3
        Stock::create(['product_id' => $product->id, 'outlet_id' => $outlet->id, 'quantity' => 1, 'last_mutation_at' => now()]);

        $service = app(SaleService::class);
        
        // Should throw exception because negative stock is NOT allowed
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('tidak mencukupi');
        
        $service->createSale([
            'outlet_id' => $outlet->id,
            'cash_session_id' => $cashSession->id,
            'user_id' => $user->id,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 3, // More than available
                    'unit_price' => 12000,
                    'discount_amount' => 0,
                ],
            ],
            'payment_method_id' => $paymentMethod->id,
            'payment_amount' => 36000,
        ]);
    }
}