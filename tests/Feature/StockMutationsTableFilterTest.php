<?php

namespace Tests\Feature;

use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\StockMutation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class StockMutationsTableFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Outlet $outlet;
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

        $this->actingAs($this->user);

        $category = ProductCategory::create([
            'code' => 'FG',
            'name' => 'Finished Goods',
        ]);

        $this->outlet = Outlet::create([
            'name' => 'Outlet Filter',
            'code' => 'OUT-FILTER',
            'is_active' => true,
        ]);

        $this->matchingProduct = Product::create([
            'sku' => 'MATCH-001',
            'name' => 'Produk Cocok',
            'category_id' => $category->id,
            'unit' => 'pcs',
            'purchase_price' => 10000,
            'selling_price' => 15000,
            'product_type' => 'finished_good',
            'min_stock' => 0,
            'is_active' => true,
        ]);

        $this->otherProduct = Product::create([
            'sku' => 'OTHER-001',
            'name' => 'Produk Lain',
            'category_id' => $category->id,
            'unit' => 'pcs',
            'purchase_price' => 12000,
            'selling_price' => 17000,
            'product_type' => 'finished_good',
            'min_stock' => 0,
            'is_active' => true,
        ]);
    }

    public function test_all_tab_header_filter_applies_and_pagination_keeps_query_string(): void
    {
        $this->seedMutations($this->matchingProduct, 55, 'note-match');
        $this->seedMutations($this->otherProduct, 10, 'note-other');

        $response = $this->get(route('admin.stocks.mutations', [
            'tab' => 'all',
            'filter_produk' => 'Produk Cocok',
            'page' => 2,
        ]));

        $response->assertOk();
        $response->assertDontSeeText('Produk Lain');
        $response->assertViewHas('mutations', function ($mutations) {
            return $mutations instanceof LengthAwarePaginator
                && $mutations->total() === 55
                && $mutations->currentPage() === 2
                && $mutations->count() === 5
                && str_contains(urldecode($mutations->url(2)), 'filter_produk=Produk Cocok');
        });
    }

    protected function seedMutations(Product $product, int $count, string $notePrefix): void
    {
        for ($i = 1; $i <= $count; $i++) {
            StockMutation::create([
                'product_id' => $product->id,
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
                'notes' => sprintf('%s-%03d', $notePrefix, $i),
                'created_by' => $this->user->id,
            ]);
        }
    }
}
