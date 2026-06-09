<?php

namespace Tests\Feature;

use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Sale;
use App\Models\User;
use App\Services\BackdateSaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackdateSaleServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_backdate_sale_uses_selected_sale_date_and_correction_session(): void
    {
        $user = User::factory()->create();
        $outlet = Outlet::factory()->create(['code' => 'MBK2']);
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'product_type' => 'service',
            'is_sellable' => true,
            'selling_price' => 25000,
        ]);
        $paymentMethod = PaymentMethod::create(['code' => 'CASH', 'name' => 'Cash', 'is_active' => true]);
        $saleDate = today()->subDay()->toDateString();

        $sale = app(BackdateSaleService::class)->createBackdateSale([
            'manual_reference' => 'MBK2-' . str_replace('-', '', $saleDate) . '-001',
            'sale_date' => $saleDate,
            'outlet_id' => $outlet->id,
            'payment_method_id' => $paymentMethod->id,
            'backdate_reason' => 'Gangguan sistem outlet.',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 25000,
                    'discount_amount' => 0,
                ],
            ],
        ], $user);

        $sale->refresh();

        $this->assertTrue($sale->is_backdated);
        $this->assertEquals($saleDate, $sale->sale_date->toDateString());
        $this->assertEquals($user->id, $sale->backdated_by);
        $this->assertStringContainsString(today()->subDay()->format('ymd'), $sale->invoice_number);
        $this->assertEquals('backdate_correction', $sale->cashSession->session_type);
        $this->assertEquals($saleDate, $sale->cashSession->business_date->toDateString());
        $this->assertEquals(1, $sale->items()->count());
        $this->assertEquals(1, $sale->payments()->count());
    }

    public function test_admin_backdate_sale_allows_ten_days_back(): void
    {
        $this->assertEquals(
            today()->subDays(10)->toDateString(),
            app(BackdateSaleService::class)->validateSaleDate(today()->subDays(10)->toDateString())
        );
    }

    public function test_duplicate_manual_reference_is_rejected(): void
    {
        $user = User::factory()->create();
        $outlet = Outlet::factory()->create(['code' => 'MBK2']);
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'product_type' => 'service',
            'is_sellable' => true,
            'selling_price' => 10000,
        ]);
        $paymentMethod = PaymentMethod::create(['code' => 'CASH', 'name' => 'Cash', 'is_active' => true]);
        $saleDate = today()->subDay()->toDateString();

        Sale::create([
            'invoice_number' => 'INV-DUMMY',
            'outlet_id' => $outlet->id,
            'cash_session_id' => \App\Models\CashSession::create([
                'session_number' => 'CS-DUMMY',
                'outlet_id' => $outlet->id,
                'user_id' => $user->id,
                'opening_balance' => 0,
                'expected_balance' => 0,
                'opened_at' => now(),
                'status' => 'open',
            ])->id,
            'user_id' => $user->id,
            'sale_date' => $saleDate,
            'subtotal' => 10000,
            'total_amount' => 10000,
            'status' => 'completed',
            'is_backdated' => true,
            'manual_reference' => 'MBK2-DUPLICATE',
        ]);

        $this->expectExceptionMessage('MBK2-DUPLICATE sudah pernah dipakai');

        app(BackdateSaleService::class)->createBackdateSale([
            'manual_reference' => 'MBK2-DUPLICATE',
            'sale_date' => $saleDate,
            'outlet_id' => $outlet->id,
            'payment_method_id' => $paymentMethod->id,
            'backdate_reason' => 'Gangguan sistem outlet.',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 10000,
                    'discount_amount' => 0,
                ],
            ],
        ], $user);
    }

    public function test_admin_can_update_backdate_sale_and_move_correction_session(): void
    {
        $user = User::factory()->create();
        $outlet = Outlet::factory()->create(['code' => 'MBK2']);
        $category = ProductCategory::factory()->create();
        $productA = Product::factory()->create([
            'category_id' => $category->id,
            'product_type' => 'service',
            'is_sellable' => true,
            'selling_price' => 10000,
        ]);
        $productB = Product::factory()->create([
            'category_id' => $category->id,
            'product_type' => 'service',
            'is_sellable' => true,
            'selling_price' => 20000,
        ]);
        $paymentMethod = PaymentMethod::create(['code' => 'CASH', 'name' => 'Cash', 'is_active' => true]);
        $originalDate = today()->subDays(2)->toDateString();
        $newDate = today()->subDay()->toDateString();

        $service = app(BackdateSaleService::class);
        $sale = $service->createBackdateSale([
            'manual_reference' => 'MBK2-ORIGINAL',
            'sale_date' => $originalDate,
            'outlet_id' => $outlet->id,
            'payment_method_id' => $paymentMethod->id,
            'backdate_reason' => 'Gangguan sistem outlet.',
            'items' => [
                [
                    'product_id' => $productA->id,
                    'quantity' => 1,
                    'unit_price' => 10000,
                    'discount_amount' => 0,
                ],
            ],
        ], $user);
        $originalSessionId = $sale->cash_session_id;

        $updated = $service->updateBackdateSale($sale, [
            'manual_reference' => 'MBK2-UPDATED',
            'sale_date' => $newDate,
            'outlet_id' => $outlet->id,
            'payment_method_id' => $paymentMethod->id,
            'backdate_reason' => 'Tanggal catatan manual salah.',
            'items' => [
                [
                    'product_id' => $productB->id,
                    'quantity' => 2,
                    'unit_price' => 20000,
                    'discount_amount' => 0,
                ],
            ],
        ], $user);

        $updated->refresh();

        $this->assertEquals('MBK2-UPDATED', $updated->manual_reference);
        $this->assertEquals($newDate, $updated->sale_date->toDateString());
        $this->assertNotEquals($originalSessionId, $updated->cash_session_id);
        $this->assertEquals($newDate, $updated->cashSession->business_date->toDateString());
        $this->assertEquals(1, $updated->items()->count());
        $this->assertEquals($productB->id, $updated->items()->first()->product_id);
        $this->assertEquals(1, $updated->payments()->count());
        $this->assertEquals((float) $updated->total_amount, (float) $updated->payments()->first()->amount);
    }
}
