<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * `outlet_id` dipakai sebagai outlet tempat absensi benar-benar terjadi
     * (outlet fisik perangkat POS), sedangkan `home_outlet_id` menyimpan outlet
     * induk karyawan pada saat absen. Dua kolom ini memisahkan "kerja di mana"
     * dari "milik outlet mana" supaya rekap payroll tidak salah alokasi.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('home_outlet_id')->nullable()->after('outlet_id')
                ->constrained('outlets')->nullOnDelete();
            $table->boolean('is_cross_outlet')->default(false)->after('home_outlet_id');

            $table->index(['is_cross_outlet', 'clock_in']);
        });

        // Data lama selalu tercatat di outlet induk karyawan, jadi bukan lintas outlet.
        DB::table('attendances')->update([
            'home_outlet_id' => DB::raw('outlet_id'),
            'is_cross_outlet' => false,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex(['is_cross_outlet', 'clock_in']);
            $table->dropConstrainedForeignId('home_outlet_id');
            $table->dropColumn('is_cross_outlet');
        });
    }
};
