<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Models\CashSession;
use App\Models\Outlet;
use App\Models\PosDevice;
use App\Models\User;
use App\Support\ReportExport;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ShiftReportController extends Controller
{
    /**
     * Laporan shift kasir (summary per shift).
     * Basis periode: aktivitas shift (dibuka/ditutup/ada penjualan di periode).
     */
    public function index(Request $request)
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);
        $viewMode = in_array($request->input('view_mode'), ['ringkas', 'detail'], true)
            ? $request->input('view_mode')
            : 'ringkas';

        $query = $this->buildSessionQuery($request, $dateFrom, $dateTo);
        $sessions = $this->decorateSessions($query->orderBy('opened_at', 'desc')->get());

        $totals = [
            'total_shifts' => $sessions->count(),
            'total_sales' => $sessions->sum('total_sales'),
            'total_sales_period' => $sessions->sum('period_sales_total'),
            'total_sales_shift' => $sessions->sum('total_sales'),
            'total_outside_period' => $sessions->sum('outside_period_sales'),
            'total_difference' => $sessions->where('status', 'closed')->sum('difference'),
            'shifts_with_shortage' => $sessions->where('status', 'closed')->where('difference', '<', 0)->count(),
            'shifts_with_overage' => $sessions->where('status', 'closed')->where('difference', '>', 0)->count(),
            'shifts_with_anomaly' => $sessions->where('anomaly_count', '>', 0)->count(),
        ];

        $outlets = Outlet::where('is_active', true)->get();
        $users = User::whereHas('role', function ($query) {
            $query->whereIn('name', ['kasir', 'admin']);
        })->get();

        $filters = $request->only(['date_from', 'date_to', 'outlet_id', 'user_id', 'status', 'view_mode']);

        return view('admin.reports.shifts.index', compact(
            'sessions',
            'totals',
            'outlets',
            'users',
            'filters',
            'viewMode',
            'dateFrom',
            'dateTo'
        ));
    }

    /**
     * Detail satu shift kasir.
     */
    public function show(CashSession $cashSession)
    {
        $cashSession->load([
            'outlet',
            'user',
            'openedDevice',
            'closedDevice',
            'sales' => function ($query) {
                $query->where('status', 'completed')
                    ->with(['payments.paymentMethod', 'items.product']);
            },
        ]);

        $paymentBreakdown = DB::table('payments')
            ->join('payment_methods', 'payments.payment_method_id', '=', 'payment_methods.id')
            ->join('sales', 'payments.sale_id', '=', 'sales.id')
            ->where('sales.cash_session_id', $cashSession->id)
            ->where('sales.status', 'completed')
            ->select(
                'payment_methods.name as method_name',
                DB::raw('SUM(payments.amount) as total_amount'),
                DB::raw('COUNT(DISTINCT sales.id) as transaction_count')
            )
            ->groupBy('payment_methods.id', 'payment_methods.name')
            ->get();

        $topProducts = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.cash_session_id', $cashSession->id)
            ->where('sales.status', 'completed')
            ->select(
                'sale_items.product_name',
                'sale_items.product_sku',
                DB::raw('SUM(sale_items.quantity) as total_qty'),
                DB::raw('SUM(sale_items.subtotal) as total_amount')
            )
            ->groupBy('sale_items.product_id', 'sale_items.product_name', 'sale_items.product_sku')
            ->orderBy('total_amount', 'desc')
            ->limit(10)
            ->get();

        $sessionDiagnostics = $this->buildSessionDiagnostics($cashSession);

        return view('admin.reports.shifts.show', compact(
            'cashSession',
            'paymentBreakdown',
            'topProducts',
            'sessionDiagnostics'
        ));
    }

    public function exportIndex(Request $request)
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);

        $sessions = $this->decorateSessions(
            $this->buildSessionQuery($request, $dateFrom, $dateTo)
                ->orderBy('opened_at', 'desc')
                ->get()
        );

        $rows = $sessions->map(function (CashSession $session) {
            return [
                'session_number' => $session->session_number,
                'outlet' => $session->outlet?->name ?? '-',
                'kasir' => $session->user?->name ?? '-',
                'dibuka' => optional($session->opened_at)->format('Y-m-d H:i:s'),
                'ditutup' => optional($session->closed_at)->format('Y-m-d H:i:s') ?? '-',
                'rentang_tanggal_transaksi' => $session->sale_date_range_label,
                'omzet_periode' => (float) $session->period_sales_total,
                'omzet_total_shift' => (float) $session->total_sales,
                'selisih_luar_periode' => (float) $session->outside_period_sales,
                'durasi_menit' => $session->duration_minutes,
                'perangkat_buka' => $session->opened_device_name,
                'perangkat_tutup' => $session->closed_device_name,
                'indikator' => implode('; ', $session->anomaly_notes),
                'selisih' => (float) ($session->difference ?? 0),
                'status' => $session->status,
            ];
        })->all();

        $columns = [
            ['key' => 'session_number', 'label' => 'Session Number', 'type' => 'text'],
            ['key' => 'outlet', 'label' => 'Outlet', 'type' => 'text'],
            ['key' => 'kasir', 'label' => 'Kasir', 'type' => 'text'],
            ['key' => 'dibuka', 'label' => 'Dibuka', 'type' => 'text'],
            ['key' => 'ditutup', 'label' => 'Ditutup', 'type' => 'text'],
            ['key' => 'rentang_tanggal_transaksi', 'label' => 'Rentang Tanggal Transaksi', 'type' => 'text'],
            ['key' => 'omzet_periode', 'label' => 'Omzet Periode', 'type' => 'number', 'decimals' => 2],
            ['key' => 'omzet_total_shift', 'label' => 'Omzet Total Shift', 'type' => 'number', 'decimals' => 2],
            ['key' => 'selisih_luar_periode', 'label' => 'Selisih Luar Periode', 'type' => 'number', 'decimals' => 2],
            ['key' => 'durasi_menit', 'label' => 'Durasi (menit)', 'type' => 'number', 'decimals' => 0],
            ['key' => 'perangkat_buka', 'label' => 'Perangkat Buka', 'type' => 'text'],
            ['key' => 'perangkat_tutup', 'label' => 'Perangkat Tutup', 'type' => 'text'],
            ['key' => 'indikator', 'label' => 'Indikator', 'type' => 'text'],
            ['key' => 'selisih', 'label' => 'Selisih Kas', 'type' => 'number', 'decimals' => 2],
            ['key' => 'status', 'label' => 'Status', 'type' => 'text'],
        ];

        $format = $request->input('format', 'xlsx');
        $filename = sprintf('laporan-shift-%s-sd-%s.%s', str_replace('-', '', $dateFrom), str_replace('-', '', $dateTo), $format);

        if ($format === 'pdf') {
            return ReportExport::pdf($filename, 'Laporan Shift Kasir', $columns, $rows);
        }

        return ReportExport::xlsx($filename, 'Laporan Shift', $columns, $rows);
    }

    public function exportShow(Request $request, CashSession $cashSession)
    {
        $cashSession->load([
            'outlet',
            'user',
            'sales' => function ($query) {
                $query->where('status', 'completed')
                    ->with(['payments.paymentMethod', 'items.product']);
            },
        ]);

        $rows = $cashSession->sales->map(function ($sale) {
            return [
                'no_transaksi' => $sale->invoice_number,
                'tanggal' => optional($sale->sale_date)->format('Y-m-d'),
                'customer' => $sale->customer_name ?? '-',
                'total' => (float) $sale->total_amount,
                'payment' => $sale->payments->map(fn ($payment) => $payment->paymentMethod?->name)->filter()->unique()->implode(', '),
            ];
        })->all();

        $columns = [
            ['key' => 'no_transaksi', 'label' => 'No Transaksi', 'type' => 'text'],
            ['key' => 'tanggal', 'label' => 'Tanggal', 'type' => 'text'],
            ['key' => 'customer', 'label' => 'Customer', 'type' => 'text'],
            ['key' => 'total', 'label' => 'Total', 'type' => 'number', 'decimals' => 2],
            ['key' => 'payment', 'label' => 'Metode Bayar', 'type' => 'text'],
        ];

        $format = $request->input('format', 'xlsx');
        $filename = sprintf('detail-shift-%s.%s', $cashSession->session_number, $format);

        if ($format === 'pdf') {
            return ReportExport::pdf($filename, 'Detail Shift Kasir ' . $cashSession->session_number, $columns, $rows);
        }

        return ReportExport::xlsx($filename, 'Detail Shift', $columns, $rows);
    }

    private function resolveDateRange(Request $request): array
    {
        $dateFrom = $request->input('date_from', now()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        return [$dateFrom, $dateTo];
    }

    private function buildSessionQuery(Request $request, string $dateFrom, string $dateTo): Builder
    {
        $openedFrom = $dateFrom . ' 00:00:00';
        $openedTo = $dateTo . ' 23:59:59';

        $query = CashSession::query()
            ->with(['outlet', 'user', 'openedDevice', 'closedDevice'])
            ->withSum([
                'sales as period_sales_total' => function ($query) use ($dateFrom, $dateTo) {
                    $query->where('status', 'completed')
                        ->whereBetween('sale_date', [$dateFrom, $dateTo]);
                },
            ], 'total_amount')
            ->withSum([
                'sales as completed_sales_total' => function ($query) {
                    $query->where('status', 'completed');
                },
            ], 'total_amount')
            ->withMin([
                'sales as first_sale_date' => function ($query) {
                    $query->where('status', 'completed');
                },
            ], 'sale_date')
            ->withMax([
                'sales as last_sale_date' => function ($query) {
                    $query->where('status', 'completed');
                },
            ], 'sale_date')
            ->withCount([
                'sales as completed_sales_count' => function ($query) {
                    $query->where('status', 'completed');
                },
            ])
            ->where(function (Builder $query) use ($openedFrom, $openedTo, $dateFrom, $dateTo) {
                $query->whereBetween('opened_at', [$openedFrom, $openedTo])
                    ->orWhereBetween('closed_at', [$openedFrom, $openedTo])
                    ->orWhereExists(function ($subQuery) use ($dateFrom, $dateTo) {
                        $subQuery->select(DB::raw(1))
                            ->from('sales')
                            ->whereColumn('sales.cash_session_id', 'cash_sessions.id')
                            ->where('sales.status', 'completed')
                            ->whereBetween('sales.sale_date', [$dateFrom, $dateTo]);
                    });
            });

        if ($request->filled('outlet_id')) {
            $query->where('outlet_id', $request->outlet_id);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return $query;
    }

    private function decorateSessions(Collection $sessions): Collection
    {
        return $sessions->map(function (CashSession $session) {
            $periodSalesTotal = (float) ($session->period_sales_total ?? 0);
            $outsidePeriodSales = (float) $session->total_sales - $periodSalesTotal;
            $completedSalesTotal = (float) ($session->completed_sales_total ?? 0);

            $durationMinutes = null;
            if ($session->opened_at) {
                $durationMinutes = (int) $session->opened_at->diffInMinutes($session->closed_at ?? now(), false);
            }

            $firstSaleDate = $session->first_sale_date ? Carbon::parse($session->first_sale_date)->toDateString() : null;
            $lastSaleDate = $session->last_sale_date ? Carbon::parse($session->last_sale_date)->toDateString() : null;

            $session->period_sales_total = $periodSalesTotal;
            $session->completed_sales_total = $completedSalesTotal;
            $session->outside_period_sales = $outsidePeriodSales;
            $session->duration_minutes = $durationMinutes;
            $session->sale_date_range_label = $this->saleDateRangeLabel($firstSaleDate, $lastSaleDate);
            $session->opened_device_name = $this->deviceLabel($session->openedDevice, $session->opened_pos_device_id);
            $session->closed_device_name = $this->deviceLabel($session->closedDevice, $session->closed_pos_device_id);
            $session->anomaly_notes = $this->buildAnomalyNotes(
                $session,
                $outsidePeriodSales,
                $durationMinutes,
                $firstSaleDate,
                $lastSaleDate
            );
            $session->anomaly_count = count($session->anomaly_notes);

            return $session;
        });
    }

    private function buildSessionDiagnostics(CashSession $cashSession): array
    {
        $firstSaleDate = $cashSession->sales->min('sale_date');
        $lastSaleDate = $cashSession->sales->max('sale_date');
        $totalFromSales = (float) $cashSession->sales->sum('total_amount');
        $totalFromSession = (float) $cashSession->total_sales;
        $durationMinutes = $cashSession->opened_at
            ? (int) $cashSession->opened_at->diffInMinutes($cashSession->closed_at ?? now(), false)
            : null;

        return [
            'first_sale_date' => $firstSaleDate ? Carbon::parse($firstSaleDate)->toDateString() : null,
            'last_sale_date' => $lastSaleDate ? Carbon::parse($lastSaleDate)->toDateString() : null,
            'sale_date_range_label' => $this->saleDateRangeLabel(
                $firstSaleDate ? Carbon::parse($firstSaleDate)->toDateString() : null,
                $lastSaleDate ? Carbon::parse($lastSaleDate)->toDateString() : null
            ),
            'duration_minutes' => $durationMinutes,
            'session_total_sales' => $totalFromSession,
            'sales_total_sum' => $totalFromSales,
            'delta_total_vs_sum' => $totalFromSession - $totalFromSales,
            'device_mismatch' => $cashSession->opened_pos_device_id
                && $cashSession->closed_pos_device_id
                && (int) $cashSession->opened_pos_device_id !== (int) $cashSession->closed_pos_device_id,
            'opened_device_name' => $this->deviceLabel($cashSession->openedDevice, $cashSession->opened_pos_device_id),
            'closed_device_name' => $this->deviceLabel($cashSession->closedDevice, $cashSession->closed_pos_device_id),
        ];
    }

    private function buildAnomalyNotes(
        CashSession $session,
        float $outsidePeriodSales,
        ?int $durationMinutes,
        ?string $firstSaleDate,
        ?string $lastSaleDate
    ): array {
        $notes = [];

        if ($outsidePeriodSales > 0.009) {
            $notes[] = 'Bawa omzet luar periode Rp ' . number_format($outsidePeriodSales, 0, ',', '.');
        }

        if ($firstSaleDate && $lastSaleDate && $firstSaleDate !== $lastSaleDate) {
            $notes[] = 'Transaksi lintas tanggal';
        }

        if ($durationMinutes !== null && $durationMinutes < 0) {
            $notes[] = 'Waktu tutup lebih awal dari buka';
        } elseif ($durationMinutes !== null && $durationMinutes <= 1 && (float) $session->total_sales > 0) {
            $notes[] = 'Durasi shift <= 1 menit dengan omzet';
        }

        if ($session->status === 'open' && $session->opened_at && $session->opened_at->lt(now()->startOfDay())) {
            $notes[] = 'Shift masih open dari hari sebelumnya';
        }

        if ($session->opened_pos_device_id && $session->closed_pos_device_id
            && (int) $session->opened_pos_device_id !== (int) $session->closed_pos_device_id) {
            $notes[] = 'Buka/tutup dilakukan di perangkat berbeda';
        }

        return $notes;
    }

    private function saleDateRangeLabel(?string $firstSaleDate, ?string $lastSaleDate): string
    {
        if (!$firstSaleDate || !$lastSaleDate) {
            return '-';
        }

        if ($firstSaleDate === $lastSaleDate) {
            return $firstSaleDate;
        }

        return $firstSaleDate . ' s/d ' . $lastSaleDate;
    }

    private function deviceLabel(?PosDevice $device, ?int $deviceId): string
    {
        if ($device && !empty($device->name)) {
            return $device->name;
        }

        if ($deviceId) {
            return 'Device #' . $deviceId;
        }

        return '-';
    }
}
