<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Outlet;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceShiftReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::query()->where('name', 'superadmin')->firstOrFail();

        $this->admin = User::factory()->create([
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin);
    }

    public function test_report_aggregates_work_hours_overtime_and_late_minutes(): void
    {
        $outlet = Outlet::create([
            'name' => 'Outlet Shift',
            'code' => 'OUT-SHIFT',
            'is_active' => true,
        ]);

        $shift = Shift::create([
            'name' => 'Pagi',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'late_tolerance_minutes' => 10,
            'is_overnight' => false,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $employee = User::factory()->create([
            'role_id' => $this->admin->role_id,
            'outlet_id' => $outlet->id,
            'is_active' => true,
        ]);

        $clockIn = now()->startOfDay()->setTime(8, 0);

        // Record 1: tepat waktu, kerja 8 jam (480 menit), tanpa lembur.
        Attendance::create([
            'user_id' => $employee->id,
            'logged_in_user_id' => $this->admin->id,
            'outlet_id' => $outlet->id,
            'shift_id' => $shift->id,
            'clock_in' => $clockIn,
            'clock_out' => (clone $clockIn)->addHours(8),
            'status' => 'present',
            'late_minutes' => 0,
            'overtime_minutes' => 0,
            'early_leave_minutes' => 0,
            'worked_minutes' => 480,
            'ip_address' => '127.0.0.1',
        ]);

        // Record 2: telat 20 menit, lembur 30 menit, kerja 490 menit.
        $clockIn2 = now()->startOfDay()->subDay()->setTime(8, 20);
        Attendance::create([
            'user_id' => $employee->id,
            'logged_in_user_id' => $this->admin->id,
            'outlet_id' => $outlet->id,
            'shift_id' => $shift->id,
            'clock_in' => $clockIn2,
            'clock_out' => (clone $clockIn2)->addMinutes(490),
            'status' => 'late',
            'late_minutes' => 20,
            'overtime_minutes' => 30,
            'early_leave_minutes' => 0,
            'worked_minutes' => 490,
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this->get(route('admin.reports.attendance.index', [
            'period' => 'custom',
            'date_from' => now()->subDays(3)->toDateString(),
            'date_to' => now()->toDateString(),
            // Filter outlet memicu join ke tabel users — pastikan tidak ada
            // ambiguous column 'outlet_id'.
            'outlet_id' => $outlet->id,
        ]));

        $response->assertOk();
        $response->assertViewHas('totalWorkedMinutes', 970);
        $response->assertViewHas('totalOvertimeMinutes', 30);
        $response->assertViewHas('totalLateMinutes', 20);
        $response->assertViewHas('totalLate', 1);

        // Ranking kedisiplinan tersedia
        $response->assertViewHas('disciplineTop', function ($top) use ($employee) {
            return $top->isNotEmpty() && $top->first()['employee_name'] === $employee->name;
        });

        // Per-karyawan menyertakan agregat jam kerja & lembur
        $response->assertViewHas('employeeStats', function ($stats) {
            $row = $stats->getCollection()->first();
            return $row['worked_minutes'] === 970 && $row['overtime_minutes'] === 30;
        });
    }
}
