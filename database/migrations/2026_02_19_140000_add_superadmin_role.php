<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('roles')->updateOrInsert(
            ['name' => 'superadmin'],
            [
                'display_name' => 'Super Admin',
                'description' => 'Akses penuh bawaan sistem',
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        if (!Schema::hasTable('permissions') || !Schema::hasTable('role_permissions')) {
            return;
        }

        $superadminRoleId = DB::table('roles')->where('name', 'superadmin')->value('id');
        if (!$superadminRoleId) {
            return;
        }

        $permissionIds = DB::table('permissions')->pluck('id');
        if ($permissionIds->isEmpty()) {
            return;
        }

        $rows = [];
        foreach ($permissionIds as $permissionId) {
            $rows[] = [
                'role_id' => $superadminRoleId,
                'permission_id' => $permissionId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 1000) as $chunk) {
            DB::table('role_permissions')->insertOrIgnore($chunk);
        }
    }

    public function down(): void
    {
        $superadminRoleId = DB::table('roles')->where('name', 'superadmin')->value('id');
        if ($superadminRoleId && Schema::hasTable('role_permissions')) {
            DB::table('role_permissions')->where('role_id', $superadminRoleId)->delete();
        }

        DB::table('roles')->where('name', 'superadmin')->delete();
    }
};
