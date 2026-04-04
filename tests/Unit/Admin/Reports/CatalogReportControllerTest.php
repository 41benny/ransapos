<?php

namespace Tests\Unit\Admin\Reports;

use App\Http\Controllers\Admin\Reports\CatalogReportController;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\BalanceSheetReportService;
use App\Services\ProfitLossReportService;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
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

    public function test_allocate_sales_vs_hpp_line_total_prorates_invoice_adjustment_into_item_total(): void
    {
        $controller = new CatalogReportController(
            Mockery::mock(BalanceSheetReportService::class),
            Mockery::mock(ProfitLossReportService::class),
        );

        $method = new \ReflectionMethod($controller, 'allocateSalesVsHppLineTotal');
        $method->setAccessible(true);

        $allocated = $method->invoke(
            $controller,
            100000.0,
            200000.0,
            220000.0,
            2,
        );

        $this->assertSame(110000.0, $allocated);
    }

    public function test_transform_sales_vs_hpp_row_updates_total_gross_profit_and_margin(): void
    {
        $controller = new CatalogReportController(
            Mockery::mock(BalanceSheetReportService::class),
            Mockery::mock(ProfitLossReportService::class),
        );

        $row = (object) [
            'item_subtotal' => 100000.0,
            'sale_subtotal' => 200000.0,
            'sale_total_amount' => 220000.0,
            'sale_item_count' => 2,
            'hpp_amount' => 70000.0,
        ];

        $method = new \ReflectionMethod($controller, 'transformSalesVsHppRow');
        $method->setAccessible(true);

        $transformed = $method->invoke($controller, $row);

        $this->assertSame(110000.0, $transformed->total_amount);
        $this->assertSame(40000.0, $transformed->gross_profit);
        $this->assertSame(36.36, $transformed->margin_percent);
    }

    public function test_resolve_catalog_outlet_ids_accepts_multi_select_for_sales_vs_hpp(): void
    {
        $controller = new CatalogReportController(
            Mockery::mock(BalanceSheetReportService::class),
            Mockery::mock(ProfitLossReportService::class),
        );

        $method = new \ReflectionMethod($controller, 'resolveCatalogOutletIds');
        $method->setAccessible(true);

        $resolved = $method->invoke(
            $controller,
            new Request(['outlet_ids' => ['2', '99', '3', '2']]),
            new Collection([1, 2, 3]),
        );

        $this->assertSame([2, 3], $resolved);
    }

    public function test_resolve_catalog_outlet_ids_falls_back_to_legacy_single_outlet_id(): void
    {
        $controller = new CatalogReportController(
            Mockery::mock(BalanceSheetReportService::class),
            Mockery::mock(ProfitLossReportService::class),
        );

        $method = new \ReflectionMethod($controller, 'resolveCatalogOutletIds');
        $method->setAccessible(true);

        $resolved = $method->invoke(
            $controller,
            new Request(['outlet_id' => '3']),
            new Collection([1, 2, 3]),
        );

        $this->assertSame([3], $resolved);
    }

    public function test_passes_purchase_by_product_filters_matches_product_text_and_numeric_columns(): void
    {
        $controller = new CatalogReportController(
            Mockery::mock(BalanceSheetReportService::class),
            Mockery::mock(ProfitLossReportService::class),
        );

        $row = (object) [
            'product_name' => 'Nasi',
            'product_sku' => '000182',
            'total_purchase_count' => 3,
            'total_qty' => 2000,
            'avg_unit_price' => 16.30,
            'total_amount' => 32600,
        ];

        $request = new Request([
            'filter_product' => '182',
            'filter_jumlah_po' => '3',
            'filter_qty' => '2.000',
            'filter_avg' => '16,30',
            'filter_amount' => '32.600',
        ]);

        $method = new \ReflectionMethod($controller, 'passesPurchaseByProductFilters');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($controller, $row, $request));
        $this->assertTrue($method->invoke($controller, $row, new Request([
            'filter_product' => 'Nasi',
        ])));

        $nonMatchingRequest = new Request([
            'filter_product' => 'Minyak',
        ]);

        $this->assertFalse($method->invoke($controller, $row, $nonMatchingRequest));
    }
}
