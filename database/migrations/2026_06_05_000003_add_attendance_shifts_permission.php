<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $newPermissions = [
            ['key' => 'attendance.shifts.manage', 'module' => 'attendance', 'action' => 'manage', 'label' => 'Kelola Master Shift Absensi'],
        ];

        $rows = array_map(fn ($p) => array_merge($p, [
            'created_at' => $now,
            'updated_at' => $now,
        ]), $newPermissions);

        DB::table('permissions')->insertOrIgnore($rows);

        // Auto-assign permission baru ke role manager, admin, superadmin.
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

        if (! empty($pivotRows)) {
            DB::table('role_permissions')->insertOrIgnore($pivotRows);
        }
    }

    public function down(): void
    {
        $keys = ['attendance.shifts.manage'];

        $ids = DB::table('permissions')->whereIn('key', $keys)->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('key', $keys)->delete();
    }
};
