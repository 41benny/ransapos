<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Services\ProfitLossReportService;
use App\Models\Outlet;
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
}
