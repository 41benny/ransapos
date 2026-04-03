<?php

namespace Tests\Unit\Admin\Reports;

use App\Http\Controllers\Admin\Reports\CatalogReportController;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\BalanceSheetReportService;
use App\Services\ProfitLossReportService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
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
}
