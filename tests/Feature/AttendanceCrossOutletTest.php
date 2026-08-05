<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Outlet;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceCrossOutletTest extends TestCase
{
    use RefreshDatabase;

    protected Outlet $outletA;
    protected Outlet $outletB;
    protected Shift $shift;
    protected User $kasirB;
    protected User $rollingEmployee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outletA = Outlet::create([
            'name' => 'Outlet A',
            'code' => 'OUT-A',
            'is_active' => true,
        ]);

        $this->outletB = Outlet::create([
            'name' => 'Outlet B',
            'code' => 'OUT-B',
            'is_active' => true,
        ]);

        $this->shift = Shift::create([
            'name' => 'Pagi',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'late_tolerance_minutes' => 10,
            'is_overnight' => false,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Role kasir hanya dibuat lewat seeder, bukan migration.
        $kasirRole = Role::query()->firstOrCreate(
            ['name' => 'kasir'],
            ['display_name' => 'Kasir', 'description' => 'POS operator']
        );

        // Kasir yang login di perangkat POS outlet B
        $this->kasirB = User::factory()->create([
            'role_id' => $kasirRole->id,
            'outlet_id' => $this->outletB->id,
            'is_active' => true,
            'attendance_pin' => Hash::make('111111'),
        ]);

        // Karyawan induk outlet A yang hari ini bertugas di outlet B
        $this->rollingEmployee = User::factory()->create([
            'role_id' => $kasirRole->id,
            'outlet_id' => $this->outletA->id,
            'is_active' => true,
            'attendance_pin' => Hash::make('123456'),
        ]);

        $this->actingAs($this->kasirB);
    }

    public function test_clock_in_lintas_outlet_tercatat_di_outlet_tempat_absen(): void
    {
        $response = $this->post(route('pos.attendance.clock-in'), [
            'employee_id' => $this->rollingEmployee->id,
            'pin' => '123456',
            'shift_id' => $this->shift->id,
        ]);

        $response->assertSessionHasNoErrors();

        $attendance = Attendance::where('user_id', $this->rollingEmployee->id)->firstOrFail();

        // Outlet kerja = outlet perangkat POS (B), bukan outlet induk karyawan (A)
        $this->assertSame($this->outletB->id, $attendance->outlet_id);
        $this->assertSame($this->outletA->id, $attendance->home_outlet_id);
        $this->assertTrue($attendance->isCrossOutlet());
        $this->assertSame($this->kasirB->id, $attendance->logged_in_user_id);
    }

    public function test_absensi_outlet_sendiri_tidak_ditandai_lintas_outlet(): void
    {
        $this->post(route('pos.attendance.clock-in'), [
            'employee_id' => $this->kasirB->id,
            'pin' => '111111',
            'shift_id' => $this->shift->id,
        ])->assertSessionHasNoErrors();

        $attendance = Attendance::where('user_id', $this->kasirB->id)->firstOrFail();

        $this->assertSame($this->outletB->id, $attendance->outlet_id);
        $this->assertSame($this->outletB->id, $attendance->home_outlet_id);
        $this->assertFalse($attendance->isCrossOutlet());
    }

    public function test_rekap_absensi_tetap_menempel_pada_satu_user_id_lintas_outlet(): void
    {
        // Hari sebelumnya karyawan absen di outlet induknya
        Attendance::create([
            'user_id' => $this->rollingEmployee->id,
            'logged_in_user_id' => $this->rollingEmployee->id,
            'outlet_id' => $this->outletA->id,
            'home_outlet_id' => $this->outletA->id,
            'is_cross_outlet' => false,
            'shift_id' => $this->shift->id,
            'clock_in' => now()->subDay()->setTime(8, 0),
            'clock_out' => now()->subDay()->setTime(16, 0),
            'status' => 'present',
            'worked_minutes' => 480,
        ]);

        // Hari ini absen di outlet B
        $this->post(route('pos.attendance.clock-in'), [
            'employee_id' => $this->rollingEmployee->id,
            'pin' => '123456',
            'shift_id' => $this->shift->id,
        ])->assertSessionHasNoErrors();

        // Dua outlet berbeda, tapi tetap satu identitas karyawan
        $records = Attendance::where('user_id', $this->rollingEmployee->id)->get();

        $this->assertCount(2, $records);
        $this->assertEqualsCanonicalizing(
            [$this->outletA->id, $this->outletB->id],
            $records->pluck('outlet_id')->all()
        );
    }

    public function test_karyawan_titipan_muncul_di_grid_setelah_clock_in(): void
    {
        $this->post(route('pos.attendance.clock-in'), [
            'employee_id' => $this->rollingEmployee->id,
            'pin' => '123456',
            'shift_id' => $this->shift->id,
        ])->assertSessionHasNoErrors();

        // Wajib tampil agar bisa clock-out tanpa harus dicari ulang
        $this->get(route('pos.attendance.index'))
            ->assertOk()
            ->assertViewHas('crossOutletEmployees', function ($employees) {
                return $employees->contains('id', $this->rollingEmployee->id);
            });
    }

    public function test_grid_utama_tidak_memuat_karyawan_outlet_lain(): void
    {
        $this->get(route('pos.attendance.index'))
            ->assertOk()
            ->assertViewHas('employees', function ($employees) {
                return !$employees->contains('id', $this->rollingEmployee->id)
                    && $employees->contains('id', $this->kasirB->id);
            });
    }

    public function test_pencarian_menemukan_karyawan_outlet_lain(): void
    {
        $keyword = substr($this->rollingEmployee->name, 0, 4);

        $this->get(route('pos.attendance.search', ['q' => $keyword]))
            ->assertOk()
            ->assertSee($this->rollingEmployee->name, false)
            ->assertSee('Titipan dari ' . $this->outletA->name, false);
    }

    public function test_pencarian_menolak_kata_kunci_terlalu_pendek(): void
    {
        $this->get(route('pos.attendance.search', ['q' => 'ab']))
            ->assertSessionHasErrors('q');
    }

    public function test_clock_out_lintas_outlet_menutup_absensi_yang_sama(): void
    {
        $this->post(route('pos.attendance.clock-in'), [
            'employee_id' => $this->rollingEmployee->id,
            'pin' => '123456',
            'shift_id' => $this->shift->id,
        ])->assertSessionHasNoErrors();

        $this->post(route('pos.attendance.clock-out'), [
            'employee_id' => $this->rollingEmployee->id,
            'pin' => '123456',
        ])->assertSessionHasNoErrors();

        $attendance = Attendance::where('user_id', $this->rollingEmployee->id)->firstOrFail();

        $this->assertNotNull($attendance->clock_out);
        $this->assertSame($this->outletB->id, $attendance->outlet_id);
    }

    public function test_karyawan_tanpa_pin_absensi_ditolak(): void
    {
        $tanpaPin = User::factory()->create([
            'role_id' => $this->rollingEmployee->role_id,
            'outlet_id' => $this->outletA->id,
            'is_active' => true,
            'attendance_pin' => null,
        ]);

        $this->post(route('pos.attendance.clock-in'), [
            'employee_id' => $tanpaPin->id,
            'pin' => '123456',
            'shift_id' => $this->shift->id,
        ])->assertSessionHas('error');

        $this->assertDatabaseMissing('attendances', ['user_id' => $tanpaPin->id]);
    }
}
