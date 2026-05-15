<?php

namespace Tests\Feature;

use App\Models\BomDetail;
use App\Models\BomHeader;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductCost;
use App\Models\Production;
use App\Models\Stock;
use App\Models\StockMutation;
use App\Models\User;
use App\Services\ProductionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductionFlowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_consumes_materials_and_adds_production_output_stock(): void
    {
        config(['app.allow_negative_stock' => false]);

        $user = User::create([
            'name' => 'Tester',
            'email' => 'tester@example.com',
            'password' => bcrypt('secret'),
        ]);
        $this->actingAs($user);

        $category = ProductCategory::create(['code' => 'FOOD', 'name' => 'Food']);
        $outlet = Outlet::create(['name' => 'Central Kitchen', 'code' => 'CK']);

        $rawBeef = Product::create([
            'sku' => 'RM-BEEF',
            'name' => 'Daging Mentah',
            'category_id' => $category->id,
            'unit' => 'kg',
            'purchase_price' => 100000,
            'selling_price' => 0,
            'product_type' => 'raw_material',
            'is_active' => true,
        ]);
        $seasoning = Product::create([
            'sku' => 'RM-SEASON',
            'name' => 'Bumbu Marinasi',
            'category_id' => $category->id,
            'unit' => 'kg',
            'purchase_price' => 50000,
            'selling_price' => 0,
            'product_type' => 'raw_material',
            'is_active' => true,
        ]);
        $marinatedBeef = Product::create([
            'sku' => 'SF-BEEF-200',
            'name' => 'Daging Marinasi 200gr',
            'category_id' => $category->id,
            'unit' => 'pcs',
            'purchase_price' => 0,
            'selling_price' => 0,
            'product_type' => 'finished_good',
            'is_active' => true,
        ]);

        Stock::create(['product_id' => $rawBeef->id, 'outlet_id' => $outlet->id, 'quantity' => 20, 'last_mutation_at' => now()]);
        Stock::create(['product_id' => $seasoning->id, 'outlet_id' => $outlet->id, 'quantity' => 5, 'last_mutation_at' => now()]);
        Stock::create(['product_id' => $marinatedBeef->id, 'outlet_id' => $outlet->id, 'quantity' => 10, 'last_mutation_at' => now()]);

        ProductCost::create(['product_id' => $rawBeef->id, 'outlet_id' => $outlet->id, 'avg_cost' => 100000]);
        ProductCost::create(['product_id' => $seasoning->id, 'outlet_id' => $outlet->id, 'avg_cost' => 50000]);
        ProductCost::create(['product_id' => $marinatedBeef->id, 'outlet_id' => $outlet->id, 'avg_cost' => 18000]);

        $bom = BomHeader::create([
            'product_id' => $marinatedBeef->id,
            'name' => 'Marinasi 200gr',
            'source_type' => 'production',
            'is_active' => true,
        ]);
        BomDetail::create(['bom_id' => $bom->id, 'component_product_id' => $rawBeef->id, 'quantity' => 0.2, 'uom' => 'kg']);
        BomDetail::create(['bom_id' => $bom->id, 'component_product_id' => $seasoning->id, 'quantity' => 0.03, 'uom' => 'kg']);

        $production = app(ProductionService::class)->createProduction([
            'bom_id' => $bom->id,
            'outlet_id' => $outlet->id,
            'production_date' => '2026-05-13',
            'quantity' => 50,
            'notes' => 'Batch pagi',
        ]);

        $this->assertInstanceOf(Production::class, $production);
        $this->assertSame('completed', $production->status);

        $this->assertEquals(10, (float) Stock::where('product_id', $rawBeef->id)->where('outlet_id', $outlet->id)->value('quantity'));
        $this->assertEquals(3.5, (float) Stock::where('product_id', $seasoning->id)->where('outlet_id', $outlet->id)->value('quantity'));
        $this->assertEquals(60, (float) Stock::where('product_id', $marinatedBeef->id)->where('outlet_id', $outlet->id)->value('quantity'));

        $this->assertEquals(1075000, (float) $production->fresh()->total_cost);
        $this->assertEquals(21500, (float) $production->fresh()->unit_cost);

        $this->assertDatabaseHas('production_materials', [
            'production_id' => $production->id,
            'product_id' => $rawBeef->id,
        ]);

        $this->assertEquals(3, StockMutation::where('reference_type', 'production')->where('reference_id', $production->id)->count());
        $this->assertDatabaseHas('stock_mutations', [
            'product_id' => $marinatedBeef->id,
            'reference_type' => 'production',
            'reference_id' => $production->id,
            'mutation_type' => 'in',
        ]);
    }

    #[Test]
    public function it_rejects_production_when_material_stock_is_insufficient(): void
    {
        config(['app.allow_negative_stock' => false]);

        $this->actingAs(User::create([
            'name' => 'Tester',
            'email' => 'tester2@example.com',
            'password' => bcrypt('secret'),
        ]));

        $category = ProductCategory::create(['code' => 'FOOD', 'name' => 'Food']);
        $outlet = Outlet::create(['name' => 'Central Kitchen', 'code' => 'CK']);

        $rawBeef = Product::create([
            'sku' => 'RM-BEEF',
            'name' => 'Daging Mentah',
            'category_id' => $category->id,
            'unit' => 'kg',
            'purchase_price' => 100000,
            'selling_price' => 0,
            'product_type' => 'raw_material',
            'is_active' => true,
        ]);
        $marinatedBeef = Product::create([
            'sku' => 'SF-BEEF-200',
            'name' => 'Daging Marinasi 200gr',
            'category_id' => $category->id,
            'unit' => 'pcs',
            'purchase_price' => 0,
            'selling_price' => 0,
            'product_type' => 'finished_good',
            'is_active' => true,
        ]);

        Stock::create(['product_id' => $rawBeef->id, 'outlet_id' => $outlet->id, 'quantity' => 1, 'last_mutation_at' => now()]);

        $bom = BomHeader::create([
            'product_id' => $marinatedBeef->id,
            'name' => 'Marinasi 200gr',
            'source_type' => 'production',
            'is_active' => true,
        ]);
        BomDetail::create(['bom_id' => $bom->id, 'component_product_id' => $rawBeef->id, 'quantity' => 0.2, 'uom' => 'kg']);

        $this->expectExceptionMessage('Stok bahan Daging Mentah tidak mencukupi');

        app(ProductionService::class)->createProduction([
            'bom_id' => $bom->id,
            'outlet_id' => $outlet->id,
            'production_date' => '2026-05-13',
            'quantity' => 10,
        ]);
    }
}
