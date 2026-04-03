<?php

namespace Tests\Unit\Admin\Reports;

use App\Http\Controllers\Admin\Reports\CatalogReportController;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\BalanceSheetReportService;
use App\Services\ProfitLossReportService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

class CatalogReportControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_build_sales_discount_row_uses_safe_fallbacks_for_missing_relation_data(): void
    {
        $controller = new CatalogReportController(
            Mockery::mock(BalanceSheetReportService::class),
            Mockery::mock(ProfitLossReportService::class),
        );

        $product = new Product([
            'name' => 'Produk Test',
            'selling_price' => 12000,
        ]);

        $item = new SaleItem([
            'product_name' => 'Produk Test',
            'product_sku' => 'SKU-TEST',
            'quantity' => 1,
            'unit_price' => 0,
            'discount_amount' => 2000,
            'subtotal' => 10000,
        ]);
        $item->setRelation('product', $product);

        $sale = new Sale([
            'id' => 99,
            'invoice_number' => 'INV-TEST-99',
            'sale_date' => null,
            'sales_type' => 'regular',
            'discount_type' => 'none',
            'discount_amount' => 0,
            'total_amount' => 10000,
            'customer_name' => null,
            'notes' => 'test',
        ]);

        $sale->setRelation('items', new EloquentCollection([$item]));
        $sale->setRelation('outlet', null);
        $sale->setRelation('promotion', null);
        $sale->setRelation('voucher', null);
        $sale->setRelation('customer', null);

        $method = new \ReflectionMethod($controller, 'buildSalesDiscountRow');
        $method->setAccessible(true);

        $row = $method->invoke($controller, $sale);

        $this->assertSame('-', $row['sale_date']);
        $this->assertSame('-', $row['outlet_name']);
        $this->assertSame('Walk-in', $row['customer_name']);
        $this->assertSame(12000.0, $row['gross_value']);
        $this->assertSame(2000.0, $row['effective_discount']);
    }

    public function test_build_sales_discount_row_from_record_uses_lightweight_query_payload(): void
    {
        $controller = new CatalogReportController(
            Mockery::mock(BalanceSheetReportService::class),
            Mockery::mock(ProfitLossReportService::class),
        );

        $record = (object) [
            'id' => 7,
            'invoice_number' => 'INV-RINGKAS-7',
            'sale_date' => '2026-04-03',
            'outlet_name' => null,
            'sales_type' => 'compliment',
            'discount_type' => 'none',
            'discount_amount' => 0,
            'item_discount_amount' => 0,
            'total_amount' => 0,
            'gross_value' => 35000,
            'promotion_name' => null,
            'promotion_code' => null,
            'voucher_name' => null,
            'voucher_table_code' => null,
            'voucher_code' => null,
            'customer_name' => '',
            'customer_relation_name' => null,
            'notes' => null,
        ];

        $method = new \ReflectionMethod($controller, 'buildSalesDiscountRowFromRecord');
        $method->setAccessible(true);

        $row = $method->invoke($controller, $record);

        $this->assertSame('2026-04-03', $row['sale_date']);
        $this->assertSame('-', $row['outlet_name']);
        $this->assertSame('Walk-in', $row['customer_name']);
        $this->assertTrue($row['is_discount_anomaly']);
        $this->assertSame('Compliment (Anomali)', $row['discount_source_label']);
        $this->assertSame(35000.0, $row['gross_value']);
    }

    public function test_build_export_payload_accepts_paginated_sales_vs_hpp_rows(): void
    {
        $controller = new CatalogReportController(
            Mockery::mock(BalanceSheetReportService::class),
            Mockery::mock(ProfitLossReportService::class),
        );

        $rows = new LengthAwarePaginator(
            items: collect([
                (object) [
                    'transaction_number' => 'INV-001',
                    'sale_date' => '2026-04-03',
                    'outlet_name' => 'Outlet A',
                    'product_name' => 'Produk A',
                    'qty' => 2,
                    'total_amount' => 40000,
                    'hpp_amount' => 25000,
                    'gross_profit' => 15000,
                    'margin_percent' => 37.5,
                ],
            ]),
            total: 1,
            perPage: 250,
            currentPage: 1,
        );

        $method = new \ReflectionMethod($controller, 'buildExportPayload');
        $method->setAccessible(true);

        [$columns, $exportRows] = $method->invoke($controller, 'sales-vs-hpp', $rows, []);

        $this->assertCount(9, $columns);
        $this->assertCount(1, $exportRows);
        $this->assertSame('INV-001', $exportRows[0]['transaction_number']);
        $this->assertSame(15000, $exportRows[0]['gross_profit']);
    }
}
