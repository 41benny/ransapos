<?php

namespace Tests\Feature;

use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\Stock;
use App\Models\StockMutation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class StockCardPaginationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Outlet $outlet;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::query()->where('name', 'superadmin')->firstOrFail();

        $this->user = User::factory()->create([
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $this->actingAs($this->user);

        $category = ProductCategory::create([
            'code' => 'FG',
            'name' => 'Finished Goods',
        ]);

        $this->outlet = Outlet::create([
            'name' => 'Outlet Audit',
            'code' => 'OUT-AUDIT',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'sku' => 'SKU-STOCK-CARD',
            'name' => 'Produk Stock Card',
            'category_id' => $category->id,
            'unit' => 'pcs',
            'purchase_price' => 10000,
            'selling_price' => 15000,
            'product_type' => 'finished_good',
            'min_stock' => 0,
            'is_active' => true,
        ]);
    }

    public function test_stock_card_paginate_results_and_keep_global_summary_values(): void
    {
        $this->seedStockMutations(120);

        $response = $this->get(route('admin.stocks.card', [
            'product_id' => $this->product->id,
            'outlet_id' => $this->outlet->id,
        ]));

        $response->assertOk();
        $response->assertSeeText('Menampilkan 1-100 dari 120 transaksi');
        $response->assertViewHas('mutations', function ($mutations) {
            return $mutations instanceof LengthAwarePaginator
                && $mutations->total() === 120
                && $mutations->count() === 100
                && $mutations->currentPage() === 1;
        });
        $response->assertViewHas('latestUnitCost', fn ($value) => abs((float) $value - 10120.0) < 0.001);
        $response->assertViewHas('totalIn', fn ($value) => abs((float) $value - 120.0) < 0.001);
        $response->assertViewHas('totalOut', fn ($value) => abs((float) $value - 0.0) < 0.001);
        $response->assertViewHas('netChange', fn ($value) => abs((float) $value - 120.0) < 0.001);
    }

    public function test_stock_card_second_page_shows_expected_rows(): void
    {
        $this->seedStockMutations(120);

        $response = $this->get(route('admin.stocks.card', [
            'product_id' => $this->product->id,
            'outlet_id' => $this->outlet->id,
            'page' => 2,
        ]));

        $response->assertOk();
        $response->assertSeeText('Menampilkan 101-120 dari 120 transaksi');
        $response->assertSeeText('mutation-101');
        $response->assertSeeText('mutation-120');
        $response->assertDontSeeText('mutation-001');
        $response->assertViewHas('mutations', function ($mutations) {
            return $mutations instanceof LengthAwarePaginator
                && $mutations->currentPage() === 2
                && $mutations->count() === 20
                && $mutations->firstItem() === 101
                && $mutations->lastItem() === 120;
        });
    }

    protected function seedStockMutations(int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            StockMutation::create([
                'product_id' => $this->product->id,
                'outlet_id' => $this->outlet->id,
                'mutation_type' => 'in',
                'quantity' => 1,
                'unit_cost' => 10000 + $i,
                'total_cost' => 10000 + $i,
                'stock_before' => $i - 1,
                'stock_after' => $i,
                'reference_type' => 'purchase',
                'reference_id' => $i,
                'mutation_date' => now()->startOfDay()->addDays($i - 1),
                'notes' => sprintf('mutation-%03d', $i),
                'created_by' => $this->user->id,
            ]);
        }

        Stock::create([
            'product_id' => $this->product->id,
            'outlet_id' => $this->outlet->id,
            'quantity' => $count,
            'last_mutation_at' => now(),
        ]);
    }
}
