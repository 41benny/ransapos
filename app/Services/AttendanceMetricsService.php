<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * Sumber tunggal rumus metrik absensi: telat, pulang cepat, lembur, durasi.
 *
 * Dipakai bersama oleh clock-in, clock-out, dan koreksi shift. Rumusnya
 * SENGAJA tidak disalin ke masing-masing pemanggil: angka-angka ini dipakai
 * untuk menilai kedisiplinan karyawan, dan dua salinan rumus yang perlahan
 * berbeda berarti absensi asli dan absensi terkoreksi menghasilkan angka
 * berbeda untuk jam masuk yang sama persis.
 */
class AttendanceMetricsService
{
    /**
     * Status & menit telat dari jam masuk terhadap shift.
     *
     * Tanggal acuannya diambil dari `clock_in`, bukan dari "hari ini": koreksi
     * bisa dijalankan beberapa jam setelah absennya dibuat, dan memakai
     * `today()` akan salah begitu absennya menyeberang tengah malam.
     *
     * @return array{status: string, late_minutes: int}
     */
    public function arrivalMetrics(Shift $shift, Carbon $clockIn): array
    {
        $date = $clockIn->copy()->startOfDay();
        $shiftStart = $shift->startFor($date);
        $lateThreshold = $shift->lateThresholdFor($date);

        $isLate = $clockIn->greaterThan($lateThreshold);

        return [
            'status' => $isLate ? 'late' : 'present',
            // Dihitung dari jam mulai shift, bukan dari batas toleransi —
            // toleransi hanya menentukan APAKAH telat, bukan berapa lamanya.
            'late_minutes' => $isLate ? (int) $shiftStart->diffInMinutes($clockIn) : 0,
        ];
    }

    /**
     * Durasi kerja, pulang cepat, dan lembur. Hanya berlaku setelah clock-out.
     *
     * @return array{worked_minutes: int|null, early_leave_minutes: int, overtime_minutes: int}
     */
    public function departureMetrics(?Shift $shift, Carbon $clockIn, ?Carbon $clockOut): array
    {
        if ($clockOut === null) {
            return [
                'worked_minutes' => null,
                'early_leave_minutes' => 0,
                'overtime_minutes' => 0,
            ];
        }

        $earlyLeave = 0;
        $overtime = 0;

        if ($shift) {
            $shiftEnd = $shift->endFor($clockIn);

            if ($clockOut->lessThan($shiftEnd)) {
                $earlyLeave = (int) $clockOut->diffInMinutes($shiftEnd);
            } elseif ($clockOut->greaterThan($shiftEnd)) {
                $overtime = (int) $shiftEnd->diffInMinutes($clockOut);
            }
        }

        return [
            'worked_minutes' => (int) $clockIn->diffInMinutes($clockOut),
            'early_leave_minutes' => $earlyLeave,
            'overtime_minutes' => $overtime,
        ];
    }

    /**
     * Apakah baris absensi ini masih boleh dikoreksi.
     *
     * Aturannya: hanya pada hari kerja yang sama. Setelah harinya lewat,
     * catatan absensi dikunci — permintaan operasional yang disengaja, supaya
     * riwayat kedisiplinan tidak bisa dirapikan belakangan saat penggajian
     * sudah dekat.
     *
     * Absensi yang belum clock-out tetap boleh dikoreksi walau tanggalnya
     * sudah berganti, karena shift malam memang menyeberang tengah malam dan
     * orangnya masih berdiri di tempat kerja.
     */
    public function canCorrect(Attendance $attendance): bool
    {
        if ($attendance->clock_out === null) {
            return true;
        }

        return $attendance->clock_in->isSameDay(now());
    }

    /**
     * Ganti shift sebuah absensi lalu hitung ulang SEMUA metrik turunannya.
     *
     * Mengganti `shift_id` saja tidak cukup: `status` dan `late_minutes`
     * adalah kolom tersimpan, jadi layar akan tetap menampilkan angka lama.
     *
     * @throws Exception
     */
    public function correctShift(Attendance $attendance, Shift $newShift, User $actor, string $reason): Attendance
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new Exception('Alasan koreksi wajib diisi.');
        }

        if (! $newShift->is_active) {
            throw new Exception('Shift tujuan sudah tidak aktif.');
        }

        if (! $this->canCorrect($attendance)) {
            throw new Exception(
                'Absensi tanggal ' . $attendance->clock_in->format('d/m/Y') .
                ' sudah dikunci. Koreksi shift hanya bisa dilakukan pada hari yang sama.'
            );
        }

        if ((int) $attendance->shift_id === (int) $newShift->id) {
            throw new Exception('Shift ini sudah sama dengan yang tercatat sekarang.');
        }

        return DB::transaction(function () use ($attendance, $newShift, $actor, $reason) {
            $attendance = Attendance::whereKey($attendance->id)->lockForUpdate()->firstOrFail();

            // Shift asli disimpan sekali saja. Koreksi kedua tidak boleh
            // menimpanya, kalau tidak jejak pilihan asli karyawan hilang.
            $originalShiftId = $attendance->original_shift_id ?? $attendance->shift_id;

            $arrival = $this->arrivalMetrics($newShift, $attendance->clock_in);
            $departure = $this->departureMetrics($newShift, $attendance->clock_in, $attendance->clock_out);

            $attendance->update(array_merge($arrival, $departure, [
                'shift_id' => $newShift->id,
                'original_shift_id' => $originalShiftId,
                'shift_corrected_by' => $actor->id,
                'shift_corrected_at' => now(),
                'shift_correction_reason' => $reason,
            ]));

            return $attendance->fresh(['shift', 'originalShift', 'shiftCorrector']);
        });
    }
}
