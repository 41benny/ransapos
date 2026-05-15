<?php

namespace Tests\Unit\Services;

use App\Models\Outlet;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PurchaseJournalExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseJournalExportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_monthly_rows_generates_inventory_against_supplier_payable(): void
    {
        $user = User::factory()->create();
        $outlet = Outlet::factory()->create([
            'name' => 'Domesteak CENTRAL PLAZA',
            'code' => 'OUT02',
            'is_active' => true,
        ]);
        $supplier = Supplier::factory()->create([
            'code' => 'AMR',
            'name' => 'Amir Sayur',
        ]);

        $this->createPurchase($outlet->id, $supplier->id, $user->id, '2026-04-10', 125000, 'received');
        $this->createPurchase($outlet->id, $supplier->id, $user->id, '2026-04-11', 50000, 'draft');
        $this->createPurchase($outlet->id, $supplier->id, $user->id, '2026-05-01', 75000, 'received');

        $rows = app(PurchaseJournalExportService::class)->buildMonthlyRows('2026-04', [$outlet->id]);

        $this->assertCount(2, $rows);
        $this->assertSame('PEMBELIAN', $rows[0]['STATUS']);
        $this->assertSame('PURCP0426', $rows[0]['_VOUCHER']);
        $this->assertSame(1117002, $rows[0]['NO_AKUN']);
        $this->assertSame('D', $rows[0]['J_MUTASI']);
        $this->assertSame(125000.0, (float) $rows[0]['D']);
        $this->assertSame('Persediaan Barang Dagang CP', $rows[0]['KET 2']);

        $this->assertSame(2102001, $rows[1]['NO_AKUN']);
        $this->assertSame('K', $rows[1]['J_MUTASI']);
        $this->assertSame(125000.0, (float) $rows[1]['K']);
        $this->assertSame('Hutang Usaha Amir Sayur', $rows[1]['KET 2']);
        $this->assertSame(
            (float) collect($rows)->sum(fn ($row) => (float) ($row['D'] ?? 0)),
            (float) collect($rows)->sum(fn ($row) => (float) ($row['K'] ?? 0))
        );
    }

    public function test_build_monthly_rows_maps_transmart_inventory_and_supplier_payable(): void
    {
        $user = User::factory()->create();
        $outlet = Outlet::factory()->create([
            'name' => 'Transmart',
            'code' => 'TRM',
            'is_active' => true,
        ]);
        $supplier = Supplier::factory()->create([
            'code' => 'ELP',
            'name' => 'PT. Elpinas',
        ]);

        $this->createPurchase($outlet->id, $supplier->id, $user->id, '2026-04-15', 99000, 'received');

        $rows = app(PurchaseJournalExportService::class)->buildMonthlyRows('2026-04', [$outlet->id]);

        $this->assertCount(2, $rows);
        $this->assertSame('PURTRM0426', $rows[0]['_VOUCHER']);
        $this->assertSame(1117011, $rows[0]['NO_AKUN']);
        $this->assertSame('Persediaan Barang Dagang Transmart', $rows[0]['KET 2']);
        $this->assertSame(2102019, $rows[1]['NO_AKUN']);
        $this->assertSame('Hutang Usaha Pt. Elpinas', $rows[1]['KET 2']);
    }

    public function test_build_monthly_rows_uses_other_payable_for_unmapped_supplier(): void
    {
        $user = User::factory()->create();
        $outlet = Outlet::factory()->create([
            'name' => 'Pahoman',
            'code' => 'PHM',
            'is_active' => true,
        ]);
        $supplier = Supplier::factory()->create([
            'code' => 'NEW',
            'name' => 'Supplier Baru Tidak Ada Mapping',
        ]);

        $this->createPurchase($outlet->id, $supplier->id, $user->id, '2026-04-20', 45000, 'received');

        $rows = app(PurchaseJournalExportService::class)->buildMonthlyRows('2026-04', [$outlet->id]);

        $this->assertCount(2, $rows);
        $this->assertSame(1117006, $rows[0]['NO_AKUN']);
        $this->assertSame(2102020, $rows[1]['NO_AKUN']);
        $this->assertSame('Hutang Usaha Lain-lain', $rows[1]['KET 2']);
    }

    private function createPurchase(int $outletId, int $supplierId, int $userId, string $purchaseDate, float $amount, string $status): Purchase
    {
        return Purchase::query()->create([
            'purchase_number' => 'PO-' . uniqid(),
            'outlet_id' => $outletId,
            'supplier_id' => $supplierId,
            'purchase_date' => $purchaseDate,
            'status' => $status,
            'received_at' => $status === 'received' ? $purchaseDate . ' 10:00:00' : null,
            'received_by' => $status === 'received' ? $userId : null,
            'subtotal' => $amount,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => $amount,
            'payment_status' => 'pending',
            'created_by' => $userId,
        ]);
    }
}

