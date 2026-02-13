<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Outlet;
use App\Models\User;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesReportController extends Controller
{
    /**
     * Laporan penjualan (summary & detail transaksi)
     */
    public function index(Request $request)
    {
        // Default date range: hari ini
        $dateFrom = $request->input('date_from', now()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $viewMode = in_array($request->input('view_mode'), ['ringkas', 'detail'], true)
            ? $request->input('view_mode')
            : 'ringkas';
        
        // Build query
        $query = Sale::with(['outlet', 'user', 'payments.paymentMethod'])
            ->where('status', 'completed')
            ->whereBetween('sale_date', [$dateFrom, $dateTo]);

        // Filter outlet
        if ($request->filled('outlet_id')) {
            $query->where('outlet_id', $request->outlet_id);
        }

        // Filter kasir
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter payment method (via payments table)
        if ($request->filled('payment_method_id')) {
            $query->whereHas('payments', function($q) use ($request) {
                $q->where('payment_method_id', $request->payment_method_id);
            });
        }

        $sales = $query->orderBy('sale_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate summary
        $summary = [
            'total_transactions' => $sales->count(),
            'total_before_rounding' => $sales->sum(fn ($sale) => (float) $sale->total_amount - (float) ($sale->rounding_amount ?? 0)),
            'total_rounding' => $sales->sum('rounding_amount'),
            'total_amount' => $sales->sum('total_amount'),
            'avg_per_transaction' => $sales->count() > 0 ? $sales->avg('total_amount') : 0,
            'total_cash' => 0,
            'total_non_cash' => 0,
        ];

        // Hitung cash vs non-cash (berbasis code PAYMENT CASH)
        foreach ($sales as $sale) {
            foreach ($sale->payments as $payment) {
                $isCash = $payment->paymentMethod?->code === 'CASH' || $payment->payment_method_id == 1;
                $summary['total_cash'] += $isCash ? $payment->amount : 0;
                $summary['total_non_cash'] += $isCash ? 0 : $payment->amount;
            }
        }

        $detailRows = collect();
        if ($viewMode === 'detail') {
            $paymentAggSub = DB::table('payments')
                ->join('payment_methods', 'payments.payment_method_id', '=', 'payment_methods.id')
                ->select('payments.sale_id')
                ->selectRaw('COALESCE(SUM(payments.amount), 0) as paid_amount')
                ->selectRaw("GROUP_CONCAT(DISTINCT payment_methods.name ORDER BY payment_methods.name SEPARATOR ', ') as payment_methods")
                ->groupBy('payments.sale_id');

            $detailQuery = DB::table('sale_items')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->join('outlets', 'sales.outlet_id', '=', 'outlets.id')
                ->leftJoinSub($paymentAggSub, 'pay_agg', function ($join) {
                    $join->on('sales.id', '=', 'pay_agg.sale_id');
                })
                ->where('sales.status', 'completed')
                ->whereBetween('sales.sale_date', [$dateFrom, $dateTo]);

            if ($request->filled('outlet_id')) {
                $detailQuery->where('sales.outlet_id', $request->outlet_id);
            }

            if ($request->filled('user_id')) {
                $detailQuery->where('sales.user_id', $request->user_id);
            }

            if ($request->filled('payment_method_id')) {
                $paymentMethodId = $request->payment_method_id;
                $detailQuery->whereExists(function ($subQuery) use ($paymentMethodId) {
                    $subQuery->select(DB::raw(1))
                        ->from('payments as payment_filter')
                        ->whereColumn('payment_filter.sale_id', 'sales.id')
                        ->where('payment_filter.payment_method_id', $paymentMethodId);
                });
            }

            $detailRows = $detailQuery
                ->select(
                    'sales.invoice_number as transaction_number',
                    'sales.sale_date',
                    'outlets.name as outlet_name',
                    'sales.customer_name',
                    'sale_items.product_name',
                    'sale_items.quantity as qty',
                    'sale_items.unit_price as price',
                    'sale_items.discount_amount as item_discount',
                    'sale_items.subtotal as item_subtotal',
                    'sales.tax_amount',
                    'sales.service_charge_amount',
                    'sales.rounding_amount',
                    'sales.total_amount',
                    DB::raw("COALESCE(pay_agg.payment_methods, '-') as payment_methods")
                )
                ->selectRaw(
                    "CASE
                        WHEN COALESCE(pay_agg.paid_amount, 0) >= sales.total_amount AND sales.total_amount > 0 THEN 'Lunas'
                        WHEN COALESCE(pay_agg.paid_amount, 0) > 0 THEN 'Parsial'
                        ELSE 'Belum Bayar'
                    END as payment_status"
                )
                ->orderByDesc('sales.sale_date')
                ->orderByDesc('sales.created_at')
                ->orderBy('sale_items.id')
                ->get();
        }

        // Data untuk filter
        $outlets = Outlet::where('is_active', true)->get();
        $users = User::whereHas('role', function($query) {
            $query->whereIn('name', ['kasir', 'admin']);
        })->get();
        $paymentMethods = PaymentMethod::where('is_active', true)->get();

        $filters = $request->only(['date_from', 'date_to', 'outlet_id', 'user_id', 'payment_method_id', 'view_mode']);

        return view('admin.reports.sales.index', compact(
            'sales', 
            'detailRows',
            'summary', 
            'outlets', 
            'users', 
            'paymentMethods', 
            'filters',
            'viewMode',
            'dateFrom',
            'dateTo'
        ));
    }

    /**
     * Laporan penjualan per produk
     */
    public function products(Request $request)
    {
        // Default date range: hari ini
        $dateFrom = $request->input('date_from', now()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        // Build query dengan agregasi
        $query = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sale_date', [$dateFrom, $dateTo]);

        // Filter outlet
        if ($request->filled('outlet_id')) {
            $query->where('sales.outlet_id', $request->outlet_id);
        }

        // Filter kasir
        if ($request->filled('user_id')) {
            $query->where('sales.user_id', $request->user_id);
        }

        // Agregasi per produk
        $products = $query->select(
                'products.id',
                'products.name as product_name',
                'products.sku',
                DB::raw('SUM(sale_items.quantity) as total_qty'),
                DB::raw('SUM(sale_items.subtotal) as total_amount')
            )
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderBy('total_amount', 'desc')
            ->get();

        // Grand total
        $grandTotal = [
            'total_qty' => $products->sum('total_qty'),
            'total_amount' => $products->sum('total_amount'),
        ];

        // Data untuk filter
        $outlets = Outlet::where('is_active', true)->get();
        $users = User::whereHas('role', function($query) {
            $query->whereIn('name', ['kasir', 'admin']);
        })->get();

        $filters = $request->only(['date_from', 'date_to', 'outlet_id', 'user_id']);

        return view('admin.reports.sales.products', compact(
            'products',
            'grandTotal',
            'outlets',
            'users',
            'filters',
            'dateFrom',
            'dateTo'
        ));
    }
}
