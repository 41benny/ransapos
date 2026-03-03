<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add permission for POS Kasir Access
        $exists = DB::table('permissions')->where('key', 'pos.dashboard')->exists();
        if (!$exists) {
            $permissionId = DB::table('permissions')->insertGetId([
                'key'        => 'pos.dashboard',
                'module'     => 'pos-dashboard',
                'action'     => 'view',
                'label'      => 'Akses POS Kasir',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Assign to roles that normally need POS access
            $roles = DB::table('roles')->whereIn('name', ['kasir', 'admin', 'manager', 'superadmin'])->get();
            $pivotRows = [];
            foreach ($roles as $role) {
                $pivotRows[] = [
                    'role_id'       => $role->id,
                    'permission_id' => $permissionId,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ];
            }
            if (count($pivotRows) > 0) {
                DB::table('role_permissions')->insert($pivotRows);
            }
        }
    }

    public function down(): void
    {
        $perm = DB::table('permissions')->where('key', 'pos.dashboard')->first();
        if ($perm) {
            DB::table('role_permissions')->where('permission_id', $perm->id)->delete();
            DB::table('user_permissions')->where('permission_id', $perm->id)->delete();
            DB::table('permissions')->where('id', $perm->id)->delete();
        }
    }
};
