<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Models\CashSession;
use App\Models\Outlet;
use App\Models\User;
use App\Support\ReportExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShiftReportController extends Controller
{
    /**
     * Laporan shift kasir (summary per shift)
     */
    public function index(Request $request)
    {
        // Default date range: hari ini
        $dateFrom = $request->input('date_from', now()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        // Build query
        $query = CashSession::with(['outlet', 'user'])
            ->whereBetween('opened_at', [
                $dateFrom . ' 00:00:00',
                $dateTo . ' 23:59:59'
            ]);

        // Filter outlet
        if ($request->filled('outlet_id')) {
            $query->where('outlet_id', $request->outlet_id);
        }

        // Filter kasir
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $sessions = $query->orderBy('opened_at', 'desc')->get();

        // Calculate totals
        $totals = [
            'total_shifts' => $sessions->count(),
            'total_sales' => $sessions->sum('total_sales'),
            'total_difference' => $sessions->where('status', 'closed')->sum('difference'),
            'shifts_with_shortage' => $sessions->where('status', 'closed')->where('difference', '<', 0)->count(),
            'shifts_with_overage' => $sessions->where('status', 'closed')->where('difference', '>', 0)->count(),
        ];

        // Data untuk filter
        $outlets = Outlet::where('is_active', true)->get();
        $users = User::whereHas('role', function($query) {
            $query->whereIn('name', ['kasir', 'admin']);
        })->get();

        $filters = $request->only(['date_from', 'date_to', 'outlet_id', 'user_id', 'status']);

        return view('admin.reports.shifts.index', compact(
            'sessions',
            'totals',
            'outlets',
            'users',
            'filters',
            'dateFrom',
            'dateTo'
        ));
    }

    /**
     * Detail satu shift kasir
     */
    public function show(CashSession $cashSession)
    {
        // Load relasi
        $cashSession->load([
            'outlet',
            'user',
            'sales' => function($query) {
                $query->where('status', 'completed')
                    ->with(['payments.paymentMethod', 'items.product']);
            }
        ]);

        // Calculate breakdown by payment method
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

        // Top products in this shift
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

        return view('admin.reports.shifts.show', compact(
            'cashSession',
            'paymentBreakdown',
            'topProducts'
        ));
    }

    public function exportIndex(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $query = CashSession::with(['outlet', 'user'])
            ->whereBetween('opened_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);

        if ($request->filled('outlet_id')) {
            $query->where('outlet_id', $request->outlet_id);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $rows = $query->orderBy('opened_at', 'desc')->get()->map(function ($session) {
            return [
                'session_number' => $session->session_number,
                'outlet' => $session->outlet?->name ?? '-',
                'kasir' => $session->user?->name ?? '-',
                'dibuka' => optional($session->opened_at)->format('Y-m-d H:i:s'),
                'ditutup' => optional($session->closed_at)->format('Y-m-d H:i:s') ?? '-',
                'total_penjualan' => (float) $session->total_sales,
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
            ['key' => 'total_penjualan', 'label' => 'Total Penjualan', 'type' => 'number', 'decimals' => 2],
            ['key' => 'selisih', 'label' => 'Selisih', 'type' => 'number', 'decimals' => 2],
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
            }
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
}
