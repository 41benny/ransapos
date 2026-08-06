<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Batasi shift agar hanya muncul di hari yang relevan.
 *
 * Ada 4 shift ("Weekdays", "Weekend", "Shift 2 weekdays", "Shift 2 weekend")
 * yang cuma beda jam, dan semuanya tampil bersamaan di dropdown clock-in
 * kapan pun — termasuk shift "Weekend" di hari Selasa. Nama yang mirip
 * membuat karyawan salah pilih.
 *
 * Perbaikan sebelumnya (menampilkan jam di label) mengurangi kebingungan
 * tapi tidak menghilangkan pilihan yang jelas-jelas tidak relevan. Ini
 * perbaikan yang lebih kuat: pilihan yang tidak berlaku hari itu tidak
 * ditampilkan sama sekali, bukan sekadar dilabeli lebih jelas.
 *
 * Default 'all' untuk shift yang sudah ada, supaya tidak ada shift yang
 * tiba-tiba hilang dari pilihan karyawan begitu migrasi ini jalan — admin
 * yang menentukan sendiri shift mana yang perlu dibatasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            if (! Schema::hasColumn('shifts', 'day_scope')) {
                $table->enum('day_scope', ['all', 'weekday', 'weekend'])
                    ->default('all')
                    ->after('is_overnight')
                    ->comment('all = setiap hari, weekday = Senin-Jumat, weekend = Sabtu-Minggu');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            if (Schema::hasColumn('shifts', 'day_scope')) {
                $table->dropColumn('day_scope');
            }
        });
    }
};
