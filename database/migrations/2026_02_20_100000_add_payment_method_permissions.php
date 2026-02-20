<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();
        $permissionDefinitions = [
            ['key' => 'payment-methods.view', 'module' => 'payment-methods', 'action' => 'view', 'label' => 'Lihat Metode Pembayaran'],
            ['key' => 'payment-methods.create', 'module' => 'payment-methods', 'action' => 'create', 'label' => 'Tambah Metode Pembayaran'],
            ['key' => 'payment-methods.update', 'module' => 'payment-methods', 'action' => 'update', 'label' => 'Ubah Metode Pembayaran'],
            ['key' => 'payment-methods.delete', 'module' => 'payment-methods', 'action' => 'delete', 'label' => 'Hapus Metode Pembayaran'],
        ];

        $permissions = array_map(function (array $permission) use ($now): array {
            return array_merge($permission, [
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }, $permissionDefinitions);

        DB::table('permissions')->upsert(
            $permissions,
            ['key'],
            ['module', 'action', 'label', 'updated_at']
        );

        $roleIds = DB::table('roles')
            ->whereIn('name', ['admin', 'manager'])
            ->pluck('id');

        if ($roleIds->isEmpty()) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('key', array_column($permissionDefinitions, 'key'))
            ->pluck('id');

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
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissionKeys = [
            'payment-methods.view',
            'payment-methods.create',
            'payment-methods.update',
            'payment-methods.delete',
        ];

        $permissionIds = DB::table('permissions')
            ->whereIn('key', $permissionKeys)
            ->pluck('id');

        if ($permissionIds->isNotEmpty()) {
            DB::table('role_permissions')
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }

        DB::table('permissions')
            ->whereIn('key', $permissionKeys)
            ->delete();
    }
};
