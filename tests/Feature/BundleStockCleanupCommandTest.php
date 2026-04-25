<?php

namespace Tests\Feature;

use App\Models\BomHeader;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BundleStockCleanupCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        foreach (glob(storage_path('app/private/repairs/bundle_stock_cleanup_*.json')) ?: [] as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    public function test_cleanup_command_removes_bundle_stock_records_only(): void
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

        $bundleStock = Stock::query()->create([
            'product_id' => $bundleProduct->id,
            'outlet_id' => $outlet->id,
            'quantity' => -12,
            'last_mutation_at' => now(),
        ]);

        $rawStock = Stock::query()->create([
            'product_id' => $rawMaterial->id,
            'outlet_id' => $outlet->id,
            'quantity' => 40,
            'last_mutation_at' => now(),
        ]);

        $this->assertFalse($bundleProduct->fresh()->isStockTracked());
        $this->assertTrue($rawMaterial->fresh()->isStockTracked());

        $this->artisan('stocks:cleanup-bundle-records --product-like=Frozen')
            ->expectsOutputToContain('"status": "dry-run"')
            ->assertExitCode(0);

        $this->assertDatabaseHas('stocks', ['id' => $bundleStock->id]);
        $this->assertDatabaseHas('stocks', ['id' => $rawStock->id]);

        $this->artisan('stocks:cleanup-bundle-records --apply --product-like=Frozen')
            ->expectsOutputToContain('"status": "applied"')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('stocks', ['id' => $bundleStock->id]);
        $this->assertDatabaseHas('stocks', ['id' => $rawStock->id]);
    }
}
