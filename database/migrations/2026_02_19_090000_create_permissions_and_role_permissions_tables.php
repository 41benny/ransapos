<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('key', 150)->unique();
            $table->string('module', 100);
            $table->string('action', 100);
            $table->string('label', 200);
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['role_id', 'permission_id']);
        });

        $this->seedDefaultPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
    }

    /**
     * Seed permission baseline untuk fase back office.
     */
    private function seedDefaultPermissions(): void
    {
        $now = now();
        $permissions = array_map(function (array $permission) use ($now): array {
            return array_merge($permission, [
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }, $this->defaultPermissions());

        DB::table('permissions')->insertOrIgnore($permissions);

        $roleIds = DB::table('roles')
            ->whereIn('name', ['manager', 'admin'])
            ->pluck('id');

        if ($roleIds->isEmpty()) {
            return;
        }

        $permissionIds = DB::table('permissions')->pluck('id');
        if ($permissionIds->isEmpty()) {
            return;
        }

        $pivotRows = [];
        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                $pivotRows[] = [
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($pivotRows, 1000) as $chunk) {
            DB::table('role_permissions')->insertOrIgnore($chunk);
        }
    }

    /**
     * Daftar permission default untuk menu back office.
     *
     * @return array<int, array<string, string>>
     */
    private function defaultPermissions(): array
    {
        return [
            ['key' => 'dashboard.view', 'module' => 'dashboard', 'action' => 'view', 'label' => 'Lihat Dashboard'],

            ['key' => 'products.view', 'module' => 'products', 'action' => 'view', 'label' => 'Lihat Produk'],
            ['key' => 'products.create', 'module' => 'products', 'action' => 'create', 'label' => 'Tambah Produk'],
            ['key' => 'products.update', 'module' => 'products', 'action' => 'update', 'label' => 'Ubah Produk'],
            ['key' => 'products.delete', 'module' => 'products', 'action' => 'delete', 'label' => 'Hapus Produk'],
            ['key' => 'products.import', 'module' => 'products', 'action' => 'import', 'label' => 'Import Produk'],

            ['key' => 'outlets.view', 'module' => 'outlets', 'action' => 'view', 'label' => 'Lihat Outlet'],
            ['key' => 'outlets.create', 'module' => 'outlets', 'action' => 'create', 'label' => 'Tambah Outlet'],
            ['key' => 'outlets.update', 'module' => 'outlets', 'action' => 'update', 'label' => 'Ubah Outlet'],

            ['key' => 'suppliers.view', 'module' => 'suppliers', 'action' => 'view', 'label' => 'Lihat Supplier'],
            ['key' => 'suppliers.create', 'module' => 'suppliers', 'action' => 'create', 'label' => 'Tambah Supplier'],

            ['key' => 'customers.view', 'module' => 'customers', 'action' => 'view', 'label' => 'Lihat Customer'],
            ['key' => 'customers.create', 'module' => 'customers', 'action' => 'create', 'label' => 'Tambah Customer'],
            ['key' => 'customers.update', 'module' => 'customers', 'action' => 'update', 'label' => 'Ubah Customer'],
            ['key' => 'customers.delete', 'module' => 'customers', 'action' => 'delete', 'label' => 'Hapus Customer'],
            ['key' => 'customers.points.adjust', 'module' => 'customers', 'action' => 'adjust', 'label' => 'Kelola Poin Customer'],
            ['key' => 'customers.report.view', 'module' => 'customers', 'action' => 'report', 'label' => 'Lihat Laporan Customer'],

            ['key' => 'users.view', 'module' => 'users', 'action' => 'view', 'label' => 'Lihat User'],
            ['key' => 'users.create', 'module' => 'users', 'action' => 'create', 'label' => 'Tambah User'],
            ['key' => 'users.update', 'module' => 'users', 'action' => 'update', 'label' => 'Ubah User'],
            ['key' => 'users.delete', 'module' => 'users', 'action' => 'delete', 'label' => 'Nonaktifkan User'],
            ['key' => 'users.pin.manage', 'module' => 'users', 'action' => 'manage', 'label' => 'Atur PIN User'],

            ['key' => 'cash-accounts.view', 'module' => 'cash-accounts', 'action' => 'view', 'label' => 'Lihat Kas & Bank'],
            ['key' => 'cash-accounts.create', 'module' => 'cash-accounts', 'action' => 'create', 'label' => 'Tambah Kas & Bank'],
            ['key' => 'cash-accounts.update', 'module' => 'cash-accounts', 'action' => 'update', 'label' => 'Ubah Kas & Bank'],
            ['key' => 'cash-accounts.delete', 'module' => 'cash-accounts', 'action' => 'delete', 'label' => 'Hapus Kas & Bank'],
            ['key' => 'cash-accounts.mutation-report.view', 'module' => 'cash-accounts', 'action' => 'report', 'label' => 'Lihat Mutasi Kas'],

            ['key' => 'cash-transactions.view', 'module' => 'cash-transactions', 'action' => 'view', 'label' => 'Lihat Transaksi Kas'],
            ['key' => 'cash-transactions.create', 'module' => 'cash-transactions', 'action' => 'create', 'label' => 'Tambah Transaksi Kas'],
            ['key' => 'cash-transactions.update', 'module' => 'cash-transactions', 'action' => 'update', 'label' => 'Ubah Transaksi Kas'],
            ['key' => 'cash-transactions.delete', 'module' => 'cash-transactions', 'action' => 'delete', 'label' => 'Hapus Transaksi Kas'],
            ['key' => 'cash-transactions.print', 'module' => 'cash-transactions', 'action' => 'print', 'label' => 'Print Transaksi Kas'],

            ['key' => 'bank-transfers.view', 'module' => 'bank-transfers', 'action' => 'view', 'label' => 'Lihat Transfer Bank'],
            ['key' => 'bank-transfers.create', 'module' => 'bank-transfers', 'action' => 'create', 'label' => 'Buat Transfer Bank'],

            ['key' => 'coa-accounts.view', 'module' => 'coa-accounts', 'action' => 'view', 'label' => 'Lihat COA'],
            ['key' => 'coa-accounts.create', 'module' => 'coa-accounts', 'action' => 'create', 'label' => 'Tambah COA'],
            ['key' => 'coa-accounts.update', 'module' => 'coa-accounts', 'action' => 'update', 'label' => 'Ubah COA'],
            ['key' => 'coa-accounts.delete', 'module' => 'coa-accounts', 'action' => 'delete', 'label' => 'Hapus COA'],
            ['key' => 'coa-accounts.balance-template.generate', 'module' => 'coa-accounts', 'action' => 'generate', 'label' => 'Generate Template Saldo COA'],

            ['key' => 'stocks.view', 'module' => 'stocks', 'action' => 'view', 'label' => 'Lihat Stok'],
            ['key' => 'stocks.adjust', 'module' => 'stocks', 'action' => 'adjust', 'label' => 'Adjustment Stok'],
            ['key' => 'stocks.export', 'module' => 'stocks', 'action' => 'export', 'label' => 'Export Stok'],

            ['key' => 'stock-transfers.view', 'module' => 'stock-transfers', 'action' => 'view', 'label' => 'Lihat Transfer Stok'],
            ['key' => 'stock-transfers.create', 'module' => 'stock-transfers', 'action' => 'create', 'label' => 'Buat Transfer Stok'],
            ['key' => 'stock-transfers.update', 'module' => 'stock-transfers', 'action' => 'update', 'label' => 'Proses Transfer Stok'],
            ['key' => 'stock-transfers.cancel', 'module' => 'stock-transfers', 'action' => 'cancel', 'label' => 'Batalkan Transfer Stok'],

            ['key' => 'boms.view', 'module' => 'boms', 'action' => 'view', 'label' => 'Lihat BOM'],
            ['key' => 'boms.create', 'module' => 'boms', 'action' => 'create', 'label' => 'Tambah BOM'],
            ['key' => 'boms.update', 'module' => 'boms', 'action' => 'update', 'label' => 'Ubah BOM'],
            ['key' => 'boms.delete', 'module' => 'boms', 'action' => 'delete', 'label' => 'Hapus BOM'],

            ['key' => 'purchases.view', 'module' => 'purchases', 'action' => 'view', 'label' => 'Lihat Purchase'],
            ['key' => 'purchases.create', 'module' => 'purchases', 'action' => 'create', 'label' => 'Buat Purchase'],
            ['key' => 'purchases.update', 'module' => 'purchases', 'action' => 'update', 'label' => 'Ubah Purchase'],
            ['key' => 'purchases.delete', 'module' => 'purchases', 'action' => 'delete', 'label' => 'Hapus Purchase'],
            ['key' => 'purchases.receive', 'module' => 'purchases', 'action' => 'receive', 'label' => 'Terima Purchase'],
            ['key' => 'purchases.cancel', 'module' => 'purchases', 'action' => 'cancel', 'label' => 'Batalkan Purchase'],
            ['key' => 'purchases.payment', 'module' => 'purchases', 'action' => 'payment', 'label' => 'Pembayaran Purchase'],
            ['key' => 'purchases.print', 'module' => 'purchases', 'action' => 'print', 'label' => 'Print Purchase'],

            ['key' => 'expense-categories.view', 'module' => 'expense-categories', 'action' => 'view', 'label' => 'Lihat Kategori Biaya'],
            ['key' => 'expense-categories.create', 'module' => 'expense-categories', 'action' => 'create', 'label' => 'Tambah Kategori Biaya'],
            ['key' => 'expense-categories.update', 'module' => 'expense-categories', 'action' => 'update', 'label' => 'Ubah Kategori Biaya'],
            ['key' => 'expense-categories.delete', 'module' => 'expense-categories', 'action' => 'delete', 'label' => 'Hapus Kategori Biaya'],

            ['key' => 'expenses.view', 'module' => 'expenses', 'action' => 'view', 'label' => 'Lihat Expense'],
            ['key' => 'expenses.create', 'module' => 'expenses', 'action' => 'create', 'label' => 'Tambah Expense'],
            ['key' => 'expenses.update', 'module' => 'expenses', 'action' => 'update', 'label' => 'Ubah Expense'],
            ['key' => 'expenses.delete', 'module' => 'expenses', 'action' => 'delete', 'label' => 'Hapus Expense'],
            ['key' => 'expenses.approve', 'module' => 'expenses', 'action' => 'approve', 'label' => 'Approve Expense'],
            ['key' => 'expenses.reject', 'module' => 'expenses', 'action' => 'reject', 'label' => 'Reject Expense'],
            ['key' => 'expenses.pay', 'module' => 'expenses', 'action' => 'pay', 'label' => 'Bayar Expense'],
            ['key' => 'expenses.report.view', 'module' => 'expenses', 'action' => 'report', 'label' => 'Lihat Laporan Expense'],

            ['key' => 'promo-vouchers.view', 'module' => 'promo-vouchers', 'action' => 'view', 'label' => 'Lihat Promo & Voucher'],
            ['key' => 'promo-vouchers.manage', 'module' => 'promo-vouchers', 'action' => 'manage', 'label' => 'Kelola Promo & Voucher'],

            ['key' => 'reports.view', 'module' => 'reports', 'action' => 'view', 'label' => 'Lihat Laporan'],
            ['key' => 'reports.export', 'module' => 'reports', 'action' => 'export', 'label' => 'Export Laporan'],

            ['key' => 'cash-sessions.view', 'module' => 'cash-sessions', 'action' => 'view', 'label' => 'Lihat Riwayat Cash Session'],

            ['key' => 'pos-devices.view', 'module' => 'pos-devices', 'action' => 'view', 'label' => 'Lihat Perangkat POS'],
            ['key' => 'pos-devices.manage', 'module' => 'pos-devices', 'action' => 'manage', 'label' => 'Kelola Perangkat POS'],

            ['key' => 'void-tokens.view', 'module' => 'void-tokens', 'action' => 'view', 'label' => 'Lihat Token Void'],
            ['key' => 'void-tokens.create', 'module' => 'void-tokens', 'action' => 'create', 'label' => 'Buat Token Void'],

            ['key' => 'permissions.manage', 'module' => 'permissions', 'action' => 'manage', 'label' => 'Kelola Role Permission'],
        ];
    }
};
