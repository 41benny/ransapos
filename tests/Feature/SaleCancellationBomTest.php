<?php

namespace Tests\Feature;

use App\Models\BomDetail;
use App\Models\BomHeader;
use App\Models\CashSession;
use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleCancellationBomTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancel_sale_restores_bom_components_stock(): void
    {
        $user = User::factory()->create();
        $outlet = Outlet::factory()->create();
        $this->actingAs($user);

        $paymentMethod = PaymentMethod::create([
            'code' => 'CASH',
            'name' => 'Cash',
            'is_active' => true,
        ]);

        $cashSession = CashSession::create([
            'session_number' => 'CS-TEST-'.now()->format('Ymd').'-001',
            'outlet_id' => $outlet->id,
            'user_id' => $user->id,
            'opening_balance' => 100000,
            'expected_balance' => 100000,
            'actual_balance' => null,
            'difference' => 0,
            'total_sales' => 0,
            'total_cash' => 0,
            'total_non_cash' => 0,
            'opened_at' => now(),
            'status' => 'open',
        ]);

        // Komponen raw material
        $bean = Product::factory()->create([
            'product_type' => 'raw_material',
            'sku' => 'RAW-BEAN',
            'name' => 'Coffee Bean',
        ]);
        $milk = Product::factory()->create([
            'product_type' => 'raw_material',
            'sku' => 'RAW-MILK',
            'name' => 'Milk',
        ]);

        Stock::create([
            'product_id' => $bean->id,
            'outlet_id' => $outlet->id,
            'quantity' => 10,
            'last_mutation_at' => now(),
        ]);
        Stock::create([
            'product_id' => $milk->id,
            'outlet_id' => $outlet->id,
            'quantity' => 10,
            'last_mutation_at' => now(),
        ]);

        // Finished good + BOM
        $latte = Product::factory()->create([
            'product_type' => 'finished_good',
            'sku' => 'FG-LATTE',
            'name' => 'Latte',
        ]);

        $bom = BomHeader::create([
            'product_id' => $latte->id,
            'name' => 'Resep Latte',
            'is_active' => true,
        ]);

        BomDetail::create([
            'bom_id' => $bom->id,
            'component_product_id' => $bean->id,
            'quantity' => 2,
            'uom' => 'shot',
        ]);
        BomDetail::create([
            'bom_id' => $bom->id,
            'component_product_id' => $milk->id,
            'quantity' => 1,
            'uom' => 'cup',
        ]);

        $service = app(SaleService::class);

        $sale = $service->createSale([
            'outlet_id' => $outlet->id,
            'cash_session_id' => $cashSession->id,
            'customer_id' => null,
            'customer_name' => null,
            'notes' => null,
            'discount_type' => 'none',
            'discount_value' => 0,
            'items' => [
                [
                    'product_id' => $latte->id,
                    'quantity' => 2,
                    'unit_price' => 25000,
                    'discount_amount' => 0,
                ],
            ],
            'payment_method_id' => $paymentMethod->id,
            'payment_amount' => 50000,
            'payment_reference' => null,
            'payment_notes' => null,
        ]);

        // Setelah penjualan, stok komponen berkurang
        $this->assertEquals(6, (float) Stock::where('product_id', $bean->id)->where('outlet_id', $outlet->id)->value('quantity'));
        $this->assertEquals(8, (float) Stock::where('product_id', $milk->id)->where('outlet_id', $outlet->id)->value('quantity'));

        $service->cancelSale($sale->id, 'Customer refund');

        // Stok komponen kembali seperti semula (10)
        $this->assertEquals(10, (float) Stock::where('product_id', $bean->id)->where('outlet_id', $outlet->id)->value('quantity'));
        $this->assertEquals(10, (float) Stock::where('product_id', $milk->id)->where('outlet_id', $outlet->id)->value('quantity'));
    }
}
