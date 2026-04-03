<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Outlet;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class AttendanceReportPaginationTest extends TestCase
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

    public function test_attendance_report_paginates_detail_rows(): void
    {
        $outlet = Outlet::create([
            'name' => 'Outlet Absensi',
            'code' => 'OUT-ATT',
            'is_active' => true,
        ]);

        $employee = User::factory()->create([
            'role_id' => $this->admin->role_id,
            'outlet_id' => $outlet->id,
            'is_active' => true,
        ]);

        for ($i = 1; $i <= 105; $i++) {
            $clockIn = now()->startOfDay()->subDays(110 - $i)->setTime(8, 0);

            Attendance::create([
                'user_id' => $employee->id,
                'logged_in_user_id' => $this->admin->id,
                'outlet_id' => $outlet->id,
                'clock_in' => $clockIn,
                'clock_out' => (clone $clockIn)->addHours(8),
                'status' => $i % 3 === 0 ? 'late' : 'present',
                'ip_address' => '127.0.0.1',
            ]);
        }

        $response = $this->get(route('admin.reports.attendance.index', [
            'period' => 'custom',
            'date_from' => now()->subDays(120)->toDateString(),
            'date_to' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSeeText('Menampilkan 1-100 dari 105 baris');
        $response->assertViewHas('attendances', function ($attendances) {
            return $attendances instanceof LengthAwarePaginator
                && $attendances->total() === 105
                && $attendances->count() === 100
                && $attendances->currentPage() === 1;
        });
    }

    public function test_attendance_report_paginates_outlet_and_employee_stats(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            $outlet = Outlet::create([
                'name' => 'Outlet ' . $i,
                'code' => 'OUT-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'is_active' => true,
            ]);

            $employee = User::factory()->create([
                'role_id' => $this->admin->role_id,
                'outlet_id' => $outlet->id,
                'is_active' => true,
            ]);

            $clockIn = now()->startOfDay()->subDays($i)->setTime(8, 0);

            Attendance::create([
                'user_id' => $employee->id,
                'logged_in_user_id' => $this->admin->id,
                'outlet_id' => $outlet->id,
                'clock_in' => $clockIn,
                'clock_out' => (clone $clockIn)->addHours(8),
                'status' => $i % 2 === 0 ? 'late' : 'present',
                'ip_address' => '127.0.0.1',
            ]);
        }

        $response = $this->get(route('admin.reports.attendance.index', [
            'period' => 'custom',
            'date_from' => now()->subDays(30)->toDateString(),
            'date_to' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSeeText('Menampilkan 1-20 dari 25 outlet');
        $response->assertSeeText('Menampilkan 1-20 dari 25 karyawan');
        $response->assertViewHas('outletStats', function ($outletStats) {
            return $outletStats instanceof LengthAwarePaginator
                && $outletStats->total() === 25
                && $outletStats->count() === 20;
        });
        $response->assertViewHas('employeeStats', function ($employeeStats) {
            return $employeeStats instanceof LengthAwarePaginator
                && $employeeStats->total() === 25
                && $employeeStats->count() === 20;
        });
    }
}
