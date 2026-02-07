<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Tampilkan halaman absensi dengan grid karyawan
     */
    public function index()
    {
        $loggedInUser = auth()->user();

        // Ambil semua karyawan di outlet yang sama
        $employees = User::where('outlet_id', $loggedInUser->outlet_id)
            ->where('is_active', true)
            ->with(['role', 'attendances' => function ($query) {
                $query->whereDate('clock_in', today())->latest();
            }])
            ->orderBy('name')
            ->get();

        return view('pos.attendance.index', compact('employees', 'loggedInUser'));
    }

    /**
     * Proses clock-in karyawan
     */
    public function clockIn(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:users,id',
            'pin' => 'required|numeric|digits:6'
        ]);

        $employee = User::findOrFail($request->employee_id);
        $loggedInUser = auth()->user();

        // Validasi outlet
        if ($employee->outlet_id !== $loggedInUser->outlet_id) {
            return back()->with('error', 'Karyawan tidak terdaftar di outlet ini');
        }

        // Rate limiting per employee
        $key = 'attendance-pin:' . $employee->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            $minutes = ceil($seconds / 60);

            return back()->with('error', "Terlalu banyak percobaan PIN salah. Coba lagi dalam {$minutes} menit.");
        }

        // Verifikasi PIN
        if (!Hash::check($request->pin, $employee->attendance_pin)) {
            RateLimiter::hit($key, 900); // 15 menit
            return back()->with('error', 'PIN salah');
        }

        // Clear rate limiter jika PIN benar
        RateLimiter::clear($key);

        // Cek apakah sudah ada absensi aktif hari ini
        $existingAttendance = Attendance::where('user_id', $employee->id)
            ->whereDate('clock_in', today())
            ->whereNull('clock_out')
            ->first();

        if ($existingAttendance) {
            return back()->with('error', 'Karyawan sudah melakukan clock-in hari ini');
        }

        // Tentukan status (late jika > jam 9 pagi)
        $clockInTime = now();
        $lateThreshold = today()->setTime(9, 0, 0);
        $status = $clockInTime->greaterThan($lateThreshold) ? 'late' : 'present';

        // Buat record attendance
        $attendance = Attendance::create([
            'user_id' => $employee->id,
            'logged_in_user_id' => $loggedInUser->id,
            'outlet_id' => $employee->outlet_id,
            'clock_in' => $clockInTime,
            'status' => $status,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Deteksi anomali: banyak absensi dalam waktu singkat
        $this->detectAnomalies($loggedInUser->id, $employee->outlet_id);

        $statusText = $status === 'late' ? 'terlambat' : 'tepat waktu';

        return back()->with('success', "Clock-in berhasil untuk {$employee->name} ({$statusText})");
    }

    /**
     * Proses clock-out karyawan
     */
    public function clockOut(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:users,id',
            'pin' => 'required|numeric|digits:6'
        ]);

        $employee = User::findOrFail($request->employee_id);
        $loggedInUser = auth()->user();

        // Validasi outlet
        if ($employee->outlet_id !== $loggedInUser->outlet_id) {
            return back()->with('error', 'Karyawan tidak terdaftar di outlet ini');
        }

        // Rate limiting
        $key = 'attendance-pin:' . $employee->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            $minutes = ceil($seconds / 60);

            return back()->with('error', "Terlalu banyak percobaan PIN salah. Coba lagi dalam {$minutes} menit.");
        }

        // Verifikasi PIN
        if (!Hash::check($request->pin, $employee->attendance_pin)) {
            RateLimiter::hit($key, 900);
            return back()->with('error', 'PIN salah');
        }

        RateLimiter::clear($key);

        // Cari attendance aktif hari ini
        $attendance = Attendance::where('user_id', $employee->id)
            ->whereDate('clock_in', today())
            ->whereNull('clock_out')
            ->first();

        if (!$attendance) {
            return back()->with('error', 'Karyawan belum melakukan clock-in hari ini');
        }

        // Update clock_out
        $attendance->update([
            'clock_out' => now(),
        ]);

        $duration = $attendance->getDurationFormatted();

        return back()->with('success', "Clock-out berhasil untuk {$employee->name}. Durasi kerja: {$duration}");
    }

    /**
     * Deteksi anomali absensi
     */
    private function detectAnomalies($loggedInUserId, $outletId)
    {
        // Hitung jumlah absensi dalam 2 menit terakhir dari kasir yang sama
        $recentCount = Attendance::where('logged_in_user_id', $loggedInUserId)
            ->where('outlet_id', $outletId)
            ->where('clock_in', '>=', now()->subMinutes(2))
            ->count();

        if ($recentCount > 3) {
            // Log warning - bisa dikirim ke admin dashboard atau log file
            Log::warning('Anomali absensi terdeteksi', [
                'logged_in_user_id' => $loggedInUserId,
                'outlet_id' => $outletId,
                'count' => $recentCount,
                'time' => now(),
            ]);
        }
    }
}
