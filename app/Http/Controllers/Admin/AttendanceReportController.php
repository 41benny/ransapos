<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceReportController extends Controller
{
    /**
     * Dashboard monitoring semua outlet
     */
    public function dashboard()
    {
        $today = today();

        // Overview stats
        $totalPresent = Attendance::whereDate('clock_in', $today)->count();
        $totalLate = Attendance::whereDate('clock_in', $today)->where('status', 'late')->count();

        // Total karyawan aktif
        $totalEmployees = User::where('is_active', true)->whereNotNull('outlet_id')->count();
        $totalNotPresent = $totalEmployees - $totalPresent;

        // Deteksi anomali hari ini
        $anomalies = $this->detectAnomalies();
        $anomalyCount = count($anomalies);

        // Real-time attendance hari ini (semua outlet)
        $attendances = Attendance::with(['user', 'loggedInUser', 'outlet'])
            ->whereDate('clock_in', $today)
            ->orderBy('clock_in', 'desc')
            ->get();

        // Laporan per outlet
        $outlets = Outlet::withCount(['users as present_count' => function ($query) use ($today) {
            $query->whereHas('attendances', function ($q) use ($today) {
                $q->whereDate('clock_in', $today);
            });
        }])
            ->withCount(['users as active_employees' => function ($query) {
                $query->where('is_active', true);
            }])
            ->get();

        return view('admin.attendance.dashboard', compact(
            'totalPresent',
            'totalLate',
            'totalNotPresent',
            'anomalyCount',
            'anomalies',
            'attendances',
            'outlets'
        ));
    }

    /**
     * Deteksi pola absensi mencurigakan
     */
    public function detectAnomalies()
    {
        $anomalies = [];
        $today = today();

        // Anomali 1: Banyak absensi dalam waktu singkat dari kasir yang sama
        $rapidClockins = DB::table('attendances')
            ->select('logged_in_user_id', 'outlet_id', DB::raw('COUNT(*) as count'), DB::raw('MIN(clock_in) as first_time'), DB::raw('MAX(clock_in) as last_time'))
            ->whereDate('clock_in', $today)
            ->groupBy('logged_in_user_id', 'outlet_id')
            ->having('count', '>', 3)
            ->get();

        foreach ($rapidClockins as $rapid) {
            $firstTime = Carbon::parse($rapid->first_time);
            $lastTime = Carbon::parse($rapid->last_time);
            $diffMinutes = $firstTime->diffInMinutes($lastTime);

            if ($diffMinutes <= 2) {
                $cashier = User::find($rapid->logged_in_user_id);
                $outlet = Outlet::find($rapid->outlet_id);

                $anomalies[] = [
                    'type' => 'rapid_clockins',
                    'severity' => 'high',
                    'message' => "{$rapid->count} karyawan di {$outlet->name} absen dalam {$diffMinutes} menit (Kasir: {$cashier->name}, " . $firstTime->format('H:i') . " - " . $lastTime->format('H:i') . ")",
                    'time' => $firstTime,
                    'data' => $rapid
                ];
            }
        }

        // Anomali 2: Semua karyawan clock-in di waktu yang sama persis
        $exactSameTime = DB::table('attendances')
            ->select('outlet_id', 'clock_in', DB::raw('COUNT(*) as count'))
            ->whereDate('clock_in', $today)
            ->groupBy('outlet_id', 'clock_in')
            ->having('count', '>', 2)
            ->get();

        foreach ($exactSameTime as $same) {
            $outlet = Outlet::find($same->outlet_id);
            $time = Carbon::parse($same->clock_in);

            $anomalies[] = [
                'type' => 'exact_same_time',
                'severity' => 'medium',
                'message' => "{$same->count} karyawan di {$outlet->name} absen di waktu yang sama persis ({$time->format('H:i:s')})",
                'time' => $time,
                'data' => $same
            ];
        }

        return $anomalies;
    }

    /**
     * Laporan per outlet
     */
    public function outletReport($outletId)
    {
        $outlet = Outlet::findOrFail($outletId);
        $period = request('period', 'today'); // today, week, month

        $query = Attendance::with(['user'])
            ->where('outlet_id', $outletId);

        switch ($period) {
            case 'week':
                $query->whereBetween('clock_in', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereBetween('clock_in', [now()->startOfMonth(), now()->endOfMonth()]);
                break;
            default:
                $query->whereDate('clock_in', today());
        }

        $attendances = $query->orderBy('clock_in', 'desc')->get();

        // Statistik
        $stats = [
            'total' => $attendances->count(),
            'present' => $attendances->where('status', 'present')->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'on_time_rate' => $attendances->count() > 0
                ? round(($attendances->where('status', 'present')->count() / $attendances->count()) * 100, 1)
                : 0
        ];

        return view('admin.attendance.outlet-report', compact('outlet', 'attendances', 'stats', 'period'));
    }

    /**
     * Export laporan ke Excel
     */
    public function exportReport(Request $request)
    {
        // TODO: Implement Excel export menggunakan Laravel Excel
        return back()->with('info', 'Fitur export akan segera tersedia');
    }

    /**
     * Dismiss anomali yang sudah ditindaklanjuti
     */
    public function dismissAnomaly($id)
    {
        // TODO: Implement anomaly dismissal (simpan di tabel terpisah)
        return back()->with('success', 'Anomali telah ditandai sebagai sudah ditindaklanjuti');
    }
}
