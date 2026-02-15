<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceReportController extends Controller
{
    /**
     * Dashboard monitoring semua outlet
     */
    public function dashboard(Request $request)
    {
        $viewData = $this->buildReportData($request);
        $viewData['isReportPage'] = false;

        return view('admin.attendance.dashboard', $viewData);
    }

    /**
     * Halaman laporan absensi di modul Laporan.
     */
    public function reportIndex(Request $request)
    {
        $viewData = $this->buildReportData($request);
        $viewData['isReportPage'] = true;

        return view('admin.attendance.dashboard', $viewData);
    }

    /**
     * Deteksi pola absensi mencurigakan
     */
    public function detectAnomalies(Carbon $dateFrom, Carbon $dateTo, ?int $outletId = null)
    {
        $anomalies = [];
        $dateRange = [$dateFrom->copy()->startOfDay(), $dateTo->copy()->endOfDay()];

        // Anomali 1: Banyak absensi dalam waktu singkat dari kasir yang sama
        $rapidClockins = DB::table('attendances')
            ->select('logged_in_user_id', 'outlet_id', DB::raw('COUNT(*) as count'), DB::raw('MIN(clock_in) as first_time'), DB::raw('MAX(clock_in) as last_time'))
            ->whereBetween('clock_in', $dateRange)
            ->when($outletId, fn ($query) => $query->where('outlet_id', $outletId))
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
            ->whereBetween('clock_in', $dateRange)
            ->when($outletId, fn ($query) => $query->where('outlet_id', $outletId))
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
        [$query, $dateFrom, $dateTo] = $this->buildFilteredAttendanceQuery($request);
        $attendances = $query->orderBy('clock_in', 'desc')->get();

        $filename = sprintf(
            'rekap-absensi-%s-sd-%s.csv',
            $dateFrom->format('Ymd'),
            $dateTo->format('Ymd')
        );

        return new StreamedResponse(function () use ($attendances) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Tanggal',
                'Karyawan',
                'Outlet',
                'Jam Masuk',
                'Jam Keluar',
                'Durasi (menit)',
                'Status',
                'Kasir Login',
                'IP Address',
            ]);

            foreach ($attendances as $attendance) {
                fputcsv($handle, [
                    optional($attendance->clock_in)->format('Y-m-d'),
                    $attendance->user?->name ?? '-',
                    $attendance->outlet?->name ?? '-',
                    optional($attendance->clock_in)->format('H:i:s'),
                    optional($attendance->clock_out)->format('H:i:s') ?? '-',
                    $attendance->isClockOut() ? $attendance->getDuration() : null,
                    $attendance->status,
                    $attendance->loggedInUser?->name ?? '-',
                    $attendance->ip_address ?? '-',
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Dismiss anomali yang sudah ditindaklanjuti
     */
    public function dismissAnomaly($id)
    {
        // TODO: Implement anomaly dismissal (simpan di tabel terpisah)
        return back()->with('success', 'Anomali telah ditandai sebagai sudah ditindaklanjuti');
    }

    private function buildReportData(Request $request): array
    {
        [$query, $dateFrom, $dateTo] = $this->buildFilteredAttendanceQuery($request);

        $attendances = (clone $query)
            ->orderBy('clock_in', 'desc')
            ->get();

        $totalPresent = $attendances->count();
        $totalLate = $attendances->where('status', 'late')->count();

        $activeEmployeeQuery = User::where('is_active', true)->whereNotNull('outlet_id');
        if ($request->filled('outlet_id')) {
            $activeEmployeeQuery->where('outlet_id', (int) $request->integer('outlet_id'));
        }
        $activeEmployeeCount = (int) $activeEmployeeQuery->count();
        $presentEmployeeCount = (int) $attendances->pluck('user_id')->unique()->count();
        $totalNotPresent = max($activeEmployeeCount - $presentEmployeeCount, 0);

        $anomalies = $this->detectAnomalies(
            $dateFrom,
            $dateTo,
            $request->filled('outlet_id') ? (int) $request->integer('outlet_id') : null
        );
        $anomalyCount = count($anomalies);

        $outletStats = $attendances
            ->groupBy('outlet_id')
            ->map(function ($rows) {
                $first = $rows->first();
                $employeeCount = $rows->pluck('user_id')->unique()->count();
                $lateCount = $rows->where('status', 'late')->count();
                $total = $rows->count();

                return [
                    'outlet_name' => $first?->outlet?->name ?? '-',
                    'total_records' => $total,
                    'unique_employees' => $employeeCount,
                    'late_count' => $lateCount,
                    'on_time_rate' => $total > 0 ? round((($total - $lateCount) / $total) * 100, 1) : 0,
                ];
            })
            ->sortByDesc('total_records')
            ->values();

        $employeeStats = $attendances
            ->groupBy('user_id')
            ->map(function ($rows) {
                $first = $rows->first();
                $lateCount = $rows->where('status', 'late')->count();
                $clockedOutRows = $rows->filter(fn ($row) => $row->isClockOut());
                $avgDuration = $clockedOutRows->count() > 0
                    ? round($clockedOutRows->map(fn ($row) => (int) $row->getDuration())->avg(), 0)
                    : null;

                return [
                    'employee_name' => $first?->user?->name ?? '-',
                    'outlet_name' => $first?->outlet?->name ?? '-',
                    'total_records' => $rows->count(),
                    'late_count' => $lateCount,
                    'on_time_rate' => $rows->count() > 0 ? round((($rows->count() - $lateCount) / $rows->count()) * 100, 1) : 0,
                    'avg_duration_minutes' => $avgDuration,
                ];
            })
            ->sortByDesc('total_records')
            ->values();

        return [
            'period' => $request->input('period', 'today'),
            'dateFrom' => $dateFrom->toDateString(),
            'dateTo' => $dateTo->toDateString(),
            'selectedOutletId' => $request->filled('outlet_id') ? (int) $request->integer('outlet_id') : null,
            'selectedUserId' => $request->filled('user_id') ? (int) $request->integer('user_id') : null,
            'selectedStatus' => $request->input('status'),
            'totalPresent' => $totalPresent,
            'totalLate' => $totalLate,
            'totalNotPresent' => $totalNotPresent,
            'anomalyCount' => $anomalyCount,
            'anomalies' => $anomalies,
            'attendances' => $attendances,
            'outletStats' => $outletStats,
            'employeeStats' => $employeeStats,
            'outlets' => Outlet::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'employees' => User::where('is_active', true)->whereNotNull('outlet_id')->orderBy('name')->get(['id', 'name']),
        ];
    }

    private function buildFilteredAttendanceQuery(Request $request): array
    {
        $period = $request->input('period', 'today');
        $dateFrom = today();
        $dateTo = today();

        if ($period === 'week') {
            $dateFrom = now()->startOfWeek();
            $dateTo = now()->endOfWeek();
        } elseif ($period === 'month') {
            $dateFrom = now()->startOfMonth();
            $dateTo = now()->endOfMonth();
        } elseif ($period === 'custom') {
            $dateFrom = Carbon::parse($request->input('date_from', now()->toDateString()))->startOfDay();
            $dateTo = Carbon::parse($request->input('date_to', now()->toDateString()))->endOfDay();
        }

        $query = Attendance::with(['user', 'loggedInUser', 'outlet'])
            ->whereBetween('clock_in', [$dateFrom->copy()->startOfDay(), $dateTo->copy()->endOfDay()])
            ->when($request->filled('outlet_id'), fn ($q) => $q->where('outlet_id', (int) $request->integer('outlet_id')))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', (int) $request->integer('user_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')));

        return [$query, $dateFrom instanceof Carbon ? $dateFrom->copy() : Carbon::parse($dateFrom), $dateTo instanceof Carbon ? $dateTo->copy() : Carbon::parse($dateTo)];
    }
}
