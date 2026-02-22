<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Outlet;
use App\Models\User;
use App\Models\PaymentMethod;
use App\Support\ReportExport;
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
        $outletIds = $this->resolveOutletIds($request);
        $viewMode = in_array($request->input('view_mode'), ['ringkas', 'detail'], true)
            ? $request->input('view_mode')
            : 'ringkas';
        
        // Build query
        $query = Sale::with(['outlet', 'user', 'payments.paymentMethod'])
            ->where('status', 'completed')
            ->whereBetween('sale_date', [$dateFrom, $dateTo]);

        // Filter outlet
        if (!empty($outletIds)) {
            $query->whereIn('outlet_id', $outletIds);
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

        // Filter product_id (via sale relations)
        if ($request->filled('product_id')) {
            $query->whereHas('items', function($q) use ($request) {
                $q->where('product_id', $request->product_id);
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

            if (!empty($outletIds)) {
                $detailQuery->whereIn('sales.outlet_id', $outletIds);
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

            if ($request->filled('product_id')) {
                $detailQuery->where('sale_items.product_id', $request->product_id);
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
                    DB::raw('COALESCE(sale_items.discount_amount, 0) as item_discount'),
                    'sale_items.subtotal as item_subtotal',
                    'sale_items.subtotal as item_total',
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

        $filters = $request->only(['date_from', 'date_to', 'user_id', 'payment_method_id', 'view_mode']);
        $filters['outlet_ids'] = $outletIds;
        $filters['outlet_id'] = count($outletIds) === 1 ? $outletIds[0] : null;

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
        $outletIds = $this->resolveOutletIds($request);

        // Ambil data outlet yang akan dijadikan kolom
        $outletsForColumns = Outlet::where('is_active', true);
        if (!empty($outletIds)) {
            $outletsForColumns->whereIn('id', $outletIds);
        }
        $outletsForColumns = $outletsForColumns->orderBy('name')->get();

        // Build query dengan agregasi
        $query = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sale_date', [$dateFrom, $dateTo]);

        // Filter outlet
        if (!empty($outletIds)) {
            $query->whereIn('sales.outlet_id', $outletIds);
        }

        // Filter kasir
        if ($request->filled('user_id')) {
            $query->where('sales.user_id', $request->user_id);
        }

        $selects = [
            'products.id',
            'products.name as product_name',
            'products.sku',
            DB::raw('SUM(sale_items.quantity) as total_qty'),
            DB::raw('SUM(sale_items.subtotal) as total_amount')
        ];

        foreach ($outletsForColumns as $outletCol) {
            $selects[] = DB::raw("SUM(CASE WHEN sales.outlet_id = {$outletCol->id} THEN sale_items.quantity ELSE 0 END) as outlet_{$outletCol->id}_qty");
            $selects[] = DB::raw("SUM(CASE WHEN sales.outlet_id = {$outletCol->id} THEN sale_items.subtotal ELSE 0 END) as outlet_{$outletCol->id}_amount");
        }

        // Agregasi per produk
        $products = $query->select($selects)
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderBy('total_amount', 'desc')
            ->get();

        // Apply filters in-memory
        if ($request->filled('filter_sku')) {
            $products = $products->filter(fn($p) => stripos($p->sku ?? '', $request->filter_sku) !== false);
        }
        if ($request->filled('filter_product')) {
            $products = $products->filter(fn($p) => stripos($p->product_name ?? '', $request->filter_product) !== false);
        }
        if ($request->filled('filter_qty')) {
            $products = $products->filter(fn($p) => stripos((string)$p->total_qty, $request->filter_qty) !== false);
        }
        if ($request->filled('filter_amount')) {
            $products = $products->filter(fn($p) => stripos((string)$p->total_amount, $request->filter_amount) !== false);
        }
        if ($request->filled('filter_avg')) {
            $products = $products->filter(function($p) use ($request) {
                $avg = $p->total_qty > 0 ? floor($p->total_amount / $p->total_qty) : 0;
                return stripos((string)$avg, $request->filter_avg) !== false;
            });
        }
        foreach ($outletsForColumns as $outletCol) {
            $filterName = "filter_outlet_{$outletCol->id}";
            if ($request->filled($filterName)) {
                $products = $products->filter(fn($p) => stripos((string)($p->{"outlet_{$outletCol->id}_qty"} ?? 0), $request->{$filterName}) !== false);
            }
        }
        $products = $products->values();

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

        $filters = $request->only(['date_from', 'date_to', 'user_id']);
        $filters['outlet_ids'] = $outletIds;
        $filters['outlet_id'] = count($outletIds) === 1 ? $outletIds[0] : null;

        return view('admin.reports.sales.products', compact(
            'products',
            'grandTotal',
            'outlets',
            'outletsForColumns',
            'users',
            'filters',
            'dateFrom',
            'dateTo'
        ));
    }

    public function exportIndex(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $outletIds = $this->resolveOutletIds($request);
        $viewMode = in_array($request->input('view_mode'), ['ringkas', 'detail'], true)
            ? $request->input('view_mode')
            : 'ringkas';

        $query = Sale::with(['outlet', 'user', 'payments.paymentMethod'])
            ->where('status', 'completed')
            ->whereBetween('sale_date', [$dateFrom, $dateTo]);

        if (!empty($outletIds)) {
            $query->whereIn('outlet_id', $outletIds);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('payment_method_id')) {
            $query->whereHas('payments', function ($q) use ($request) {
                $q->where('payment_method_id', $request->payment_method_id);
            });
        }
        if ($request->filled('product_id')) {
            $query->whereHas('items', function ($q) use ($request) {
                $q->where('product_id', $request->product_id);
            });
        }

        $format = $request->input('format', 'xlsx');
        $filename = sprintf('laporan-penjualan-%s-sd-%s.%s', str_replace('-', '', $dateFrom), str_replace('-', '', $dateTo), $format);

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

            if (!empty($outletIds)) {
                $detailQuery->whereIn('sales.outlet_id', $outletIds);
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

            if ($request->filled('product_id')) {
                $detailQuery->where('sale_items.product_id', $request->product_id);
            }

            $rows = $detailQuery
                ->select(
                    'sales.invoice_number as no_transaksi',
                    'sales.sale_date as tanggal',
                    'outlets.name as outlet',
                    'sale_items.product_name as produk',
                    'sale_items.quantity as qty',
                    'sale_items.unit_price as harga',
                    DB::raw('COALESCE(sale_items.discount_amount, 0) as diskon_item'),
                    'sale_items.subtotal as subtotal',
                    DB::raw("COALESCE(pay_agg.payment_methods, '-') as metode_bayar"),
                    'sale_items.subtotal as total_item'
                )
                ->orderByDesc('sales.sale_date')
                ->orderByDesc('sales.created_at')
                ->orderBy('sale_items.id')
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all();

            $columns = [
                ['key' => 'no_transaksi', 'label' => 'No Transaksi', 'type' => 'text'],
                ['key' => 'tanggal', 'label' => 'Tanggal', 'type' => 'text'],
                ['key' => 'outlet', 'label' => 'Outlet', 'type' => 'text'],
                ['key' => 'produk', 'label' => 'Produk', 'type' => 'text'],
                ['key' => 'qty', 'label' => 'Qty', 'type' => 'number', 'decimals' => 2],
                ['key' => 'harga', 'label' => 'Harga', 'type' => 'number', 'decimals' => 2],
                ['key' => 'diskon_item', 'label' => 'Diskon Item', 'type' => 'number', 'decimals' => 2],
                ['key' => 'subtotal', 'label' => 'Subtotal', 'type' => 'number', 'decimals' => 2],
                ['key' => 'metode_bayar', 'label' => 'Metode Bayar', 'type' => 'text'],
                ['key' => 'total_item', 'label' => 'Total Item', 'type' => 'number', 'decimals' => 2],
            ];
        } else {
            $sales = $query->orderBy('sale_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            $rows = $sales->map(function ($sale) {
                $paymentMethods = $sale->payments->map(fn ($payment) => $payment->paymentMethod?->name)->filter()->unique()->implode(', ');
                return [
                    'no_transaksi' => $sale->invoice_number,
                    'tanggal' => optional($sale->sale_date)->format('Y-m-d'),
                    'outlet' => $sale->outlet?->name ?? '-',
                    'kasir' => $sale->user?->name ?? '-',
                    'total' => (float) $sale->total_amount,
                    'metode_bayar' => $paymentMethods ?: '-',
                ];
            })->all();

            $columns = [
                ['key' => 'no_transaksi', 'label' => 'No Transaksi', 'type' => 'text'],
                ['key' => 'tanggal', 'label' => 'Tanggal', 'type' => 'text'],
                ['key' => 'outlet', 'label' => 'Outlet', 'type' => 'text'],
                ['key' => 'kasir', 'label' => 'Kasir', 'type' => 'text'],
                ['key' => 'total', 'label' => 'Total', 'type' => 'number', 'decimals' => 2],
                ['key' => 'metode_bayar', 'label' => 'Metode Bayar', 'type' => 'text'],
            ];
        }

        if ($format === 'pdf') {
            return ReportExport::pdf($filename, 'Laporan Penjualan', $columns, $rows);
        }

        return ReportExport::xlsx($filename, 'Laporan Penjualan', $columns, $rows);
    }

    public function exportProducts(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $outletIds = $this->resolveOutletIds($request);

        $query = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sale_date', [$dateFrom, $dateTo]);

        if (!empty($outletIds)) {
            $query->whereIn('sales.outlet_id', $outletIds);
        }
        if ($request->filled('user_id')) {
            $query->where('sales.user_id', $request->user_id);
        }

        $products = $query->select(
            'products.name as produk',
            'products.sku as sku',
            DB::raw('SUM(sale_items.quantity) as qty'),
            DB::raw('SUM(sale_items.subtotal) as total')
        )
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderBy('total', 'desc')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        $columns = [
            ['key' => 'produk', 'label' => 'Produk', 'type' => 'text'],
            ['key' => 'sku', 'label' => 'SKU', 'type' => 'text'],
            ['key' => 'qty', 'label' => 'Qty', 'type' => 'number', 'decimals' => 2],
            ['key' => 'total', 'label' => 'Total', 'type' => 'number', 'decimals' => 2],
        ];

        $format = $request->input('format', 'xlsx');
        $filename = sprintf('laporan-penjualan-produk-%s-sd-%s.%s', str_replace('-', '', $dateFrom), str_replace('-', '', $dateTo), $format);

        if ($format === 'pdf') {
            return ReportExport::pdf($filename, 'Laporan Penjualan per Produk', $columns, $products);
        }

        return ReportExport::xlsx($filename, 'Penjualan per Produk', $columns, $products);
    }

    private function resolveOutletIds(Request $request): array
    {
        if ($request->has('outlet_ids')) {
            $rawOutletIds = $request->input('outlet_ids', []);
            if (!is_array($rawOutletIds)) {
                return [];
            }

            return collect($rawOutletIds)
                ->filter(fn($id) => $id !== null && $id !== '')
                ->map(fn($id) => is_numeric($id) ? (int) $id : null)
                ->filter(fn($id) => is_int($id) && $id > 0)
                ->unique()
                ->values()
                ->all();
        }

        if ($request->filled('outlet_id') && is_numeric($request->input('outlet_id'))) {
            $outletId = (int) $request->input('outlet_id');
            return $outletId > 0 ? [$outletId] : [];
        }

        return [];
    }
}
