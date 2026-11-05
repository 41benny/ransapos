<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\CashSession;
use App\Models\Outlet;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\User;
use App\Services\CashSessionService;
use App\Services\ShiftAttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ShiftAttendanceClosingTest extends TestCase
{
    use RefreshDatabase;

    private CashSession $session;
    private User $cashier;
    private Shift $shift;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::setValue('pos_drawer_expense_enabled', false);
        $this->travelTo(now()->setDate(2026, 11, 5)->setTime(18, 0));
        $outlet = Outlet::factory()->create();
        $role = Role::firstOrCreate(['name' => 'kasir'], ['display_name' => 'Kasir']);
        $this->cashier = User::factory()->create(['role_id' => $role->id, 'outlet_id' => $outlet->id]);
        $this->session = CashSession::create([
            'session_number' => 'CS-ATTENDANCE', 'outlet_id' => $outlet->id,
            'user_id' => $this->cashier->id, 'opened_at' => today()->setTime(10, 0),
            'opening_balance' => 0, 'expected_balance' => 0, 'status' => 'open',
        ]);
        $this->shift = Shift::create([
            'name' => 'Pagi', 'start_time' => '08:00', 'end_time' => '17:00',
            'is_active' => true, 'is_overnight' => false,
        ]);
        $this->actingAs($this->cashier);
    }

    private function attendance(array $attributes = [], ?User $employee = null): Attendance
    {
        $employee ??= User::factory()->create(['outlet_id' => $this->session->outlet_id]);

        return Attendance::create(array_replace([
            'user_id' => $employee->id, 'logged_in_user_id' => $this->cashier->id,
            'outlet_id' => $this->session->outlet_id, 'shift_id' => $this->shift->id,
            'clock_in' => today()->setTime(8, 0), 'status' => 'present',
        ], $attributes));
    }

    public function test_close_requires_every_employee_including_visiting_employee_to_checkout(): void
    {
        $this->attendance(['clock_out' => now()->subHour()]);
        $visitor = User::factory()->create(['name' => 'Budi Titipan', 'outlet_id' => Outlet::factory()->create()->id]);
        $pending = $this->attendance(['is_cross_outlet' => true], $visitor);
        $this->get(route('pos.sessions.close'))->assertSuccessful()
            ->assertSee('Budi Titipan')->assertSee('Buka Absensi');
        $this->from(route('pos.sessions.close'))
            ->post(route('pos.sessions.close.store', $this->session), ['actual_balance' => 0])
            ->assertRedirect(route('pos.sessions.close'))->assertSessionHas('error', fn ($message) => str_contains($message, 'Budi Titipan'));
        $this->assertSame('open', $this->session->fresh()->status);
        $this->assertNull($this->session->fresh()->closed_at);
        $this->assertNull($pending->fresh()->clock_out);

        $pending->update(['clock_out' => now()]);
        $this->post(route('pos.sessions.close.store', $this->session), ['actual_balance' => 0])
            ->assertRedirect(route('pos.dashboard'))->assertSessionHas('success');
        $this->assertSame('closed', $this->session->fresh()->status);
    }

    public function test_service_also_blocks_direct_close_with_unfinished_attendance(): void
    {
        $this->attendance();
        try {
            app(CashSessionService::class)->closeSession($this->session, 0);
            $this->fail('Closing must be blocked outside the POS page too.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('attendance', $exception->errors());
        }
        $this->assertSame('open', $this->session->fresh()->status);
    }

    public function test_other_outlets_and_old_historical_records_do_not_block_this_operational_day(): void
    {
        $this->attendance(['outlet_id' => Outlet::factory()->create()->id]);
        $this->attendance(['clock_in' => today()->subDay()->setTime(8, 0)]);
        $closed = app(CashSessionService::class)->closeSession($this->session, 0);
        $this->assertSame('closed', $closed->status);
    }

    public function test_no_attendance_does_not_prevent_closing(): void
    {
        $closed = app(CashSessionService::class)->closeSession($this->session, 0);
        $this->assertSame('closed', $closed->status);
    }

    public function test_admin_can_save_the_attendance_closing_setting(): void
    {
        $this->cashier->update(['role_id' => Role::firstOrCreate(['name' => 'superadmin'], ['display_name' => 'Superadmin'])->id]);
        $this->cashier->unsetRelation('role');
        $this->get(route('admin.settings.index'))->assertSuccessful()
            ->assertSee('Wajib check-out semua karyawan sebelum tutup shift');
        $this->put(route('admin.settings.update'), [
            'company_name' => 'Test', 'require_attendance_checkout_before_close' => '0',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertFalse(Setting::getBool(ShiftAttendanceService::SETTING, true));
        $this->attendance();
        $this->assertSame('closed', app(CashSessionService::class)->closeSession($this->session, 0)->status);
    }

    public function test_overnight_shift_keeps_pending_attendance_visible_and_allows_pin_checkout(): void
    {
        $this->travelTo(today()->setTime(0, 30));
        $this->session->update(['opened_at' => today()->subDay()->setTime(22, 0)]);
        $employee = User::factory()->create([
            'name' => 'Karyawan Malam', 'outlet_id' => $this->session->outlet_id,
            'attendance_pin' => Hash::make('123456'), 'is_active' => true,
        ]);
        $pending = $this->attendance(['clock_in' => today()->subDay()->setTime(21, 55)], $employee);
        $this->assertCount(1, app(ShiftAttendanceService::class)->pendingClockOuts($this->session));
        $this->get(route('pos.attendance.index'))->assertSuccessful()->assertSee('Karyawan Malam');
        $this->post(route('pos.attendance.clock-out'), ['employee_id' => $employee->id, 'pin' => '123456'])
            ->assertRedirect()->assertSessionHas('success');
        $this->assertNotNull($pending->fresh()->clock_out);
        $this->assertSame('closed', app(CashSessionService::class)->closeSession($this->session, 0)->status);
    }
}
