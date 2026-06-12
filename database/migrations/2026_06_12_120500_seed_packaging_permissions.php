<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $permissions = [
            ['key' => 'packaging-items.view', 'module' => 'packaging-items', 'action' => 'view', 'label' => 'Lihat Item Packaging'],
            ['key' => 'packaging-items.create', 'module' => 'packaging-items', 'action' => 'create', 'label' => 'Tambah Item Packaging'],
            ['key' => 'packaging-items.update', 'module' => 'packaging-items', 'action' => 'update', 'label' => 'Ubah Item Packaging'],

            ['key' => 'packaging-mappings.view', 'module' => 'packaging-mappings', 'action' => 'view', 'label' => 'Lihat Mapping Packaging'],
            ['key' => 'packaging-mappings.create', 'module' => 'packaging-mappings', 'action' => 'create', 'label' => 'Tambah Mapping Packaging'],
            ['key' => 'packaging-mappings.update', 'module' => 'packaging-mappings', 'action' => 'update', 'label' => 'Ubah Mapping Packaging'],

            ['key' => 'packaging-adjustments.create', 'module' => 'packaging-adjustments', 'action' => 'create', 'label' => 'Buat Adjustment Packaging'],
            ['key' => 'packaging-adjustments.view', 'module' => 'packaging-adjustments', 'action' => 'view', 'label' => 'Lihat Adjustment Packaging'],
            ['key' => 'packaging-adjustments.approve', 'module' => 'packaging-adjustments', 'action' => 'approve', 'label' => 'Approve Adjustment Packaging'],
            ['key' => 'packaging-adjustments.reject', 'module' => 'packaging-adjustments', 'action' => 'reject', 'label' => 'Reject Adjustment Packaging'],

            ['key' => 'packaging-reports.view', 'module' => 'packaging-reports', 'action' => 'view', 'label' => 'Lihat Laporan Packaging'],
        ];

        $rows = array_map(fn ($p) => array_merge($p, [
            'created_at' => $now,
            'updated_at' => $now,
        ]), $permissions);

        DB::table('permissions')->insertOrIgnore($rows);

        // Backoffice/owner roles: full access.
        $backofficeRoleIds = DB::table('roles')
            ->whereIn('name', ['manager', 'admin', 'superadmin'])
            ->pluck('id');

        $allPermissionIds = DB::table('permissions')
            ->whereIn('key', collect($permissions)->pluck('key')->all())
            ->pluck('id');

        $this->assign($backofficeRoleIds, $allPermissionIds, $now);

        // Kasir: hanya boleh membuat & melihat adjustment.
        $kasirRoleIds = DB::table('roles')->whereIn('name', ['kasir'])->pluck('id');
        $kasirPermissionIds = DB::table('permissions')
            ->whereIn('key', ['packaging-adjustments.create', 'packaging-adjustments.view'])
            ->pluck('id');

        $this->assign($kasirRoleIds, $kasirPermissionIds, $now);
    }

    private function assign($roleIds, $permissionIds, $now): void
    {
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

        if (! empty($pivotRows)) {
            DB::table('role_permissions')->insertOrIgnore($pivotRows);
        }
    }

    public function down(): void
    {
        $keys = [
            'packaging-items.view', 'packaging-items.create', 'packaging-items.update',
            'packaging-mappings.view', 'packaging-mappings.create', 'packaging-mappings.update',
            'packaging-adjustments.create', 'packaging-adjustments.view',
            'packaging-adjustments.approve', 'packaging-adjustments.reject',
            'packaging-reports.view',
        ];

        $ids = DB::table('permissions')->whereIn('key', $keys)->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('key', $keys)->delete();
    }
};
