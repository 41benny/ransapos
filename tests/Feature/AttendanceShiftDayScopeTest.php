<?php

namespace Tests\Feature;

use App\Models\Outlet;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Shift dibatasi ke hari tertentu (weekday/weekend) supaya pilihan yang jelas
 * tidak relevan hari itu (mis. shift "Weekend" di hari Selasa) tidak pernah
 * muncul sebagai opsi clock-in sama sekali -- bukan sekadar diberi label
 * lebih jelas.
 */
class AttendanceShiftDayScopeTest extends TestCase
{
    use RefreshDatabase;

    protected Outlet $outlet;
    protected User $kasir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outlet = Outlet::create([
            'name' => 'Outlet A',
            'code' => 'OUT-A',
            'is_active' => true,
        ]);

        $kasirRole = Role::query()->firstOrCreate(
            ['name' => 'kasir'],
            ['display_name' => 'Kasir', 'description' => 'POS operator']
        );

        $this->kasir = User::factory()->create([
            'role_id' => $kasirRole->id,
            'outlet_id' => $this->outlet->id,
            'is_active' => true,
            'attendance_pin' => Hash::make('111111'),
        ]);

        $this->actingAs($this->kasir);
    }

    private function shift(string $name, string $start, string $end, string $dayScope = 'all'): Shift
    {
        return Shift::create([
            'name' => $name,
            'start_time' => $start,
            'end_time' => $end,
            'late_tolerance_minutes' => 0,
            'is_overnight' => false,
            'day_scope' => $dayScope,
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }

    public function test_shift_akhir_pekan_tidak_berlaku_di_hari_kerja(): void
    {
        $weekend = $this->shift('Weekend', '10:00', '19:00', 'weekend');

        // 2026-08-11 adalah hari Selasa.
        $this->assertFalse($weekend->appliesOn(Carbon::parse('2026-08-11')));
        $this->assertTrue($weekend->appliesOn(Carbon::parse('2026-08-08'))); // Sabtu
    }

    public function test_shift_hari_kerja_tidak_berlaku_di_akhir_pekan(): void
    {
        $weekday = $this->shift('Weekdays', '10:30', '19:30', 'weekday');

        $this->assertTrue($weekday->appliesOn(Carbon::parse('2026-08-11'))); // Selasa
        $this->assertFalse($weekday->appliesOn(Carbon::parse('2026-08-08'))); // Sabtu
    }

    public function test_shift_setiap_hari_selalu_berlaku(): void
    {
        $harian = $this->shift('Harian', '09:00', '17:00', 'all');

        $this->assertTrue($harian->appliesOn(Carbon::parse('2026-08-11')));
        $this->assertTrue($harian->appliesOn(Carbon::parse('2026-08-08')));
    }

    public function test_grid_clock_in_hanya_menampilkan_shift_yang_berlaku_hari_ini(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-11 09:00:00')); // Selasa

        $weekday = $this->shift('Weekdays', '10:30', '19:30', 'weekday');
        $this->shift('Weekend', '10:00', '19:00', 'weekend');
        $harian = $this->shift('Shift Harian', '08:00', '16:00', 'all');

        $response = $this->get(route('pos.attendance.index'));

        $response->assertViewHas('shifts', function ($shifts) use ($weekday, $harian) {
            $ids = $shifts->pluck('id')->all();

            return in_array($weekday->id, $ids, true)
                && in_array($harian->id, $ids, true)
                && count($ids) === 2;
        });

        Carbon::setTestNow();
    }

    public function test_grid_clock_in_menampilkan_shift_weekend_di_hari_sabtu(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-08 09:00:00')); // Sabtu

        $this->shift('Weekdays', '10:30', '19:30', 'weekday');
        $weekend = $this->shift('Weekend', '10:00', '19:00', 'weekend');

        $response = $this->get(route('pos.attendance.index'));

        $response->assertViewHas('shifts', function ($shifts) use ($weekend) {
            return $shifts->pluck('id')->contains($weekend->id) && $shifts->count() === 1;
        });

        Carbon::setTestNow();
    }

    public function test_clock_in_dengan_shift_yang_tidak_berlaku_hari_ini_ditolak_di_server(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-11 09:00:00')); // Selasa

        $employee = User::factory()->create([
            'outlet_id' => $this->outlet->id,
            'is_active' => true,
            'attendance_pin' => Hash::make('123456'),
        ]);

        $weekend = $this->shift('Weekend', '10:00', '19:00', 'weekend');

        // Request dikarang tangan langsung ke endpoint, melewati dropdown --
        // yang seharusnya tidak pernah menampilkan opsi ini hari Selasa.
        $response = $this->post(route('pos.attendance.clock-in'), [
            'employee_id' => $employee->id,
            'pin' => '123456',
            'shift_id' => $weekend->id,
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('attendances', ['user_id' => $employee->id]);

        Carbon::setTestNow();
    }

    public function test_clock_in_dengan_shift_yang_berlaku_hari_ini_diterima(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-11 10:35:00')); // Selasa

        $employee = User::factory()->create([
            'outlet_id' => $this->outlet->id,
            'is_active' => true,
            'attendance_pin' => Hash::make('123456'),
        ]);

        $weekday = $this->shift('Weekdays', '10:30', '19:30', 'weekday');

        $response = $this->post(route('pos.attendance.clock-in'), [
            'employee_id' => $employee->id,
            'pin' => '123456',
            'shift_id' => $weekday->id,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('attendances', [
            'user_id' => $employee->id,
            'shift_id' => $weekday->id,
        ]);

        Carbon::setTestNow();
    }

    public function test_shift_yang_paling_dekat_jam_sekarang_disorot_sebagai_saran(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-11 11:07:00')); // Selasa, kasus Diva

        $shiftSalah = $this->shift('Weekdays', '10:30', '19:30', 'weekday');
        $shiftBenar = $this->shift('Weekdays 2', '11:30', '20:30', 'weekday');

        $html = $this->get(route('pos.attendance.index'))->getContent();

        // Radio shift yang benar (11:30, jarak 23 menit) harus ter-checked;
        // shift yang salah (10:30, jarak 37 menit) tidak.
        $this->assertStringContainsString(
            'value="' . $shiftBenar->id . '" class="sr-only" required checked',
            $html
        );
        $this->assertStringNotContainsString(
            'value="' . $shiftSalah->id . '" class="sr-only" required checked',
            $html
        );

        Carbon::setTestNow();
    }

    public function test_admin_bisa_menyimpan_shift_dengan_day_scope(): void
    {
        $superadminRole = Role::query()->firstOrCreate(
            ['name' => 'superadmin'],
            ['display_name' => 'Super Admin', 'description' => 'Full access']
        );
        $admin = User::factory()->create(['is_active' => true, 'role_id' => $superadminRole->id]);
        $this->actingAs($admin);

        $response = $this->post(route('admin.shifts.store'), [
            'name' => 'Weekend Pagi',
            'start_time' => '10:00',
            'end_time' => '19:00',
            'late_tolerance_minutes' => 0,
            'day_scope' => 'weekend',
            'is_active' => '1',
            'sort_order' => 0,
        ]);

        $response->assertRedirect(route('admin.shifts.index'));
        $this->assertDatabaseHas('shifts', [
            'name' => 'Weekend Pagi',
            'day_scope' => 'weekend',
        ]);
    }

    public function test_shift_tanpa_day_scope_default_ke_setiap_hari(): void
    {
        $superadminRole = Role::query()->firstOrCreate(
            ['name' => 'superadmin'],
            ['display_name' => 'Super Admin', 'description' => 'Full access']
        );
        $admin = User::factory()->create(['is_active' => true, 'role_id' => $superadminRole->id]);
        $this->actingAs($admin);

        $this->post(route('admin.shifts.store'), [
            'name' => 'Shift Lama',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'is_active' => '1',
        ]);

        $this->assertDatabaseHas('shifts', [
            'name' => 'Shift Lama',
            'day_scope' => 'all',
        ]);
    }
}
