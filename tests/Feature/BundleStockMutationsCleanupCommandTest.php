<?php

namespace Tests\Feature;

use App\Models\BomHeader;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockMutation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BundleStockMutationsCleanupCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        foreach (glob(storage_path('app/private/repairs/bundle_stock_mutations_cleanup_*.json')) ?: [] as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    public function test_cleanup_command_removes_bundle_stock_mutations_only(): void
    {
        $user = User::factory()->create(['id' => 1, 'is_active' => true]);
        $category = ProductCategory::factory()->create([
            'code' => 'DIMSUM',
            'name' => 'Dimsum',
        ]);
        $outlet = Outlet::factory()->create([
            'id' => 1,
            'code' => 'OUT001',
            'name' => 'Gudang',
        ]);

        $bundleProduct = Product::factory()->create([
            'created_by' => $user->id,
            'category_id' => $category->id,
            'sku' => 'PRD-FROZEN-001',
            'name' => 'Frozen Dimsum',
            'product_type' => 'finished_good',
        ]);

        BomHeader::query()->create([
            'product_id' => $bundleProduct->id,
            'name' => 'Resep Frozen Dimsum',
            'source_type' => 'bundle',
            'is_active' => true,
        ]);

        $rawMaterial = Product::factory()->create([
            'created_by' => $user->id,
            'category_id' => $category->id,
            'sku' => 'RM-UDANG-001',
            'name' => 'Udang',
            'product_type' => 'raw_material',
        ]);

        $bundleMutation = StockMutation::query()->create([
            'product_id' => $bundleProduct->id,
            'outlet_id' => $outlet->id,
            'mutation_type' => 'out',
            'quantity' => -5,
            'unit_cost' => 0,
            'total_cost' => 0,
            'stock_before' => 0,
            'stock_after' => -5,
            'reference_type' => 'sale',
            'reference_id' => 123,
            'mutation_date' => now()->toDateString(),
            'notes' => 'Penjualan: Frozen Dimsum',
            'created_by' => $user->id,
        ]);

        $rawMutation = StockMutation::query()->create([
            'product_id' => $rawMaterial->id,
            'outlet_id' => $outlet->id,
            'mutation_type' => 'out',
            'quantity' => -20,
            'unit_cost' => 100,
            'total_cost' => 2000,
            'stock_before' => 100,
            'stock_after' => 80,
            'reference_type' => 'sale',
            'reference_id' => 123,
            'mutation_date' => now()->toDateString(),
            'notes' => 'Penjualan: Frozen Dimsum',
            'created_by' => $user->id,
        ]);

        $this->artisan('stocks:cleanup-bundle-mutations --product-like=Frozen')
            ->expectsOutputToContain('"status": "dry-run"')
            ->assertExitCode(0);

        $this->assertDatabaseHas('stock_mutations', ['id' => $bundleMutation->id]);
        $this->assertDatabaseHas('stock_mutations', ['id' => $rawMutation->id]);

        $this->artisan('stocks:cleanup-bundle-mutations --apply --product-like=Frozen')
            ->expectsOutputToContain('"status": "applied"')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('stock_mutations', ['id' => $bundleMutation->id]);
        $this->assertDatabaseHas('stock_mutations', ['id' => $rawMutation->id]);
    }
}
