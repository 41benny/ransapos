<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Services\ProfitLossReportService;
use App\Models\Outlet;
use App\Support\ReportExport;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ProfitLossReportController extends Controller
{
    protected ProfitLossReportService $reportService;

    public function __construct(ProfitLossReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index(Request $request)
    {
        // Default date range: bulan ini
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->endOfMonth()->format('Y-m-d'));
        $outlets = Outlet::active()->orderBy('name')->get();
        $outletIds = $this->resolveOutletIds($request, $outlets);

        // Generate report
        $report = $this->reportService->generate($dateFrom, $dateTo, $outletIds);

        return view('admin.reports.profit-loss', compact('report', 'outlets', 'dateFrom', 'dateTo', 'outletIds'));
    }

    public function export(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->endOfMonth()->format('Y-m-d'));
        $outlets = Outlet::active()->pluck('id');
        $outletIds = $this->resolveOutletIds($request, $outlets);

        $report = $this->reportService->generate($dateFrom, $dateTo, $outletIds);

        $rows = [
            ['metric' => 'Total Pendapatan', 'amount' => (float) ($report['total_revenue'] ?? 0)],
            ['metric' => 'HPP', 'amount' => (float) ($report['total_cogs'] ?? 0)],
            ['metric' => 'Laba Kotor', 'amount' => (float) ($report['gross_profit'] ?? 0)],
            ['metric' => 'Total Biaya Operasional', 'amount' => (float) ($report['total_expenses'] ?? 0)],
            ['metric' => 'Laba Bersih', 'amount' => (float) ($report['net_profit'] ?? 0)],
            ['metric' => 'Margin Laba Bersih (%)', 'amount' => (float) ($report['net_profit_margin'] ?? 0)],
        ];

        $columns = [
            ['key' => 'metric', 'label' => 'Keterangan', 'type' => 'text'],
            ['key' => 'amount', 'label' => 'Nilai', 'type' => 'number', 'decimals' => 2],
        ];

        $format = $request->input('format', 'xlsx');
        $filename = sprintf('laporan-laba-rugi-%s-sd-%s.%s', str_replace('-', '', $dateFrom), str_replace('-', '', $dateTo), $format);

        if ($format === 'pdf') {
            return ReportExport::pdf($filename, 'Laporan Laba Rugi', $columns, $rows);
        }

        return ReportExport::xlsx($filename, 'Laba Rugi', $columns, $rows);
    }

    private function resolveOutletIds(Request $request, Collection $availableOutletIds): array
    {
        $allowedOutletIds = $availableOutletIds
            ->map(fn ($id) => (int) (is_object($id) ? $id->id : $id))
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($request->has('outlet_ids')) {
            $requestedOutletIds = collect($request->input('outlet_ids', []))
                ->filter(fn ($id) => $id !== null && $id !== '')
                ->map(fn ($id) => is_numeric($id) ? (int) $id : null)
                ->filter(fn ($id) => is_int($id) && $id > 0)
                ->unique()
                ->values()
                ->all();

            if (empty($requestedOutletIds)) {
                return $allowedOutletIds;
            }

            $resolvedOutletIds = array_values(array_intersect($requestedOutletIds, $allowedOutletIds));

            return !empty($resolvedOutletIds) ? $resolvedOutletIds : $allowedOutletIds;
        }

        if ($request->filled('outlet_id') && is_numeric($request->input('outlet_id'))) {
            $outletId = (int) $request->input('outlet_id');

            return in_array($outletId, $allowedOutletIds, true)
                ? [$outletId]
                : $allowedOutletIds;
        }

        return $allowedOutletIds;
    }
}
