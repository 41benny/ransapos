<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Services\ProfitLossReportService;
use App\Models\Outlet;
use App\Support\ReportExport;
use Illuminate\Http\Request;

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
        $outletId = $request->input('outlet_id');

        // Generate report
        $report = $this->reportService->generate($dateFrom, $dateTo, $outletId);

        // Load outlets untuk filter
        $outlets = Outlet::active()->get();

        return view('admin.reports.profit-loss', compact('report', 'outlets', 'dateFrom', 'dateTo', 'outletId'));
    }

    public function export(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->endOfMonth()->format('Y-m-d'));
        $outletId = $request->input('outlet_id');

        $report = $this->reportService->generate($dateFrom, $dateTo, $outletId);

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
}
