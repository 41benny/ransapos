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
        $exists = DB::table('roles')->where('name', 'karyawan_outlet')->exists();

        if (!$exists) {
            DB::table('roles')->insert([
                'name' => 'karyawan_outlet',
                'display_name' => 'Karyawan Outlet',
                'description' => 'Karyawan outlet untuk absensi tanpa akses login',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('roles')->where('name', 'karyawan_outlet')->delete();
    }
};

