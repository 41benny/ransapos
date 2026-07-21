<?php

namespace Tests\Feature;

use App\Models\CashSession;
use App\Models\CashSessionPackagingClosing;
use App\Models\CashSessionPackagingOpening;
use App\Models\Outlet;
use App\Models\PackagingItem;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPackagingMapping;
use App\Models\Sale;
use App\Models\User;
use App\Services\BackdateSaleService;
use App\Services\PackagingService;
use App\Services\SaleService;
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

    public function test_backdate_sale_attaches_to_existing_shift_and_recomputes_packaging(): void
    {
        $user = User::factory()->create();
        $cashier = User::factory()->create();
        $outlet = Outlet::factory()->create(['code' => 'MBK2', 'tax_rate' => 0, 'service_charge_rate' => 0]);
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'product_type' => 'service',
            'is_sellable' => true,
            'selling_price' => 12000,
        ]);
        $paymentMethod = PaymentMethod::create(['code' => 'QRIS_BANK', 'name' => 'QRIS Bank', 'is_active' => true]);
        $saleDate = today()->subDay()->toDateString();

        $packagingItem = PackagingItem::create(['name' => 'Cup', 'unit' => 'pcs', 'is_active' => true, 'sort_order' => 1]);
        ProductPackagingMapping::create([
            'product_id' => $product->id,
            'packaging_item_id' => $packagingItem->id,
            'qty_per_product' => 1,
            'is_active' => true,
        ]);

        // Shift kasir berjalan: buka, jual 8 cup, hitung fisik sisa 52, lalu tutup dengan kas pas.
        $shift = CashSession::create([
            'session_number' => 'CS-MBK2-001',
            'session_type' => 'regular',
            'outlet_id' => $outlet->id,
            'user_id' => $cashier->id,
            'opening_balance' => 250000,
            'expected_balance' => 250000,
            'opened_at' => today()->subDay()->setTime(10, 20),
            'status' => 'open',
        ]);

        CashSessionPackagingOpening::create([
            'cash_session_id' => $shift->id,
            'packaging_item_id' => $packagingItem->id,
            'opening_qty' => 60,
            'source_last_closing_qty' => 60,
            'is_manual_corrected' => false,
            'created_by' => $cashier->id,
        ]);

        app(SaleService::class)->createSale([
            'outlet_id' => $outlet->id,
            'cash_session_id' => $shift->id,
            'user_id' => $cashier->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 8, 'unit_price' => 12000, 'discount_amount' => 0],
            ],
            'payment_method_id' => $paymentMethod->id,
            'payment_amount' => 96000,
            'sale_date' => $saleDate,
        ]);

        // Kasir menghitung fisik 52 saat closing — estimasi 8, jadi terlihat pas.
        app(PackagingService::class)->saveClosings($shift, [$packagingItem->id => 52]);

        $shift->refresh();
        $shift->update([
            'actual_balance' => $shift->expected_balance,
            'difference' => 0,
            'closed_at' => today()->subDay()->setTime(21, 32),
            'status' => 'closed',
        ]);

        $balanceBeforeBackdate = (float) $shift->expected_balance;
        $salesBeforeBackdate = (float) $shift->total_sales;
        $nonCashBeforeBackdate = (float) $shift->total_non_cash;

        $closingBefore = CashSessionPackagingClosing::where('cash_session_id', $shift->id)
            ->where('packaging_item_id', $packagingItem->id)
            ->first();
        $this->assertEquals(8, (float) $closingBefore->estimated_sales_used_qty);
        $this->assertEquals(0, (float) $closingBefore->difference_qty);

        $sale = app(BackdateSaleService::class)->createBackdateSale([
            'manual_reference' => 'MBK2-LUPA-001',
            'sale_date' => $saleDate,
            'outlet_id' => $outlet->id,
            'payment_method_id' => $paymentMethod->id,
            'backdate_reason' => 'Lupa input',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 12000, 'discount_amount' => 0],
            ],
        ], $user);

        // Menempel ke shift asli, bukan bikin sesi koreksi baru.
        $this->assertEquals($shift->id, $sale->cash_session_id);
        $this->assertSame(0, CashSession::where('session_type', 'backdate_correction')->count());

        $shift->refresh();

        // Rekap penjualan ikut naik...
        $this->assertEquals($salesBeforeBackdate + 12000, (float) $shift->total_sales);
        $this->assertEquals($nonCashBeforeBackdate + 12000, (float) $shift->total_non_cash);

        // ...tapi hasil hitung kas fisik shift yang sudah ditutup dibekukan.
        $this->assertEquals($balanceBeforeBackdate, (float) $shift->expected_balance);
        $this->assertEquals($balanceBeforeBackdate, (float) $shift->actual_balance);
        $this->assertEquals(0, (float) $shift->difference);

        // Estimasi packaging naik 1 cup, hitungan fisik kasir dipertahankan,
        // dan selisihnya sekarang memunculkan kekurangan 1 cup yang tadinya tersembunyi.
        $closing = CashSessionPackagingClosing::where('cash_session_id', $shift->id)
            ->where('packaging_item_id', $packagingItem->id)
            ->first();
        $this->assertEquals(52, (float) $closing->closing_physical_qty);
        $this->assertEquals(9, (float) $closing->estimated_sales_used_qty);
        $this->assertEquals(8, (float) $closing->actual_used_qty);
        $this->assertEquals(-1, (float) $closing->difference_qty);
    }

    public function test_backdate_sale_requires_shift_choice_when_date_has_multiple_shifts(): void
    {
        $user = User::factory()->create();
        $outlet = Outlet::factory()->create(['code' => 'MBK2']);
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'product_type' => 'service',
            'is_sellable' => true,
            'selling_price' => 12000,
        ]);
        $paymentMethod = PaymentMethod::create(['code' => 'CASH', 'name' => 'Cash', 'is_active' => true]);
        $saleDate = today()->subDay()->toDateString();

        foreach ([[8, 14], [15, 21]] as $i => [$open, $close]) {
            CashSession::create([
                'session_number' => 'CS-MBK2-00' . ($i + 1),
                'session_type' => 'regular',
                'outlet_id' => $outlet->id,
                'user_id' => User::factory()->create()->id,
                'opening_balance' => 0,
                'opened_at' => today()->subDay()->setTime($open, 0),
                'closed_at' => today()->subDay()->setTime($close, 0),
                'status' => 'closed',
            ]);
        }

        $payload = [
            'manual_reference' => 'MBK2-AMBIGU-001',
            'sale_date' => $saleDate,
            'outlet_id' => $outlet->id,
            'payment_method_id' => $paymentMethod->id,
            'backdate_reason' => 'Lupa input',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 12000, 'discount_amount' => 0],
            ],
        ];

        $this->expectExceptionMessage('Pilih shift tujuan terlebih dahulu.');
        app(BackdateSaleService::class)->createBackdateSale($payload, $user);
    }

    public function test_backdate_sale_honours_explicitly_selected_shift(): void
    {
        $user = User::factory()->create();
        $outlet = Outlet::factory()->create(['code' => 'MBK2']);
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'product_type' => 'service',
            'is_sellable' => true,
            'selling_price' => 12000,
        ]);
        $paymentMethod = PaymentMethod::create(['code' => 'CASH', 'name' => 'Cash', 'is_active' => true]);
        $saleDate = today()->subDay()->toDateString();

        $shifts = collect([[8, 14], [15, 21]])->map(fn ($range, $i) => CashSession::create([
            'session_number' => 'CS-MBK2-00' . ($i + 1),
            'session_type' => 'regular',
            'outlet_id' => $outlet->id,
            'user_id' => User::factory()->create()->id,
            'opening_balance' => 0,
            'opened_at' => today()->subDay()->setTime($range[0], 0),
            'closed_at' => today()->subDay()->setTime($range[1], 0),
            'status' => 'closed',
        ]));

        $sale = app(BackdateSaleService::class)->createBackdateSale([
            'manual_reference' => 'MBK2-PILIH-001',
            'sale_date' => $saleDate,
            'outlet_id' => $outlet->id,
            'cash_session_id' => $shifts[1]->id,
            'payment_method_id' => $paymentMethod->id,
            'backdate_reason' => 'Lupa input',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 12000, 'discount_amount' => 0],
            ],
        ], $user);

        $this->assertEquals($shifts[1]->id, $sale->cash_session_id);
    }

    public function test_admin_backdate_sale_honours_manual_unit_price_over_catalog_price(): void
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
            'manual_reference' => 'MBK2-MANUAL-PRICE',
            'sale_date' => $saleDate,
            'outlet_id' => $outlet->id,
            'payment_method_id' => $paymentMethod->id,
            'backdate_reason' => 'Harga historis berbeda dari katalog.',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 18000, // harga historis, beda dari selling_price 25000
                    'discount_amount' => 0,
                ],
            ],
        ], $user);

        $this->assertEquals(18000, (float) $sale->items()->first()->unit_price);
    }

    public function test_admin_backdate_sale_falls_back_to_catalog_price_when_unit_price_blank(): void
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
            'manual_reference' => 'MBK2-AUTO-PRICE',
            'sale_date' => $saleDate,
            'outlet_id' => $outlet->id,
            'payment_method_id' => $paymentMethod->id,
            'backdate_reason' => 'Harga ikut katalog.',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => '', // kosong -> auto-isi dari katalog
                    'discount_amount' => 0,
                ],
            ],
        ], $user);

        $this->assertEquals(25000, (float) $sale->items()->first()->unit_price);
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
