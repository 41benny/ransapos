<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Nominal lama tetap disimpan untuk rekap sebelum Oktober 2026.
        DB::table('settings')->insertOrIgnore([
            'key' => 'payroll_period2_allowance_schedule',
            'value' => json_encode(['2026-10-01' => 310000]),
        ]);

        Cache::forget('settings:payroll_period2_allowance_schedule');
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'payroll_period2_allowance_schedule')->delete();
        Cache::forget('settings:payroll_period2_allowance_schedule');
    }
};
