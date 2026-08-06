<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tarif gaji harian & tunjangan periode kedua untuk tabel gaji di halaman
 * Rekap Absensi. Disimpan di `settings` (bukan kolom per user) karena saat
 * ini seragam untuk semua karyawan.
 *
 * Tidak ada permission baru: tabel gaji menumpang halaman Rekap Absensi yang
 * sudah dikunci ke admin/manager/superadmin lewat `reports.attendance.view`
 * (kasir tidak pernah bisa membuka halaman itu sama sekali).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->updateOrInsert(['key' => 'payroll_daily_wage'], ['value' => '50000']);
        DB::table('settings')->updateOrInsert(['key' => 'payroll_period2_allowance'], ['value' => '250000']);
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', ['payroll_daily_wage', 'payroll_period2_allowance'])->delete();
    }
};
