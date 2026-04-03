<?php

namespace Tests\Feature;

use App\Models\Outlet;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseIndexFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Outlet $outlet;
    protected Supplier $supplier;
    protected Product $nasi;
    protected Product $minyak;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::query()->where('name', 'superadmin')->firstOrFail();

        $this->user = User::factory()->create([
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $this->actingAs($this->user);

        $this->outlet = Outlet::factory()->create([
            'name' => 'Outlet Audit Pembelian',
        ]);

        $this->supplier = Supplier::factory()->create([
            'name' => 'Supplier Audit',
        ]);

        $this->nasi = Product::factory()->create([
            'name' => 'Nasi',
            'sku' => 'NASI-001',
            'purchase_price' => 16.3,
            'created_by' => $this->user->id,
        ]);

        $this->minyak = Product::factory()->create([
            'name' => 'Minyak Goreng',
            'sku' => 'MNYK-001',
            'purchase_price' => 20,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_purchase_index_can_filter_by_keyword_matching_product_name(): void
    {
        $matchingPurchase = $this->createPurchase(
            purchaseNumber: 'PO-AUDIT-001',
            purchaseDate: '2026-03-15',
            receivedAt: '2026-03-27 15:31:47',
            product: $this->nasi,
        );

        $nonMatchingPurchase = $this->createPurchase(
            purchaseNumber: 'PO-AUDIT-002',
            purchaseDate: '2026-03-15',
            receivedAt: '2026-03-27 16:00:00',
            product: $this->minyak,
        );

        $response = $this->get(route('admin.purchases.index', [
            'keyword' => 'Nasi',
        ]));

        $response->assertOk();
        $response->assertSeeText($matchingPurchase->purchase_number);
        $response->assertDontSeeText($nonMatchingPurchase->purchase_number);
    }

    public function test_purchase_index_can_filter_by_received_date_range(): void
    {
        $receivedOnTargetDate = $this->createPurchase(
            purchaseNumber: 'PO-AUDIT-003',
            purchaseDate: '2026-03-15',
            receivedAt: '2026-03-27 15:31:47',
            product: $this->nasi,
        );

        $receivedOutsideTargetDate = $this->createPurchase(
            purchaseNumber: 'PO-AUDIT-004',
            purchaseDate: '2026-03-27',
            receivedAt: '2026-03-26 15:31:47',
            product: $this->nasi,
        );

        $response = $this->get(route('admin.purchases.index', [
            'received_from' => '2026-03-27',
            'received_to' => '2026-03-27',
        ]));

        $response->assertOk();
        $response->assertSeeText($receivedOnTargetDate->purchase_number);
        $response->assertDontSeeText($receivedOutsideTargetDate->purchase_number);
        $response->assertSeeText('Tanggal Receive');
        $response->assertSeeText('Receive:');
    }

    protected function createPurchase(
        string $purchaseNumber,
        string $purchaseDate,
        string $receivedAt,
        Product $product,
    ): Purchase {
        $purchase = Purchase::create([
            'purchase_number' => $purchaseNumber,
            'outlet_id' => $this->outlet->id,
            'supplier_id' => $this->supplier->id,
            'purchase_date' => $purchaseDate,
            'status' => 'received',
            'received_at' => $receivedAt,
            'received_by' => $this->user->id,
            'subtotal' => 100000,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 100000,
            'payment_status' => 'pending',
            'created_by' => $this->user->id,
        ]);

        PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'quantity' => 1000,
            'unit_price' => $product->purchase_price,
            'discount_amount' => 0,
            'subtotal' => 1000 * $product->purchase_price,
        ]);

        return $purchase;
    }
}
