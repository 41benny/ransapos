<?php

namespace Tests\Feature;

use App\Models\CashAccount;
use App\Models\CashSession;
use App\Models\CashTransaction;
use App\Models\ExpenseCategory;
use App\Models\Outlet;
use App\Models\PosDevice;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class RemainingPaginationPagesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();

        $role = Role::query()->where('name', 'superadmin')->firstOrFail();

        $this->admin = User::factory()->create([
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin);
    }

    public function test_debt_report_index_and_detail_are_paginated(): void
    {
        $outlet = $this->createOutlet('Debt Outlet', 'DEBT-OUT');

        for ($i = 1; $i <= 55; $i++) {
            $supplier = Supplier::create([
                'code' => 'SUP-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'name' => 'Supplier ' . $i,
                'phone' => '0800' . str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                'is_active' => true,
            ]);

            Purchase::create([
                'purchase_number' => 'PO-INDEX-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'outlet_id' => $outlet->id,
                'supplier_id' => $supplier->id,
                'purchase_date' => now()->subDays(5),
                'status' => 'received',
                'subtotal' => 100000,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => 100000,
                'payment_status' => 'pending',
                'created_by' => $this->admin->id,
            ]);
        }

        $indexResponse = $this->get(route('admin.reports.debts.index'));

        $indexResponse->assertOk();
        $indexResponse->assertViewHas('suppliers', function ($suppliers) {
            return $suppliers instanceof LengthAwarePaginator
                && $suppliers->hasPages()
                && $suppliers->perPage() === 50
                && $suppliers->count() === 50;
        });

        $detailSupplier = Supplier::create([
            'code' => 'SUP-DETAIL',
            'name' => 'Supplier Detail',
            'phone' => '081234567890',
            'is_active' => true,
        ]);

        for ($i = 1; $i <= 105; $i++) {
            Purchase::create([
                'purchase_number' => 'PO-DETAIL-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'outlet_id' => $outlet->id,
                'supplier_id' => $detailSupplier->id,
                'purchase_date' => now()->startOfMonth()->addDays($i % 28),
                'status' => 'received',
                'subtotal' => 50000,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => 50000,
                'payment_status' => 'pending',
                'created_by' => $this->admin->id,
            ]);
        }

        $showResponse = $this->get(route('admin.reports.debts.show', [
            'supplier' => $detailSupplier,
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
        ]));

        $showResponse->assertOk();
        $showResponse->assertViewHas('mutations', function ($mutations) {
            return $mutations instanceof LengthAwarePaginator
                && $mutations->hasPages()
                && $mutations->perPage() === 100
                && $mutations->count() <= 100;
        });
    }

    public function test_cash_account_pages_are_paginated(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            CashAccount::create([
                'name' => 'Kas ' . $i,
                'code' => 'CA-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'type' => $i % 2 === 0 ? 'bank' : 'cash',
                'usage_type' => 'operational',
                'is_active' => true,
                'opening_balance' => 100000,
                'current_balance' => 100000,
                'created_by' => $this->admin->id,
            ]);
        }

        $indexResponse = $this->get(route('admin.cash-accounts.index'));

        $indexResponse->assertOk();
        $indexResponse->assertViewHas('accounts', function ($accounts) {
            return $accounts instanceof LengthAwarePaginator
                && $accounts->hasPages()
                && $accounts->perPage() === 20
                && $accounts->count() === 20;
        });

        $account = CashAccount::create([
            'name' => 'Kas Mutasi',
            'code' => 'CA-MUT',
            'type' => 'cash',
            'usage_type' => 'operational',
            'is_active' => true,
            'opening_balance' => 0,
            'current_balance' => 105000,
            'created_by' => $this->admin->id,
        ]);

        $runningBalance = 0;
        for ($i = 1; $i <= 105; $i++) {
            $balanceBefore = $runningBalance;
            $runningBalance += 1000;

            CashTransaction::create([
                'transaction_number' => 'CTX-' . str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'voucher_number' => 'VCH-' . str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'cash_account_id' => $account->id,
                'type' => 'in',
                'transaction_date' => now()->startOfMonth()->addDays($i % 28),
                'amount' => 1000,
                'balance_before' => $balanceBefore,
                'balance_after' => $runningBalance,
                'description' => 'Mutasi ' . $i,
                'created_by' => $this->admin->id,
            ]);
        }

        $reportResponse = $this->get(route('admin.cash-accounts.mutation-report', [
            'cashAccount' => $account,
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->endOfMonth()->toDateString(),
        ]));

        $reportResponse->assertOk();
        $reportResponse->assertViewHas('transactions', function ($transactions) {
            return $transactions instanceof LengthAwarePaginator
                && $transactions->hasPages()
                && $transactions->perPage() === 100
                && $transactions->count() === 100;
        });
    }

    public function test_admin_listing_pages_are_paginated(): void
    {
        for ($i = 1; $i <= 24; $i++) {
            Role::create([
                'name' => 'role-' . $i,
                'display_name' => 'Role ' . $i,
                'description' => 'Role testing ' . $i,
            ]);
        }

        $permissionsResponse = $this->get(route('admin.permissions.index'));
        $permissionsResponse->assertOk();
        $permissionsResponse->assertViewHas('roles', function ($roles) {
            return $roles instanceof LengthAwarePaginator
                && $roles->hasPages()
                && $roles->perPage() === 20
                && $roles->count() === 20;
        });

        for ($i = 1; $i <= 25; $i++) {
            ExpenseCategory::create([
                'name' => 'Kategori ' . $i,
                'code' => 'EXP-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'description' => 'Kategori testing ' . $i,
                'is_active' => true,
                'order' => $i,
            ]);
        }

        $expenseCategoriesResponse = $this->get(route('admin.expense-categories.index'));
        $expenseCategoriesResponse->assertOk();
        $expenseCategoriesResponse->assertViewHas('parentCategories', function ($categories) {
            return $categories instanceof LengthAwarePaginator
                && $categories->hasPages()
                && $categories->perPage() === 20
                && $categories->count() === 20;
        });

        $outlet = $this->createOutlet('POS Device Outlet', 'POS-OUT');
        for ($i = 1; $i <= 25; $i++) {
            PosDevice::create([
                'outlet_id' => $outlet->id,
                'name' => 'Device ' . $i,
                'device_type' => 'kasir',
                'is_active' => true,
                'created_by' => $this->admin->id,
            ]);
        }

        $posDevicesResponse = $this->get(route('admin.pos-devices.index'));
        $posDevicesResponse->assertOk();
        $posDevicesResponse->assertViewHas('devices', function ($devices) {
            return $devices instanceof LengthAwarePaginator
                && $devices->hasPages()
                && $devices->perPage() === 20
                && $devices->count() === 20;
        });
    }

    public function test_pos_dashboard_paginates_today_sales(): void
    {
        $outlet = $this->createOutlet('POS Outlet', 'POS-DASH');

        $posUser = User::factory()->create([
            'role_id' => $this->admin->role_id,
            'outlet_id' => $outlet->id,
            'is_active' => true,
        ]);

        $this->actingAs($posUser);

        $session = CashSession::create([
            'session_number' => 'CS-0001',
            'outlet_id' => $outlet->id,
            'user_id' => $posUser->id,
            'opening_balance' => 100000,
            'expected_balance' => 0,
            'difference' => 0,
            'total_sales' => 0,
            'total_cash' => 0,
            'total_non_cash' => 0,
            'opened_at' => now()->subHours(2),
            'status' => 'open',
        ]);

        for ($i = 1; $i <= 25; $i++) {
            Sale::create([
                'invoice_number' => 'INV-' . str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'outlet_id' => $outlet->id,
                'cash_session_id' => $session->id,
                'user_id' => $posUser->id,
                'sale_date' => now()->toDateString(),
                'sales_type' => 'regular',
                'subtotal' => 10000,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'total_amount' => 10000,
                'status' => 'completed',
            ]);
        }

        $response = $this->get(route('pos.dashboard'));

        $response->assertOk();
        $response->assertViewHas('todaySales', function ($todaySales) {
            return $todaySales instanceof LengthAwarePaginator
                && $todaySales->total() === 25
                && $todaySales->count() === 20;
        });
    }

    protected function createOutlet(string $name, string $code): Outlet
    {
        return Outlet::create([
            'name' => $name,
            'code' => $code,
            'is_active' => true,
        ]);
    }
}
