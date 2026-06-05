<?php

namespace Database\Seeders;

use App\Models\Shift;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    /**
     * Seed contoh shift default agar fitur absensi langsung bisa dipakai.
     */
    public function run(): void
    {
        $shifts = [
            ['name' => 'Pagi', 'start_time' => '08:00', 'end_time' => '16:00', 'late_tolerance_minutes' => 10, 'is_overnight' => false, 'sort_order' => 1],
            ['name' => 'Sore', 'start_time' => '14:00', 'end_time' => '22:00', 'late_tolerance_minutes' => 10, 'is_overnight' => false, 'sort_order' => 2],
        ];

        foreach ($shifts as $shift) {
            Shift::firstOrCreate(
                ['name' => $shift['name']],
                array_merge($shift, ['is_active' => true])
            );
        }
    }
}
