<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Shift;
use App\Services\AttendanceMetricsService;
use Exception;
use Illuminate\Http\Request;

/**
 * Koreksi shift absensi oleh admin, hanya untuk hari yang sedang berjalan.
 *
 * Yang boleh diubah HANYA pilihan shift-nya. Jam masuk dan jam keluar sengaja
 * tidak bisa disentuh: keduanya adalah waktu yang dicatat mesin, dan begitu
 * bisa diedit, seluruh catatan absensi berhenti menjadi bukti.
 */
class AttendanceCorrectionController extends Controller
{
    public function __construct(
        protected AttendanceMetricsService $metricsService,
    ) {
    }

    public function updateShift(Request $request, Attendance $attendance)
    {
        $validated = $request->validate([
            'shift_id' => ['required', 'integer', 'exists:shifts,id'],
            'reason' => ['required', 'string', 'max:200'],
        ], [
            'shift_id.required' => 'Pilih shift yang benar.',
            'reason.required' => 'Alasan koreksi wajib diisi.',
            'reason.max' => 'Alasan maksimal 200 karakter.',
        ]);

        try {
            $shift = Shift::findOrFail($validated['shift_id']);

            $attendance = $this->metricsService->correctShift(
                $attendance,
                $shift,
                auth()->user(),
                $validated['reason']
            );

            $statusLabel = $attendance->status === 'late'
                ? 'Terlambat ' . $attendance->late_minutes . ' menit'
                : 'Tepat Waktu';

            return back()->with(
                'success',
                'Shift ' . ($attendance->user->name ?? 'karyawan') . ' dikoreksi ke "'
                . $shift->name . '" (' . $shift->timeRangeLabel() . '). Status sekarang: ' . $statusLabel . '.'
            );
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
