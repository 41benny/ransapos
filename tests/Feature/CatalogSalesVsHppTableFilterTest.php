<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\Reports\CatalogReportController;
use App\Models\CashSession;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\User;
use App\Services\BalanceSheetReportService;
use App\Services\ProfitLossReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

class CatalogSalesVsHppTableFilterTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use RefreshDatabase;

    protected User $user;
    protected Outlet $outlet;
    protected CashSession $cashSession;
    protected Product $matchingProduct;
    protected Product $otherProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::query()->where('name', 'superadmin')->firstOrFail();

        $this->user = User::factory()->create([
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $category = ProductCategory::create([
            'code' => 'FG',
            'name' => 'Finished Goods',
        ]);

        $this->outlet = Outlet::create([
            'name' => 'Outlet Sales HPP',
            'code' => 'OUT-SALES-HPP',
            'is_active' => true,
        ]);

        $this->cashSession = CashSession::create([
            'session_number' => 'CS-SALES-HPP-001',
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'opening_balance' => 100000,
            'expected_balance' => 100000,
            'actual_balance' => 100000,
            'difference' => 0,
            'total_sales' => 0,
            'total_cash' => 0,
            'total_non_cash' => 0,
            'opened_at' => now()->subHour(),
            'status' => 'open',
        ]);

        $this->matchingProduct = Product::create([
            'sku' => 'SKU-MATCH-HPP',
            'name' => 'Produk Match HPP',
            'category_id' => $category->id,
            'unit' => 'pcs',
            'purchase_price' => 10000,
            'selling_price' => 18000,
            'product_type' => 'finished_good',
            'min_stock' => 0,
            'is_active' => true,
        ]);

        $this->otherProduct = Product::create([
            'sku' => 'SKU-OTHER-HPP',
            'name' => 'Produk Other HPP',
            'category_id' => $category->id,
            'unit' => 'pcs',
            'purchase_price' => 9000,
            'selling_price' => 15000,
            'product_type' => 'finished_good',
            'min_stock' => 0,
            'is_active' => true,
        ]);
    }

    public function test_sales_vs_hpp_query_filter_applies_and_pagination_keeps_query_string(): void
    {
        $this->seedSaleRow('INV-HPP-001', $this->matchingProduct, '2026-04-04', 2, 36000, 22000);
        $this->seedSaleRow('INV-HPP-002', $this->otherProduct, '2026-04-04', 1, 15000, 9000);

        $this->assertDatabaseCount('sales', 2);
        $this->assertDatabaseCount('sale_items', 2);

        $controller = new CatalogReportController(
            Mockery::mock(BalanceSheetReportService::class),
            Mockery::mock(ProfitLossReportService::class),
        );

        $request = Request::create('/admin/reports/catalog/sales-vs-hpp', 'GET', [
            'slug' => 'sales-vs-hpp',
            'date_from' => '2026-04-04',
            'date_to' => '2026-04-04',
            'filter_product' => 'Produk Match HPP',
        ]);

        app()->instance('request', $request);

        $saleItemCountSub = DB::table('sale_items')
            ->select('sale_items.sale_id')
            ->selectRaw('COUNT(*) as sale_item_count')
            ->groupBy('sale_items.sale_id');

        $rowsQuery = DB::table('sales')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sale_date', ['2026-04-04', '2026-04-04'])
            ->join('sale_items', 'sale_items.sale_id', '=', 'sales.id')
            ->join('outlets', 'sales.outlet_id', '=', 'outlets.id')
            ->leftJoinSub($saleItemCountSub, 'sale_item_counts', function ($join) {
                $join->on('sale_item_counts.sale_id', '=', 'sales.id');
            })
            ->select(
                'sales.id as sale_id',
                'sales.invoice_number as transaction_number',
                'sales.sale_date',
                'sales.outlet_id',
                'outlets.name as outlet_name',
                'sale_items.product_name',
                'sale_items.quantity as qty',
                'sale_items.subtotal as item_subtotal',
                'sales.subtotal as sale_subtotal',
                'sales.total_amount as sale_total_amount',
                'sale_item_counts.sale_item_count',
                'sale_items.cogs as hpp_amount'
            )
            ->orderByDesc('sales.sale_date')
            ->orderByDesc('sales.id')
            ->orderByDesc('sale_items.id');

        $this->assertSame(2, (clone $rowsQuery)->count());

        $method = new \ReflectionMethod($controller, 'applySalesVsHppTableFilters');
        $method->setAccessible(true);
        $method->invoke($controller, $rowsQuery, $request);

        $paginator = $rowsQuery->paginate(250)->withQueryString();

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);
        $this->assertSame(1, $paginator->total());
        $this->assertSame('Produk Match HPP', $paginator->items()[0]->product_name);
        $this->assertStringContainsString('filter_product=Produk Match HPP', urldecode($paginator->url(2)));
    }

    protected function seedSaleRow(
        string $invoiceNumber,
        Product $product,
        string $saleDate,
        float $quantity,
        float $subtotal,
        float $cogs
    ): void {
        $now = now();

        $saleId = DB::table('sales')->insertGetId([
            'invoice_number' => $invoiceNumber,
            'outlet_id' => $this->outlet->id,
            'cash_session_id' => $this->cashSession->id,
            'user_id' => $this->user->id,
            'sale_date' => $saleDate,
            'sales_type' => 'regular',
            'subtotal' => $subtotal,
            'discount_type' => 'none',
            'discount_value' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'service_charge_amount' => 0,
            'rounding_amount' => 0,
            'total_amount' => $subtotal,
            'customer_name' => 'Walk-in',
            'status' => 'completed',
            'kitchen_status' => 'done',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('sale_items')->insert([
            'sale_id' => $saleId,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'quantity' => $quantity,
            'unit_price' => $quantity > 0 ? round($subtotal / $quantity, 2) : $subtotal,
            'discount_amount' => 0,
            'subtotal' => $subtotal,
            'cogs' => $cogs,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
