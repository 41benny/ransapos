<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Outlet;
use App\Models\User;
use App\Support\ReportExport;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceReportController extends Controller
{
    /**
     * Halaman laporan absensi di modul Laporan.
     */
    public function reportIndex(Request $request)
    {
        return view('admin.attendance.dashboard', $this->buildReportData($request));
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

    public function exportReport(Request $request)
    {
        [$query, $dateFrom, $dateTo] = $this->buildFilteredAttendanceQuery($request);
        $attendances = $query->orderBy('clock_in', 'desc')->get();
        $rows = $attendances->map(function ($attendance) {
            return [
                'tanggal' => optional($attendance->clock_in)->format('Y-m-d'),
                'karyawan' => $attendance->user?->name ?? '-',
                'outlet' => $attendance->outlet?->name ?? '-',
                'shift' => $attendance->shift?->name ?? '-',
                'jam_masuk' => optional($attendance->clock_in)->format('H:i:s'),
                'jam_keluar' => optional($attendance->clock_out)->format('H:i:s') ?? '-',
                'durasi_menit' => $attendance->isClockOut() ? (int) $attendance->getDuration() : 0,
                'telat_menit' => (int) $attendance->late_minutes,
                'pulang_cepat_menit' => (int) $attendance->early_leave_minutes,
                'lembur_menit' => (int) $attendance->overtime_minutes,
                'status' => $attendance->status,
                'kasir_login' => $attendance->loggedInUser?->name ?? '-',
                'ip_address' => $attendance->ip_address ?? '-',
            ];
        })->all();

        $columns = [
            ['key' => 'tanggal', 'label' => 'Tanggal', 'type' => 'text'],
            ['key' => 'karyawan', 'label' => 'Karyawan', 'type' => 'text'],
            ['key' => 'outlet', 'label' => 'Outlet', 'type' => 'text'],
            ['key' => 'shift', 'label' => 'Shift', 'type' => 'text'],
            ['key' => 'jam_masuk', 'label' => 'Jam Masuk', 'type' => 'text'],
            ['key' => 'jam_keluar', 'label' => 'Jam Keluar', 'type' => 'text'],
            ['key' => 'durasi_menit', 'label' => 'Durasi (menit)', 'type' => 'number'],
            ['key' => 'telat_menit', 'label' => 'Telat (menit)', 'type' => 'number'],
            ['key' => 'pulang_cepat_menit', 'label' => 'Pulang Cepat (menit)', 'type' => 'number'],
            ['key' => 'lembur_menit', 'label' => 'Lembur (menit)', 'type' => 'number'],
            ['key' => 'status', 'label' => 'Status', 'type' => 'text'],
            ['key' => 'kasir_login', 'label' => 'Kasir Login', 'type' => 'text'],
            ['key' => 'ip_address', 'label' => 'IP Address', 'type' => 'text'],
        ];

        $format = $request->input('format', 'xlsx');
        $baseFilename = sprintf('rekap-absensi-%s-sd-%s', $dateFrom->format('Ymd'), $dateTo->format('Ymd'));

        if ($format === 'pdf') {
            return ReportExport::pdf($baseFilename . '.pdf', 'Rekap Absensi Karyawan', $columns, $rows);
        }

        return ReportExport::xlsx($baseFilename . '.xlsx', 'Rekap Absensi', $columns, $rows);
    }

    private function buildReportData(Request $request): array
    {
        [$detailQuery, $dateFrom, $dateTo] = $this->buildFilteredAttendanceQuery($request);
        [$statsQuery] = $this->buildFilteredAttendanceQuery($request, false);

        $attendances = (clone $detailQuery)
            ->orderBy('clock_in', 'desc')
            ->paginate(100, ['*'], 'attendance_page')
            ->withQueryString();

        $summary = (clone $statsQuery)
            ->selectRaw('COUNT(*) as total_present')
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END), 0) as total_late")
            ->selectRaw('COALESCE(SUM(worked_minutes), 0) as total_worked_minutes')
            ->selectRaw('COALESCE(SUM(overtime_minutes), 0) as total_overtime_minutes')
            ->selectRaw('COALESCE(SUM(late_minutes), 0) as total_late_minutes')
            ->selectRaw('COALESCE(SUM(CASE WHEN early_leave_minutes > 0 THEN 1 ELSE 0 END), 0) as total_early_leave')
            ->first();

        $totalPresent = (int) ($summary->total_present ?? 0);
        $totalLate = (int) ($summary->total_late ?? 0);
        $totalWorkedMinutes = (int) ($summary->total_worked_minutes ?? 0);
        $totalOvertimeMinutes = (int) ($summary->total_overtime_minutes ?? 0);
        $totalLateMinutes = (int) ($summary->total_late_minutes ?? 0);
        $totalEarlyLeave = (int) ($summary->total_early_leave ?? 0);

        $activeEmployeeQuery = User::where('is_active', true)->whereNotNull('outlet_id');
        if ($request->filled('outlet_id')) {
            $activeEmployeeQuery->where('outlet_id', (int) $request->integer('outlet_id'));
        }
        $activeEmployeeCount = (int) $activeEmployeeQuery->count();
        $presentEmployeeCount = (int) (clone $statsQuery)->distinct()->count('user_id');
        $totalNotPresent = max($activeEmployeeCount - $presentEmployeeCount, 0);

        $anomalies = $this->detectAnomalies(
            $dateFrom,
            $dateTo,
            $request->filled('outlet_id') ? (int) $request->integer('outlet_id') : null
        );
        $anomalyCount = count($anomalies);

        $outletStats = (clone $statsQuery)
            ->leftJoin('outlets', 'attendances.outlet_id', '=', 'outlets.id')
            ->selectRaw('attendances.outlet_id')
            ->selectRaw('outlets.name as outlet_name')
            ->selectRaw('COUNT(*) as total_records')
            ->selectRaw('COUNT(DISTINCT attendances.user_id) as unique_employees')
            ->selectRaw("COALESCE(SUM(CASE WHEN attendances.status = 'late' THEN 1 ELSE 0 END), 0) as late_count")
            ->groupBy('attendances.outlet_id', 'outlets.name')
            ->orderByDesc('total_records')
            ->paginate(20, ['*'], 'outlets_page')
            ->withQueryString();

        $outletStats->setCollection(
            $outletStats->getCollection()->map(function ($row) {
                $totalRecords = (int) ($row->total_records ?? 0);
                $lateCount = (int) ($row->late_count ?? 0);

                return [
                    'outlet_name' => $row->outlet_name ?? '-',
                    'total_records' => $totalRecords,
                    'unique_employees' => (int) ($row->unique_employees ?? 0),
                    'late_count' => $lateCount,
                    'on_time_rate' => $totalRecords > 0 ? round((($totalRecords - $lateCount) / $totalRecords) * 100, 1) : 0,
                ];
            })
        );

        $employeeStats = (clone $statsQuery)
            ->leftJoin('users', 'attendances.user_id', '=', 'users.id')
            ->leftJoin('outlets', 'attendances.outlet_id', '=', 'outlets.id')
            ->selectRaw('attendances.user_id')
            ->selectRaw('users.name as employee_name')
            ->selectRaw('outlets.name as outlet_name')
            ->selectRaw('COUNT(*) as total_records')
            ->selectRaw("COALESCE(SUM(CASE WHEN attendances.status = 'late' THEN 1 ELSE 0 END), 0) as late_count")
            ->selectRaw("COALESCE(SUM(CASE WHEN attendances.clock_out IS NOT NULL THEN 1 ELSE 0 END), 0) as clocked_out_count")
            ->selectRaw('COALESCE(SUM(attendances.worked_minutes), 0) as worked_minutes')
            ->selectRaw('COALESCE(SUM(attendances.overtime_minutes), 0) as overtime_minutes')
            ->selectRaw('COALESCE(SUM(attendances.late_minutes), 0) as late_minutes_sum')
            ->selectRaw($this->averageAttendanceDurationExpression() . ' as avg_duration_minutes')
            ->groupBy('attendances.user_id', 'users.name', 'outlets.name')
            ->orderByDesc('total_records')
            ->paginate(20, ['*'], 'employees_page')
            ->withQueryString();

        $employeeStats->setCollection(
            $employeeStats->getCollection()->map(function ($row) {
                $totalRecords = (int) ($row->total_records ?? 0);
                $lateCount = (int) ($row->late_count ?? 0);
                $clockedOutCount = (int) ($row->clocked_out_count ?? 0);

                return [
                    'employee_name' => $row->employee_name ?? '-',
                    'outlet_name' => $row->outlet_name ?? '-',
                    'total_records' => $totalRecords,
                    'late_count' => $lateCount,
                    'on_time_rate' => $totalRecords > 0 ? round((($totalRecords - $lateCount) / $totalRecords) * 100, 1) : 0,
                    'avg_duration_minutes' => $clockedOutCount > 0 ? (int) round((float) ($row->avg_duration_minutes ?? 0)) : null,
                    'worked_minutes' => (int) ($row->worked_minutes ?? 0),
                    'overtime_minutes' => (int) ($row->overtime_minutes ?? 0),
                    'late_minutes' => (int) ($row->late_minutes_sum ?? 0),
                ];
            })
        );

        // Tren kehadiran per hari (hadir vs telat) untuk grafik
        $trend = (clone $statsQuery)
            ->selectRaw('DATE(clock_in) as date')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END), 0) as late_count")
            ->groupByRaw('DATE(clock_in)')
            ->orderByRaw('DATE(clock_in)')
            ->get()
            ->map(fn ($r) => [
                'date' => (string) $r->date,
                'present' => max(0, (int) $r->total - (int) $r->late_count),
                'late' => (int) $r->late_count,
            ])
            ->values();

        // Ranking kedisiplinan (paling disiplin & paling sering telat)
        $disciplineBase = (clone $statsQuery)
            ->leftJoin('users', 'attendances.user_id', '=', 'users.id')
            ->selectRaw('attendances.user_id')
            ->selectRaw('users.name as employee_name')
            ->selectRaw('COUNT(*) as total_records')
            ->selectRaw("COALESCE(SUM(CASE WHEN attendances.status = 'late' THEN 1 ELSE 0 END), 0) as late_count")
            ->selectRaw('COALESCE(SUM(attendances.late_minutes), 0) as late_minutes_sum')
            ->groupBy('attendances.user_id', 'users.name')
            ->get()
            ->map(function ($row) {
                $totalRecords = (int) ($row->total_records ?? 0);
                $lateCount = (int) ($row->late_count ?? 0);

                return [
                    'employee_name' => $row->employee_name ?? '-',
                    'total_records' => $totalRecords,
                    'late_count' => $lateCount,
                    'late_minutes' => (int) ($row->late_minutes_sum ?? 0),
                    'on_time_rate' => $totalRecords > 0 ? round((($totalRecords - $lateCount) / $totalRecords) * 100, 1) : 0,
                ];
            });

        $disciplineTop = $disciplineBase
            ->sortByDesc('on_time_rate')
            ->take(5)
            ->values();

        $mostLate = $disciplineBase
            ->filter(fn ($row) => $row['late_count'] > 0)
            ->sortByDesc('late_count')
            ->take(5)
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
            'totalWorkedMinutes' => $totalWorkedMinutes,
            'totalOvertimeMinutes' => $totalOvertimeMinutes,
            'totalLateMinutes' => $totalLateMinutes,
            'totalEarlyLeave' => $totalEarlyLeave,
            'anomalyCount' => $anomalyCount,
            'anomalies' => $anomalies,
            'trend' => $trend,
            'disciplineTop' => $disciplineTop,
            'mostLate' => $mostLate,
            'attendances' => $attendances,
            'outletStats' => $outletStats,
            'employeeStats' => $employeeStats,
            'outlets' => Outlet::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'employees' => User::where('is_active', true)->whereNotNull('outlet_id')->orderBy('name')->get(['id', 'name']),
        ];
    }

    private function buildFilteredAttendanceQuery(Request $request, bool $withRelations = true): array
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

        // Kolom dikualifikasi dengan nama tabel agar tidak ambigu saat query
        // di-join ke tabel `users` (yang juga punya kolom outlet_id) untuk rekap.
        $query = Attendance::query()
            ->when($withRelations, fn ($q) => $q->with(['user', 'loggedInUser', 'outlet', 'shift']))
            ->whereBetween('attendances.clock_in', [$dateFrom->copy()->startOfDay(), $dateTo->copy()->endOfDay()])
            ->when($request->filled('outlet_id'), fn ($q) => $q->where('attendances.outlet_id', (int) $request->integer('outlet_id')))
            ->when($request->filled('user_id'), fn ($q) => $q->where('attendances.user_id', (int) $request->integer('user_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('attendances.status', $request->input('status')));

        return [$query, $dateFrom instanceof Carbon ? $dateFrom->copy() : Carbon::parse($dateFrom), $dateTo instanceof Carbon ? $dateTo->copy() : Carbon::parse($dateTo)];
    }

    private function averageAttendanceDurationExpression(): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return "AVG(CASE WHEN attendances.clock_out IS NOT NULL THEN (julianday(attendances.clock_out) - julianday(attendances.clock_in)) * 24 * 60 END)";
        }

        return "AVG(CASE WHEN attendances.clock_out IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, attendances.clock_in, attendances.clock_out) END)";
    }
}
