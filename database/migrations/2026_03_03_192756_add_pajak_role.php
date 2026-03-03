<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Buat role "pajak" jika belum ada
        $exists = DB::table('roles')->where('name', 'pajak')->exists();
        if (!$exists) {
            DB::table('roles')->insert([
                'name'         => 'pajak',
                'display_name' => 'Dinas Pajak',
                'description'  => 'Akses terbatas untuk petugas Dinas Pendapatan (eksternal). Hanya dapat melihat Laporan Penjualan Harian.',
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        // 2. Ambil ID role pajak
        $roleId = DB::table('roles')->where('name', 'pajak')->value('id');
        if (!$roleId) return;

        // 3. Pastikan permission reports.view & reports.daily.view ada, lalu assign ke role pajak
        // reports.view   → bisa lihat katalog laporan (halaman index reports)
        // reports.daily.view → bisa lihat halaman laporan penjualan harian
        $permissionKeys = [
            'reports.view',       // akses masuk ke katalog laporan
            'reports.daily.view', // lihat laporan penjualan harian
        ];

        $permissionIds = DB::table('permissions')
            ->whereIn('key', $permissionKeys)
            ->pluck('id');

        $now = now();
        $pivotRows = [];
        foreach ($permissionIds as $permissionId) {
            $alreadyExists = DB::table('role_permissions')
                ->where('role_id', $roleId)
                ->where('permission_id', $permissionId)
                ->exists();
            if (!$alreadyExists) {
                $pivotRows[] = [
                    'role_id'       => $roleId,
                    'permission_id' => $permissionId,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            }
        }

        if (!empty($pivotRows)) {
            DB::table('role_permissions')->insert($pivotRows);
        }
    }

    public function down(): void
    {
        $roleId = DB::table('roles')->where('name', 'pajak')->value('id');
        if ($roleId) {
            DB::table('role_permissions')->where('role_id', $roleId)->delete();
        }
        DB::table('roles')->where('name', 'pajak')->delete();
    }
};
