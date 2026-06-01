<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::query()->where('name', 'superadmin')->firstOrFail();
        $user = User::factory()->create([
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $this->actingAs($user);
    }

    public function test_admin_can_manage_product_categories(): void
    {
        $this->get(route('admin.product-categories.index'))
            ->assertOk()
            ->assertViewIs('admin.product-categories.index');

        $this->post(route('admin.product-categories.store'), [
            'code' => 'minuman dingin',
            'name' => 'Minuman Dingin',
            'description' => 'Kategori untuk minuman siap jual.',
            'is_active' => '1',
        ])->assertRedirect(route('admin.product-categories.index'));

        $category = ProductCategory::query()->where('code', 'MINUMAN_DINGIN')->firstOrFail();

        $this->put(route('admin.product-categories.update', $category), [
            'code' => 'minuman botol',
            'name' => 'Minuman Botol',
            'description' => 'Kategori minuman kemasan.',
        ])->assertRedirect(route('admin.product-categories.index'));

        $category->refresh();

        $this->assertSame('MINUMAN_BOTOL', $category->code);
        $this->assertSame('Minuman Botol', $category->name);
        $this->assertFalse($category->is_active);

        $this->delete(route('admin.product-categories.destroy', $category))
            ->assertRedirect(route('admin.product-categories.index'));

        $this->assertModelMissing($category);
    }

    public function test_category_with_products_cannot_be_deleted(): void
    {
        $category = ProductCategory::factory()->create();
        Product::factory()->create([
            'category_id' => $category->id,
        ]);

        $this->delete(route('admin.product-categories.destroy', $category))
            ->assertSessionHas('error');

        $this->assertModelExists($category);
    }
}
