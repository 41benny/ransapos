<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Outlet;
use App\Models\Shift;
use App\Models\User;
use App\Services\AttendanceMetricsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Koreksi salah pilih shift saat clock-in.
 *
 * Kasus nyata 6 Agustus 2026: karyawan clock-in 11:07 lalu memilih shift yang
 * mulai 10:30 padahal shift-nya yang mulai 11:30, sehingga tercatat terlambat
 * 37 menit padahal tepat waktu. Kedua shift bernama sama di layar.
 */
class AttendanceShiftCorrectionTest extends TestCase
{
    use RefreshDatabase;

    private function shift(string $name, string $start, string $end, int $tolerance = 0): Shift
    {
        return Shift::create([
            'name' => $name,
            'start_time' => $start,
            'end_time' => $end,
            'late_tolerance_minutes' => $tolerance,
            'is_overnight' => false,
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }

    private function attendance(Shift $shift, Carbon $clockIn, ?Carbon $clockOut = null): Attendance
    {
        $outlet = Outlet::factory()->create();
        $employee = User::factory()->create(['outlet_id' => $outlet->id]);
        $service = app(AttendanceMetricsService::class);

        $arrival = $service->arrivalMetrics($shift, $clockIn);
        $departure = $service->departureMetrics($shift, $clockIn, $clockOut);

        return Attendance::create(array_merge($arrival, $departure, [
            'user_id' => $employee->id,
            'outlet_id' => $outlet->id,
            'home_outlet_id' => $outlet->id,
            'shift_id' => $shift->id,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
        ]));
    }

    public function test_koreksi_shift_menghapus_status_terlambat_yang_salah(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-06 12:40:00'));

        $shiftSalah = $this->shift('Weekdays', '10:30:00', '19:30:00');
        $shiftBenar = $this->shift('Weekdays 2', '11:30:00', '20:30:00');

        $absen = $this->attendance($shiftSalah, Carbon::parse('2026-08-06 11:07:54'));

        // Kondisi awal: persis seperti yang muncul di layar produksi.
        $this->assertSame('late', $absen->status);
        $this->assertSame(37, $absen->late_minutes);

        $admin = User::factory()->create();
        $hasil = app(AttendanceMetricsService::class)
            ->correctShift($absen, $shiftBenar, $admin, 'Karyawan salah pilih shift');

        $this->assertSame('present', $hasil->status);
        $this->assertSame(0, $hasil->late_minutes);
        $this->assertSame($shiftBenar->id, $hasil->shift_id);

        Carbon::setTestNow();
    }

    public function test_shift_asli_dan_pelaku_koreksi_tersimpan_sebagai_jejak_audit(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-06 12:40:00'));

        $shiftSalah = $this->shift('Weekdays', '10:30:00', '19:30:00');
        $shiftBenar = $this->shift('Weekdays 2', '11:30:00', '20:30:00');
        $shiftKetiga = $this->shift('Weekend', '12:00:00', '21:00:00');

        $absen = $this->attendance($shiftSalah, Carbon::parse('2026-08-06 11:07:54'));
        $admin = User::factory()->create();
        $service = app(AttendanceMetricsService::class);

        $hasil = $service->correctShift($absen, $shiftBenar, $admin, 'Salah pilih shift');

        $this->assertSame($shiftSalah->id, $hasil->original_shift_id);
        $this->assertSame($admin->id, $hasil->shift_corrected_by);
        $this->assertSame('Salah pilih shift', $hasil->shift_correction_reason);
        $this->assertTrue($hasil->isShiftCorrected());

        // Koreksi kedua tidak boleh menimpa jejak pilihan ASLI karyawan.
        $hasil2 = $service->correctShift($hasil, $shiftKetiga, $admin, 'Ternyata weekend');
        $this->assertSame($shiftSalah->id, $hasil2->original_shift_id);

        Carbon::setTestNow();
    }

    public function test_absensi_hari_kemarin_terkunci(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-06 09:00:00'));

        $shiftSalah = $this->shift('Weekdays', '10:30:00', '19:30:00');
        $shiftBenar = $this->shift('Weekdays 2', '11:30:00', '20:30:00');

        $absen = $this->attendance(
            $shiftSalah,
            Carbon::parse('2026-08-05 11:07:54'),
            Carbon::parse('2026-08-05 19:30:00')
        );

        $service = app(AttendanceMetricsService::class);
        $this->assertFalse($service->canCorrect($absen));

        $this->expectExceptionMessage('sudah dikunci');
        $service->correctShift($absen, $shiftBenar, User::factory()->create(), 'Telat lapor');

        Carbon::setTestNow();
    }

    public function test_absensi_yang_belum_clock_out_tetap_bisa_dikoreksi_walau_lewat_tengah_malam(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-07 01:00:00'));

        $shiftSalah = $this->shift('Malam', '20:00:00', '23:00:00');
        $shiftBenar = $this->shift('Malam 2', '22:30:00', '23:59:00');

        // Shift malam menyeberang tengah malam dan orangnya masih di tempat.
        $absen = $this->attendance($shiftSalah, Carbon::parse('2026-08-06 22:05:00'));
        $this->assertSame('late', $absen->status);

        // Sudah ganti tanggal, tapi belum clock-out — masih boleh dikoreksi.
        $service = app(AttendanceMetricsService::class);
        $this->assertTrue($service->canCorrect($absen));

        $hasil = $service->correctShift($absen, $shiftBenar, User::factory()->create(), 'Salah pilih');
        $this->assertSame('present', $hasil->status);
        $this->assertSame(0, $hasil->late_minutes);

        Carbon::setTestNow();
    }

    public function test_koreksi_menghitung_ulang_pulang_cepat_dan_lembur(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-06 20:00:00'));

        $shiftSalah = $this->shift('Weekdays', '10:30:00', '19:30:00');
        $shiftBenar = $this->shift('Weekdays 2', '11:30:00', '20:30:00');

        $absen = $this->attendance(
            $shiftSalah,
            Carbon::parse('2026-08-06 11:07:54'),
            Carbon::parse('2026-08-06 19:45:00')
        );

        // Terhadap shift lama (selesai 19:30) dia lembur 15 menit.
        $this->assertSame(15, $absen->overtime_minutes);
        $this->assertSame(0, $absen->early_leave_minutes);

        $hasil = app(AttendanceMetricsService::class)
            ->correctShift($absen, $shiftBenar, User::factory()->create(), 'Salah pilih shift');

        // Terhadap shift yang benar (selesai 20:30) dia justru pulang 45 menit
        // lebih cepat. Kalau metrik ini tidak ikut dihitung ulang, penggajian
        // membayar lembur yang tidak pernah terjadi.
        $this->assertSame(0, $hasil->overtime_minutes);
        $this->assertSame(45, $hasil->early_leave_minutes);

        Carbon::setTestNow();
    }

    public function test_toleransi_keterlambatan_tetap_dihormati_saat_koreksi(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-06 12:00:00'));

        $shiftSalah = $this->shift('Pagi', '08:00:00', '17:00:00');
        $shiftToleransi = $this->shift('Pagi Fleksibel', '09:00:00', '18:00:00', 15);

        // 09:10 masih di dalam toleransi 15 menit shift tujuan.
        $absen = $this->attendance($shiftSalah, Carbon::parse('2026-08-06 09:10:00'));
        $this->assertSame('late', $absen->status);

        $hasil = app(AttendanceMetricsService::class)
            ->correctShift($absen, $shiftToleransi, User::factory()->create(), 'Salah pilih');

        $this->assertSame('present', $hasil->status);
        $this->assertSame(0, $hasil->late_minutes);

        Carbon::setTestNow();
    }

    public function test_alasan_kosong_ditolak(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-06 12:00:00'));

        $shiftSalah = $this->shift('Weekdays', '10:30:00', '19:30:00');
        $shiftBenar = $this->shift('Weekdays 2', '11:30:00', '20:30:00');
        $absen = $this->attendance($shiftSalah, Carbon::parse('2026-08-06 11:07:54'));

        $this->expectExceptionMessage('Alasan koreksi wajib diisi.');

        app(AttendanceMetricsService::class)
            ->correctShift($absen, $shiftBenar, User::factory()->create(), '   ');

        Carbon::setTestNow();
    }

    public function test_koreksi_ke_shift_yang_sama_ditolak(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-06 12:00:00'));

        $shift = $this->shift('Weekdays', '10:30:00', '19:30:00');
        $absen = $this->attendance($shift, Carbon::parse('2026-08-06 11:07:54'));

        $this->expectExceptionMessage('sudah sama');

        app(AttendanceMetricsService::class)
            ->correctShift($absen, $shift, User::factory()->create(), 'Iseng');

        Carbon::setTestNow();
    }
}
