<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Outlet;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Tabel "Gaji Karyawan Outlet" yang menumpang halaman Rekap Absensi.
 *
 * Aturan yang diuji, disepakati dengan owner 06/08/2026:
 *  - Hari kerja dihitung dari HARI unik yang ada absensi, bukan jumlah baris.
 *  - Telat tetap dihitung 1 hari kerja penuh, tanpa potongan.
 *  - Tunjangan flat ditambahkan kalau tanggal akhir filter > tanggal 15.
 */
class AttendancePayrollTableTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Outlet $outlet;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::query()->where('name', 'superadmin')->firstOrFail();
        $this->admin = User::factory()->create(['role_id' => $role->id, 'is_active' => true]);
        $this->actingAs($this->admin);

        $this->outlet = Outlet::create(['name' => 'Outlet Gaji', 'code' => 'OUT-GJ', 'is_active' => true]);
    }

    private function attend(User $employee, string $date, string $status = 'present', string $time = '10:00:00'): Attendance
    {
        $clockIn = Carbon::parse("{$date} {$time}");

        return Attendance::create([
            'user_id' => $employee->id,
            'outlet_id' => $this->outlet->id,
            'clock_in' => $clockIn,
            'clock_out' => (clone $clockIn)->addHours(8),
            'status' => $status,
        ]);
    }

    public function test_gaji_dihitung_dari_hari_kerja_kali_tarif_periode_pertama(): void
    {
        $diva = User::factory()->create(['outlet_id' => $this->outlet->id, 'is_active' => true]);

        foreach (range(1, 13) as $day) {
            $this->attend($diva, sprintf('2026-08-%02d', $day));
        }

        $response = $this->get(route('admin.reports.attendance.index', [
            'period' => 'custom',
            'date_from' => '2026-08-01',
            'date_to' => '2026-08-15',
        ]));

        $response->assertOk();
        $response->assertViewHas('payrollAllowanceApplies', false);
        $response->assertViewHas('payrollRows', function ($rows) {
            $row = collect($rows)->first();
            return $row['days_worked'] === 13
                && (float) $row['daily_wage'] === 50000.0
                && (float) $row['subtotal'] === 650000.0
                && (float) $row['allowance'] === 0.0
                && (float) $row['total'] === 650000.0;
        });
    }

    public function test_telat_tetap_dihitung_satu_hari_penuh(): void
    {
        $employee = User::factory()->create(['outlet_id' => $this->outlet->id, 'is_active' => true]);

        $this->attend($employee, '2026-08-03', 'present');
        $this->attend($employee, '2026-08-04', 'late');

        $response = $this->get(route('admin.reports.attendance.index', [
            'period' => 'custom',
            'date_from' => '2026-08-01',
            'date_to' => '2026-08-15',
        ]));

        $response->assertViewHas('payrollRows', function ($rows) {
            $row = collect($rows)->first();
            return $row['days_worked'] === 2 && (float) $row['subtotal'] === 100000.0;
        });
    }

    public function test_dua_baris_absensi_di_hari_yang_sama_dihitung_satu_hari(): void
    {
        $employee = User::factory()->create(['outlet_id' => $this->outlet->id, 'is_active' => true]);

        // Simulasi clock-in dobel di hari yang sama (mis. error jaringan lalu
        // input ulang) -- tidak boleh dihitung 2 hari kerja.
        $this->attend($employee, '2026-08-03', 'present', '10:00:00');
        $this->attend($employee, '2026-08-03', 'present', '10:05:00');

        $response = $this->get(route('admin.reports.attendance.index', [
            'period' => 'custom',
            'date_from' => '2026-08-01',
            'date_to' => '2026-08-15',
        ]));

        $response->assertViewHas('payrollRows', function ($rows) {
            $row = collect($rows)->first();
            return $row['days_worked'] === 1 && (float) $row['subtotal'] === 50000.0;
        });
    }

    public function test_tunjangan_ditambahkan_kalau_filter_masuk_periode_kedua(): void
    {
        $employee = User::factory()->create(['outlet_id' => $this->outlet->id, 'is_active' => true]);

        foreach (range(16, 25) as $day) {
            $this->attend($employee, sprintf('2026-08-%02d', $day));
        }

        $response = $this->get(route('admin.reports.attendance.index', [
            'period' => 'custom',
            'date_from' => '2026-08-16',
            'date_to' => '2026-08-31',
        ]));

        $response->assertViewHas('payrollAllowanceApplies', true);
        $response->assertViewHas('payrollRows', function ($rows) {
            $row = collect($rows)->first();
            // 10 hari x 50.000 + tunjangan 250.000
            return (float) $row['subtotal'] === 500000.0
                && (float) $row['allowance'] === 250000.0
                && (float) $row['total'] === 750000.0;
        });
    }

    public function test_tarif_dan_tunjangan_ikut_setting_yang_diubah(): void
    {
        Setting::setValue('payroll_daily_wage', 75000);
        Setting::setValue('payroll_period2_allowance', 300000);

        $employee = User::factory()->create(['outlet_id' => $this->outlet->id, 'is_active' => true]);
        $this->attend($employee, '2026-08-20');

        $response = $this->get(route('admin.reports.attendance.index', [
            'period' => 'custom',
            'date_from' => '2026-08-16',
            'date_to' => '2026-08-31',
        ]));

        $response->assertViewHas('payrollRows', function ($rows) {
            $row = collect($rows)->first();
            return (float) $row['daily_wage'] === 75000.0
                && (float) $row['subtotal'] === 75000.0
                && (float) $row['allowance'] === 300000.0;
        });
    }

    public static function monthlyAllowancePeriods(): array
    {
        return [
            'September tetap nominal lama' => ['2026-09-16', '2026-09-30', 250000.0],
            'Oktober periode pertama tanpa tunjangan' => ['2026-10-01', '2026-10-15', 0.0],
            'Oktober mulai nominal baru' => ['2026-10-16', '2026-10-31', 310000.0],
            'November periode pertama tanpa tunjangan' => ['2026-11-01', '2026-11-15', 0.0],
            'November tetap nominal baru' => ['2026-11-16', '2026-11-30', 310000.0],
            'Desember tetap nominal baru' => ['2026-12-16', '2026-12-31', 310000.0],
        ];
    }

    #[DataProvider('monthlyAllowancePeriods')]
    public function test_tunjangan_semua_karyawan_mengikuti_bulan_berlaku(string $dateFrom, string $dateTo, float $allowance): void
    {
        $employees = User::factory()->count(2)->create(['outlet_id' => $this->outlet->id, 'is_active' => true]);
        foreach ($employees as $employee) {
            $this->attend($employee, $dateFrom);
        }

        $response = $this->get(route('admin.reports.attendance.index', [
            'period' => 'custom',
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ]));

        $response->assertOk();
        $response->assertViewHas('payrollRows', function ($rows) use ($allowance) {
            return count($rows) === 2 && collect($rows)->every(fn ($row) =>
                (float) $row['allowance'] === $allowance
                && (float) $row['total'] === 50000.0 + $allowance
            );
        });
        $response->assertViewHas('payrollGrandTotal', 2 * (50000.0 + $allowance));
    }

    public function test_rekap_sebulan_menambahkan_tunjangan_sekali(): void
    {
        $employee = User::factory()->create(['outlet_id' => $this->outlet->id, 'is_active' => true]);
        $this->attend($employee, '2026-10-03');
        $this->attend($employee, '2026-10-20');

        $response = $this->get(route('admin.reports.attendance.index', [
            'period' => 'custom',
            'date_from' => '2026-10-01',
            'date_to' => '2026-10-31',
        ]));

        $response->assertOk();
        $response->assertViewHas('payrollRows', fn ($rows) =>
            count($rows) === 1
            && $rows[0]['days_worked'] === 2
            && (float) $rows[0]['allowance'] === 310000.0
            && (float) $rows[0]['total'] === 410000.0
        );
    }

    public function test_migrasi_jadwal_tidak_menimpa_nominal_lama_dan_membersihkan_cache(): void
    {
        Setting::query()->where('key', 'payroll_period2_allowance_schedule')->delete();
        Setting::setValue('payroll_period2_allowance', 275000);
        $this->assertSame('{}', Setting::getValue('payroll_period2_allowance_schedule', '{}'));

        $migration = require database_path('migrations/2026_10_31_000001_schedule_monthly_payroll_allowance.php');
        $migration->up();

        $this->assertSame('275000', Setting::getValue('payroll_period2_allowance'));
        $this->assertSame(
            ['2026-10-01' => 310000],
            json_decode(Setting::getValue('payroll_period2_allowance_schedule'), true)
        );
    }

    public function test_gaji_menghormati_filter_outlet_yang_sedang_aktif(): void
    {
        $outletLain = Outlet::create(['name' => 'Outlet Lain', 'code' => 'OUT-LN', 'is_active' => true]);

        $diOutletIni = User::factory()->create(['outlet_id' => $this->outlet->id, 'is_active' => true]);
        $diOutletLain = User::factory()->create(['outlet_id' => $outletLain->id, 'is_active' => true]);

        $this->attend($diOutletIni, '2026-08-03');

        Attendance::create([
            'user_id' => $diOutletLain->id,
            'outlet_id' => $outletLain->id,
            'clock_in' => Carbon::parse('2026-08-03 10:00:00'),
            'clock_out' => Carbon::parse('2026-08-03 18:00:00'),
            'status' => 'present',
        ]);

        $response = $this->get(route('admin.reports.attendance.index', [
            'period' => 'custom',
            'date_from' => '2026-08-01',
            'date_to' => '2026-08-15',
            'outlet_id' => $this->outlet->id,
        ]));

        $response->assertViewHas('payrollRows', function ($rows) {
            return count($rows) === 1 && $rows[0]['name'] !== null;
        });
    }

    public function test_tidak_ada_absensi_menampilkan_tabel_kosong_tanpa_error(): void
    {
        $response = $this->get(route('admin.reports.attendance.index', [
            'period' => 'custom',
            'date_from' => '2026-08-01',
            'date_to' => '2026-08-15',
        ]));

        $response->assertOk();
        $response->assertViewHas('payrollRows', []);
        $response->assertViewHas('payrollGrandTotal', 0.0);
    }
}
