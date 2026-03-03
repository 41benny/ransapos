<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $newPermissions = [
            // Laporan Penjualan (Sales Reports)
            ['key' => 'reports.sales.view',      'module' => 'reports', 'action' => 'view', 'label' => 'Lihat Laporan Penjualan'],
            ['key' => 'reports.sales.export',    'module' => 'reports', 'action' => 'export', 'label' => 'Export Laporan Penjualan'],
            ['key' => 'reports.daily.view',      'module' => 'reports', 'action' => 'view', 'label' => 'Lihat Laporan Penjualan Harian'],
            ['key' => 'reports.daily.export',    'module' => 'reports', 'action' => 'export', 'label' => 'Export Laporan Penjualan Harian'],
            ['key' => 'reports.product.view',    'module' => 'reports', 'action' => 'view', 'label' => 'Lihat Laporan Per Produk'],
            ['key' => 'reports.product.export',  'module' => 'reports', 'action' => 'export', 'label' => 'Export Laporan Per Produk'],

            // Laporan Shift
            ['key' => 'reports.shift.view',      'module' => 'reports', 'action' => 'view', 'label' => 'Lihat Laporan Shift'],
            ['key' => 'reports.shift.export',    'module' => 'reports', 'action' => 'export', 'label' => 'Export Laporan Shift'],

            // Laporan Laba Rugi
            ['key' => 'reports.profit.view',     'module' => 'reports', 'action' => 'view', 'label' => 'Lihat Laporan Laba Rugi'],
            ['key' => 'reports.profit.export',   'module' => 'reports', 'action' => 'export', 'label' => 'Export Laporan Laba Rugi'],

            // Laporan Kehadiran
            ['key' => 'reports.attendance.view',   'module' => 'reports', 'action' => 'view', 'label' => 'Lihat Laporan Kehadiran'],
            ['key' => 'reports.attendance.export', 'module' => 'reports', 'action' => 'export', 'label' => 'Export Laporan Kehadiran'],

            // Laporan Hutang (Buku Hutang)
            ['key' => 'reports.debts.view',      'module' => 'reports', 'action' => 'view', 'label' => 'Lihat Buku Hutang Supplier'],
        ];

        $rows = array_map(fn($p) => array_merge($p, [
            'created_at' => $now,
            'updated_at' => $now,
        ]), $newPermissions);

        DB::table('permissions')->insertOrIgnore($rows);

        // Auto-assign semua permission baru ke role manager & admin
        $roleIds = DB::table('roles')
            ->whereIn('name', ['manager', 'admin', 'superadmin'])
            ->pluck('id');

        $newPermissionIds = DB::table('permissions')
            ->whereIn('key', collect($newPermissions)->pluck('key')->all())
            ->pluck('id');

        $pivotRows = [];
        foreach ($roleIds as $roleId) {
            foreach ($newPermissionIds as $permissionId) {
                $pivotRows[] = [
                    'role_id'       => $roleId,
                    'permission_id' => $permissionId,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            }
        }

        DB::table('role_permissions')->insertOrIgnore($pivotRows);
    }

    public function down(): void
    {
        $keys = [
            'reports.sales.view',   'reports.sales.export',
            'reports.daily.view',   'reports.daily.export',
            'reports.product.view', 'reports.product.export',
            'reports.shift.view',   'reports.shift.export',
            'reports.profit.view',  'reports.profit.export',
            'reports.attendance.view', 'reports.attendance.export',
            'reports.debts.view',
        ];

        $ids = DB::table('permissions')->whereIn('key', $keys)->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('key', $keys)->delete();
    }
};
