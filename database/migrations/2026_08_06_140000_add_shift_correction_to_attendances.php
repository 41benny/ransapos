<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Koreksi shift absensi oleh admin, terbatas pada hari yang sama.
 *
 * Kasus nyata: karyawan memilih shift yang salah saat clock-in (dua shift
 * bernama sama "Weekdays", satu mulai 10:30 satu 11:30), sehingga tercatat
 * terlambat 37 menit padahal tepat waktu. Sebelum ini tidak ada cara apa pun
 * memperbaikinya selain menyentuh database produksi langsung — laporan absensi
 * sepenuhnya read-only.
 *
 * `late_minutes` dan `status` adalah kolom TERSIMPAN, dihitung sekali saat
 * clock-in. Jadi koreksi tidak cukup mengganti `shift_id`: seluruh metrik
 * turunannya harus dihitung ulang, kalau tidak layar tetap menampilkan angka
 * lama selamanya.
 *
 * Kolom jejak audit wajib ada karena angka ini dipakai untuk penilaian
 * kedisiplinan: siapa pun yang melihat "tepat waktu" harus bisa tahu apakah
 * itu hasil absen asli atau hasil koreksi, oleh siapa, dan atas alasan apa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('attendances', 'original_shift_id')) {
                $table->unsignedBigInteger('original_shift_id')->nullable()->after('shift_id')
                    ->comment('Shift yang dipilih karyawan sebelum dikoreksi admin');
            }
            if (! Schema::hasColumn('attendances', 'shift_corrected_by')) {
                $table->unsignedBigInteger('shift_corrected_by')->nullable()->after('original_shift_id');
            }
            if (! Schema::hasColumn('attendances', 'shift_corrected_at')) {
                $table->timestamp('shift_corrected_at')->nullable()->after('shift_corrected_by');
            }
            if (! Schema::hasColumn('attendances', 'shift_correction_reason')) {
                $table->string('shift_correction_reason', 200)->nullable()->after('shift_corrected_at');
            }
        });

        DB::table('permissions')->updateOrInsert(
            ['key' => 'attendance.correct_shift'],
            [
                'module' => 'attendance',
                'action' => 'correct_shift',
                'label' => 'Koreksi Shift Absensi (Hari Berjalan)',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Kasir sengaja TIDAK diberi izin. Kasir adalah pihak yang menginput
        // absensi rekannya; memberi mereka kuasa mengoreksi status keterlambatan
        // sendiri membuat catatan disiplin tidak ada artinya.
        $roleIds = DB::table('roles')->whereIn('name', ['admin', 'manager', 'superadmin'])->pluck('id');
        $permissionId = DB::table('permissions')->where('key', 'attendance.correct_shift')->value('id');

        if ($permissionId && $roleIds->isNotEmpty()) {
            foreach ($roleIds as $roleId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            foreach (['original_shift_id', 'shift_corrected_by', 'shift_corrected_at', 'shift_correction_reason'] as $column) {
                if (Schema::hasColumn('attendances', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        $permissionId = DB::table('permissions')->where('key', 'attendance.correct_shift')->value('id');
        if ($permissionId) {
            DB::table('role_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('user_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
    }
};
