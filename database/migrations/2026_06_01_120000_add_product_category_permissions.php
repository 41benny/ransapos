<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $permissions = [
            ['key' => 'product-categories.view', 'module' => 'product-categories', 'action' => 'view', 'label' => 'Lihat Kategori Produk'],
            ['key' => 'product-categories.create', 'module' => 'product-categories', 'action' => 'create', 'label' => 'Tambah Kategori Produk'],
            ['key' => 'product-categories.update', 'module' => 'product-categories', 'action' => 'update', 'label' => 'Ubah Kategori Produk'],
            ['key' => 'product-categories.delete', 'module' => 'product-categories', 'action' => 'delete', 'label' => 'Hapus Kategori Produk'],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['key' => $permission['key']],
                array_merge($permission, ['updated_at' => $now, 'created_at' => $now])
            );
        }

        $roleIds = DB::table('roles')
            ->whereIn('name', ['manager', 'admin'])
            ->pluck('id');

        $permissionIds = DB::table('permissions')
            ->whereIn('key', array_column($permissions, 'key'))
            ->pluck('id');

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

        if ($pivotRows !== []) {
            DB::table('role_permissions')->insertOrIgnore($pivotRows);
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('key', [
                'product-categories.view',
                'product-categories.create',
                'product-categories.update',
                'product-categories.delete',
            ])
            ->pluck('id');

        if ($permissionIds->isNotEmpty()) {
            DB::table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('user_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }
    }
};
