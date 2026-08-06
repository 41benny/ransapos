<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Shift;
use App\Models\User;
use App\Services\AttendanceMetricsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceMetricsService $metricsService,
    ) {
    }

    /**
     * Tampilkan halaman absensi dengan grid karyawan
     */
    public function index()
    {
        $loggedInUser = auth()->user();
        $currentOutletId = $loggedInUser->outlet_id;

        // Grid utama: karyawan di outlet perangkat POS ini
        $employees = $this->employeeQuery()
            ->where('outlet_id', $currentOutletId)
            ->orderBy('name')
            ->get();

        // Karyawan titipan dari outlet lain yang hari ini sudah absen di outlet ini.
        // Wajib ikut tampil supaya mereka bisa clock-out tanpa harus dicari ulang.
        $crossOutletEmployees = collect();
        if ($currentOutletId) {
            $crossOutletIds = Attendance::query()
                ->whereDate('clock_in', today())
                ->where('outlet_id', $currentOutletId)
                ->where('is_cross_outlet', true)
                ->pluck('user_id')
                ->unique();

            if ($crossOutletIds->isNotEmpty()) {
                $crossOutletEmployees = $this->employeeQuery()
                    ->whereIn('id', $crossOutletIds)
                    ->orderBy('name')
                    ->get();
            }
        }

        // Daftar shift aktif untuk dipilih saat clock-in
        $shifts = Shift::active()->get();

        return view('pos.attendance.index', compact(
            'employees',
            'crossOutletEmployees',
            'loggedInUser',
            'shifts',
            'currentOutletId'
        ));
    }

    /**
     * Cari karyawan dari outlet lain (kasir rolling / titipan).
     *
     * Sengaja tidak ditampilkan default di grid: membuka semua nama semua outlet
     * memperbesar peluang titip absen dan membuat layar tidak terbaca.
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:3|max:50',
        ]);

        $loggedInUser = auth()->user();
        $currentOutletId = $loggedInUser->outlet_id;
        $keyword = trim($request->input('q'));

        $employees = $this->employeeQuery()
            ->when($currentOutletId, fn ($query) => $query->where(function ($q) use ($currentOutletId) {
                $q->whereNull('outlet_id')->orWhere('outlet_id', '!=', $currentOutletId);
            }))
            ->where('name', 'like', '%' . $keyword . '%')
            ->orderBy('name')
            ->limit(12)
            ->get();

        return view('pos.attendance._search-results', [
            'employees' => $employees,
            'shifts' => Shift::active()->get(),
            'currentOutletId' => $currentOutletId,
            'keyword' => $keyword,
        ]);
    }

    /**
     * Query dasar karyawan yang boleh absen, lengkap dengan absensi hari ini.
     */
    private function employeeQuery()
    {
        return User::query()
            ->where('is_active', true)
            ->whereNotNull('attendance_pin')
            ->with(['role', 'outlet', 'attendances' => function ($query) {
                $query->whereDate('clock_in', today())->with(['shift', 'outlet'])->latest();
            }]);
    }

    /**
     * Outlet tempat absensi benar-benar terjadi = outlet perangkat POS yang dipakai.
     *
     * Fallback ke outlet induk karyawan hanya untuk operator tanpa outlet
     * (mis. admin pusat), karena `attendances.outlet_id` tidak boleh null.
     */
    private function resolveWorkOutletId(User $loggedInUser, User $employee): ?int
    {
        return $loggedInUser->outlet_id ?? $employee->outlet_id;
    }

    /**
     * Proses clock-in karyawan
     */
    public function clockIn(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:users,id',
            'pin' => 'required|numeric|digits:6',
            'shift_id' => 'required|exists:shifts,id',
        ]);

        $employee = User::with('outlet')->findOrFail($request->employee_id);
        $loggedInUser = auth()->user();

        if (!$employee->is_active || !$employee->attendance_pin) {
            return back()->with('error', 'Karyawan tidak aktif atau belum punya PIN absensi');
        }

        // Outlet fisik tempat absensi terjadi (bukan outlet induk karyawan)
        $workOutletId = $this->resolveWorkOutletId($loggedInUser, $employee);
        if (!$workOutletId) {
            return back()->with('error', 'Outlet perangkat tidak dikenali. Hubungi admin.');
        }

        $isCrossOutlet = (int) $employee->outlet_id !== (int) $workOutletId;

        // Validasi shift aktif
        $shift = Shift::find($request->shift_id);
        if (!$shift || !$shift->is_active) {
            return back()->with('error', 'Shift tidak valid atau tidak aktif. Hubungi admin.');
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

        // Status & menit telat dihitung lewat service supaya rumusnya sama
        // persis dengan yang dipakai saat admin mengoreksi shift. Rumus yang
        // disalin ke dua tempat akan perlahan berbeda, dan absensi terkoreksi
        // jadi menghasilkan angka lain untuk jam masuk yang sama.
        $clockInTime = now();
        $arrival = $this->metricsService->arrivalMetrics($shift, $clockInTime);
        $status = $arrival['status'];
        $lateMinutes = $arrival['late_minutes'];

        // Buat record attendance
        $attendance = Attendance::create([
            'user_id' => $employee->id,
            'logged_in_user_id' => $loggedInUser->id,
            'outlet_id' => $workOutletId,
            'home_outlet_id' => $employee->outlet_id,
            'is_cross_outlet' => $isCrossOutlet,
            'shift_id' => $shift->id,
            'clock_in' => $clockInTime,
            'status' => $status,
            'late_minutes' => $lateMinutes,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Deteksi anomali: banyak absensi dalam waktu singkat
        $this->detectAnomalies($loggedInUser->id, $workOutletId);

        if ($isCrossOutlet) {
            Log::info('Absensi lintas outlet', [
                'attendance_id' => $attendance->id,
                'employee_id' => $employee->id,
                'home_outlet_id' => $employee->outlet_id,
                'work_outlet_id' => $workOutletId,
                'logged_in_user_id' => $loggedInUser->id,
            ]);
        }

        $statusText = $status === 'late' ? "terlambat {$lateMinutes} menit" : 'tepat waktu';
        $crossText = $isCrossOutlet
            ? ' [titipan dari ' . ($employee->outlet?->name ?? 'outlet lain') . ']'
            : '';

        return back()->with('success', "Clock-in berhasil untuk {$employee->name} - Shift {$shift->name} ({$statusText}){$crossText}");
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

        $employee = User::with('outlet')->findOrFail($request->employee_id);
        $loggedInUser = auth()->user();

        if (!$employee->is_active || !$employee->attendance_pin) {
            return back()->with('error', 'Karyawan tidak aktif atau belum punya PIN absensi');
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

        // Durasi kerja, pulang cepat, dan lembur — rumusnya dipegang service
        // yang sama dengan clock-in dan koreksi shift.
        $clockOutTime = now();
        $attendance->loadMissing(['shift', 'outlet']);

        $departure = $this->metricsService->departureMetrics(
            $attendance->shift,
            $attendance->clock_in,
            $clockOutTime
        );

        // Update clock_out + metrik
        $attendance->update(array_merge($departure, [
            'clock_out' => $clockOutTime,
        ]));

        $duration = $attendance->getDurationFormatted();
        $extra = '';
        if ($earlyLeaveMinutes > 0) {
            $extra = " (pulang cepat {$earlyLeaveMinutes} menit)";
        } elseif ($overtimeMinutes > 0) {
            $extra = " (lembur {$overtimeMinutes} menit)";
        }

        // Clock-out di outlet lain dari tempat clock-in tetap diizinkan (kasir rolling),
        // tapi harus terlihat jelas supaya tidak dikira salah input.
        if ($loggedInUser->outlet_id && (int) $attendance->outlet_id !== (int) $loggedInUser->outlet_id) {
            $extra .= ' - absen masuk tercatat di ' . ($attendance->outlet?->name ?? 'outlet lain');
        }

        return back()->with('success', "Clock-out berhasil untuk {$employee->name}. Durasi kerja: {$duration}{$extra}");
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
