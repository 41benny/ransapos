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
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        if ($viewMode === 'ringkas') {
            if ($request->filled('filter_tanggal')) {
                $sales = $sales->filter(fn($s) => stripos($s->created_at->format('d M Y, H:i'), $request->filter_tanggal) !== false);
            }
            if ($request->filled('filter_invoice')) {
                $sales = $sales->filter(fn($s) => stripos($s->invoice_number ?? '', $request->filter_invoice) !== false);
            }
            if ($request->filled('filter_outlet')) {
                $sales = $sales->filter(fn($s) => stripos($s->outlet->name ?? '', $request->filter_outlet) !== false);
            }
            if ($request->filled('filter_kasir')) {
                $sales = $sales->filter(fn($s) => stripos($s->user->name ?? '', $request->filter_kasir) !== false);
            }
            if ($request->filled('filter_pembayaran')) {
                $sales = $sales->filter(fn($s) => stripos($s->payments->first()?->paymentMethod->name ?? 'Mixed', $request->filter_pembayaran) !== false);
            }
            if ($request->filled('filter_bulat')) {
                $sales = $sales->filter(fn($s) => stripos((string)abs((float)$s->rounding_amount), $request->filter_bulat) !== false);
            }
            if ($request->filled('filter_total')) {
                $sales = $sales->filter(fn($s) => stripos((string)$s->total_amount, $request->filter_total) !== false);
            }
            $sales = $sales->values();
        }

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
                    'sales.sales_type as metode_penjualan',
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

            if ($request->filled('filter_transaksi')) {
                $detailRows = $detailRows->filter(fn($r) => stripos($r->transaction_number ?? '', $request->filter_transaksi) !== false);
            }
            if ($request->filled('filter_tanggal')) {
                $detailRows = $detailRows->filter(fn($r) => stripos(\Carbon\Carbon::parse($r->sale_date)->format('d M Y'), $request->filter_tanggal) !== false);
            }
            if ($request->filled('filter_outlet')) {
                $detailRows = $detailRows->filter(fn($r) => stripos($r->outlet_name ?? '', $request->filter_outlet) !== false);
            }
            if ($request->filled('filter_produk')) {
                $detailRows = $detailRows->filter(fn($r) => stripos($r->product_name ?? '', $request->filter_produk) !== false);
            }
            if ($request->filled('filter_qty')) {
                $detailRows = $detailRows->filter(fn($r) => stripos((string)$r->qty, $request->filter_qty) !== false);
            }
            if ($request->filled('filter_harga')) {
                $detailRows = $detailRows->filter(fn($r) => stripos((string)$r->price, $request->filter_harga) !== false);
            }
            if ($request->filled('filter_diskon')) {
                $detailRows = $detailRows->filter(fn($r) => stripos((string)$r->item_discount, $request->filter_diskon) !== false);
            }
            if ($request->filled('filter_subtotal')) {
                $detailRows = $detailRows->filter(fn($r) => stripos((string)$r->item_subtotal, $request->filter_subtotal) !== false);
            }
            if ($request->filled('filter_total')) {
                $detailRows = $detailRows->filter(fn($r) => stripos((string)($r->item_total ?? $r->item_subtotal), $request->filter_total) !== false);
            }
            if ($request->filled('filter_status')) {
                $detailRows = $detailRows->filter(fn($r) => stripos($r->payment_status ?? '', $request->filter_status) !== false);
            }
            if ($request->filled('filter_metode_bayar')) {
                $detailRows = $detailRows->filter(fn($r) => stripos($r->payment_methods ?? '', $request->filter_metode_bayar) !== false);
            }
            if ($request->filled('filter_metode_jual')) {
                $detailRows = $detailRows->filter(fn($r) => stripos($r->metode_penjualan ?? '', $request->filter_metode_jual) !== false);
            }
            $detailRows = $detailRows->values();
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
                    'sales.sales_type as metode_penjualan',
                    'sale_items.subtotal as total_item'
                )
                ->orderByDesc('sales.sale_date')
                ->orderByDesc('sales.created_at')
                ->orderBy('sale_items.id')
                ->orderBy('sale_items.id')
                ->get();

            if ($request->filled('filter_transaksi')) {
                $rows = $rows->filter(fn($r) => stripos($r->no_transaksi ?? '', $request->filter_transaksi) !== false);
            }
            if ($request->filled('filter_tanggal')) {
                $rows = $rows->filter(fn($r) => stripos(\Carbon\Carbon::parse($r->tanggal)->format('d M Y'), $request->filter_tanggal) !== false);
            }
            if ($request->filled('filter_outlet')) {
                $rows = $rows->filter(fn($r) => stripos($r->outlet ?? '', $request->filter_outlet) !== false);
            }
            if ($request->filled('filter_produk')) {
                $rows = $rows->filter(fn($r) => stripos($r->produk ?? '', $request->filter_produk) !== false);
            }
            if ($request->filled('filter_qty')) {
                $rows = $rows->filter(fn($r) => stripos((string)$r->qty, $request->filter_qty) !== false);
            }
            if ($request->filled('filter_harga')) {
                $rows = $rows->filter(fn($r) => stripos((string)$r->harga, $request->filter_harga) !== false);
            }
            if ($request->filled('filter_diskon')) {
                $rows = $rows->filter(fn($r) => stripos((string)$r->diskon_item, $request->filter_diskon) !== false);
            }
            if ($request->filled('filter_subtotal')) {
                $rows = $rows->filter(fn($r) => stripos((string)$r->subtotal, $request->filter_subtotal) !== false);
            }
            if ($request->filled('filter_total')) {
                $rows = $rows->filter(fn($r) => stripos((string)($r->total_item ?? $r->subtotal), $request->filter_total) !== false);
            }
            if ($request->filled('filter_metode_bayar')) {
                $rows = $rows->filter(fn($r) => stripos($r->metode_bayar ?? '', $request->filter_metode_bayar) !== false);
            }
            if ($request->filled('filter_metode_jual')) {
                $rows = $rows->filter(fn($r) => stripos($r->metode_penjualan ?? '', $request->filter_metode_jual) !== false);
            }

            $rows = $rows->map(fn ($row) => array_merge((array) $row, [
                'metode_penjualan' => str_replace('_', ' ', $row->metode_penjualan),
            ]))->all();

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
                ['key' => 'metode_penjualan', 'label' => 'Tipe Order', 'type' => 'text'],
                ['key' => 'total_item', 'label' => 'Total Item', 'type' => 'number', 'decimals' => 2],
            ];
        } else {
            $sales = $query->orderBy('sale_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            if ($request->filled('filter_tanggal')) {
                $sales = $sales->filter(fn($s) => stripos($s->created_at->format('d M Y, H:i'), $request->filter_tanggal) !== false);
            }
            if ($request->filled('filter_invoice')) {
                $sales = $sales->filter(fn($s) => stripos($s->invoice_number ?? '', $request->filter_invoice) !== false);
            }
            if ($request->filled('filter_outlet')) {
                $sales = $sales->filter(fn($s) => stripos($s->outlet->name ?? '', $request->filter_outlet) !== false);
            }
            if ($request->filled('filter_kasir')) {
                $sales = $sales->filter(fn($s) => stripos($s->user->name ?? '', $request->filter_kasir) !== false);
            }
            if ($request->filled('filter_pembayaran')) {
                $sales = $sales->filter(fn($s) => stripos($s->payments->first()?->paymentMethod->name ?? 'Mixed', $request->filter_pembayaran) !== false);
            }
            if ($request->filled('filter_bulat')) {
                $sales = $sales->filter(fn($s) => stripos((string)abs((float)$s->rounding_amount), $request->filter_bulat) !== false);
            }
            if ($request->filled('filter_total')) {
                $sales = $sales->filter(fn($s) => stripos((string)$s->total_amount, $request->filter_total) !== false);
            }

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

        $outletsForColumns = Outlet::where('is_active', true);
        if (!empty($outletIds)) {
            $outletsForColumns->whereIn('id', $outletIds);
        }
        $outletsForColumns = $outletsForColumns->orderBy('name')->get();

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

        $selects = [
            'products.id',
            'products.name as product_name',
            'products.sku',
            DB::raw('SUM(sale_items.quantity) as total_qty'),
            DB::raw('SUM(sale_items.subtotal) as total_amount'),
        ];

        foreach ($outletsForColumns as $outletCol) {
            $selects[] = DB::raw("SUM(CASE WHEN sales.outlet_id = {$outletCol->id} THEN sale_items.quantity ELSE 0 END) as outlet_{$outletCol->id}_qty");
        }

        $products = $query->select($selects)
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderBy('total_amount', 'desc')
            ->get();

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

        $rows = $products->map(function ($row, $index) use ($outletsForColumns) {
            $data = [
                'no' => $index + 1,
                'produk' => $row->product_name,
                'sku' => $row->sku,
            ];

            foreach ($outletsForColumns as $outletCol) {
                $data["outlet_{$outletCol->id}_qty"] = (float) ($row->{"outlet_{$outletCol->id}_qty"} ?? 0);
            }

            $qty = (float) $row->total_qty;
            $amount = (float) $row->total_amount;
            $data['total_qty'] = $qty;
            $data['total_omzet'] = $amount;
            $data['avg_price'] = $qty > 0 ? floor($amount / $qty) : 0;

            return $data;
        })->all();

        $grandTotalQty = collect($rows)->sum('total_qty');
        $grandTotalAmount = collect($rows)->sum('total_omzet');
        $grandTotalRow = [
            'no' => '',
            'produk' => 'GRAND TOTAL',
            'sku' => '',
        ];

        foreach ($outletsForColumns as $outletCol) {
            $grandTotalRow["outlet_{$outletCol->id}_qty"] = collect($rows)->sum("outlet_{$outletCol->id}_qty");
        }

        $grandTotalRow['total_qty'] = $grandTotalQty;
        $grandTotalRow['total_omzet'] = $grandTotalAmount;
        $grandTotalRow['avg_price'] = $grandTotalQty > 0 ? floor($grandTotalAmount / $grandTotalQty) : 0;

        $rows[] = $grandTotalRow;

        $columns = [
            ['key' => 'no', 'label' => 'No', 'type' => 'text'],
            ['key' => 'produk', 'label' => 'Produk', 'type' => 'text'],
            ['key' => 'sku', 'label' => 'SKU', 'type' => 'text'],
        ];

        foreach ($outletsForColumns as $outletCol) {
            $columns[] = ['key' => "outlet_{$outletCol->id}_qty", 'label' => "{$outletCol->name} Qty", 'type' => 'number', 'decimals' => 0];
        }

        $columns[] = ['key' => 'total_qty', 'label' => 'Total Qty', 'type' => 'number', 'decimals' => 0];
        $columns[] = ['key' => 'total_omzet', 'label' => 'Total Omzet', 'type' => 'number', 'decimals' => 0];
        $columns[] = ['key' => 'avg_price', 'label' => 'Avg Price', 'type' => 'number', 'decimals' => 0];

        $format = $request->input('format', 'xlsx');
        $filename = sprintf('laporan-penjualan-produk-%s-sd-%s.%s', str_replace('-', '', $dateFrom), str_replace('-', '', $dateTo), $format);

        if ($format === 'pdf') {
            return ReportExport::pdf($filename, 'Laporan Penjualan per Produk', $columns, $rows);
        }

        return ReportExport::xlsx($filename, 'Penjualan per Produk', $columns, $rows);
    }

    public function exportProductsOld(Request $request): StreamedResponse
    {
        $dateFrom = $request->input('date_from', now()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $outletIds = $this->resolveOutletIds($request);

        $selectedOutletsQuery = Outlet::where('is_active', true);
        if (!empty($outletIds)) {
            $selectedOutletsQuery->whereIn('id', $outletIds);
        }
        $selectedOutlets = $selectedOutletsQuery->orderBy('name')->get();

        $query = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('outlets', 'sales.outlet_id', '=', 'outlets.id')
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sale_date', [$dateFrom, $dateTo]);

        if (!empty($outletIds)) {
            $query->whereIn('sales.outlet_id', $outletIds);
        }
        if ($request->filled('user_id')) {
            $query->where('sales.user_id', $request->user_id);
        }

        $rows = $query
            ->select(
                'sales.outlet_id',
                'outlets.name as outlet_name',
                'products.name as product_name',
                'products.sku',
                DB::raw("COALESCE(product_categories.name, 'Tanpa Kategori') as category_name"),
                DB::raw('SUM(sale_items.quantity) as qty')
            )
            ->groupBy('sales.outlet_id', 'outlets.name', 'products.name', 'products.sku', 'product_categories.name')
            ->orderBy('outlets.name')
            ->orderBy('category_name')
            ->orderBy('products.name')
            ->get();

        if ($request->filled('filter_sku')) {
            $rows = $rows->filter(fn($r) => stripos($r->sku ?? '', $request->filter_sku) !== false);
        }
        if ($request->filled('filter_product')) {
            $rows = $rows->filter(fn($r) => stripos($r->product_name ?? '', $request->filter_product) !== false);
        }
        if ($request->filled('filter_qty')) {
            $rows = $rows->filter(fn($r) => stripos((string)$r->qty, $request->filter_qty) !== false);
        }
        foreach ($selectedOutlets as $outlet) {
            $filterName = "filter_outlet_{$outlet->id}";
            if ($request->filled($filterName)) {
                $rows = $rows->filter(function ($r) use ($request, $filterName, $outlet) {
                    return (int) $r->outlet_id === (int) $outlet->id
                        ? stripos((string) $r->qty, $request->{$filterName}) !== false
                        : true;
                });
            }
        }

        $filename = sprintf(
            'laporan-penjualan-produk-old-%s-sd-%s.xlsx',
            str_replace('-', '', $dateFrom),
            str_replace('-', '', $dateTo)
        );

        return new StreamedResponse(function () use ($rows, $selectedOutlets) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Penjualan Produk Old');

            $rowNum = 1;
            $writtenSections = 0;

            foreach ($selectedOutlets as $outlet) {
                $outletRows = $rows
                    ->where('outlet_id', $outlet->id)
                    ->sortBy([
                        ['category_name', 'asc'],
                        ['product_name', 'asc'],
                    ])
                    ->values();

                if ($outletRows->isEmpty()) {
                    continue;
                }

                $writtenSections++;

                $sheet->setCellValueExplicit("A{$rowNum}", 'Outlet: ' . $outlet->name, DataType::TYPE_STRING);
                $sheet->mergeCells("A{$rowNum}:B{$rowNum}");
                $sheet->getStyle("A{$rowNum}")->getFont()->setBold(true);
                $rowNum++;

                $sheet->setCellValue('A' . $rowNum, 'Nama Produk');
                $sheet->setCellValue('B' . $rowNum, 'Qty');
                $sheet->getStyle("A{$rowNum}:B{$rowNum}")->getFont()->setBold(true);
                $rowNum++;

                $currentCategory = null;
                foreach ($outletRows as $item) {
                    $categoryName = (string) ($item->category_name ?? 'Tanpa Kategori');

                    if ($currentCategory !== $categoryName) {
                        $sheet->setCellValueExplicit("A{$rowNum}", 'Kategori: ' . $categoryName, DataType::TYPE_STRING);
                        $sheet->mergeCells("A{$rowNum}:B{$rowNum}");
                        $sheet->getStyle("A{$rowNum}")->getFont()->setBold(true);
                        $rowNum++;
                        $currentCategory = $categoryName;
                    }

                    $sheet->setCellValueExplicit("A{$rowNum}", (string) $item->product_name, DataType::TYPE_STRING);
                    $sheet->setCellValue("B{$rowNum}", (float) ($item->qty ?? 0));
                    $sheet->getStyle("B{$rowNum}")
                        ->getNumberFormat()
                        ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
                    $rowNum++;
                }

                $rowNum++;
            }

            if ($writtenSections === 0) {
                $sheet->setCellValueExplicit('A1', 'Tidak ada data untuk filter yang dipilih.', DataType::TYPE_STRING);
                $sheet->mergeCells('A1:B1');
            }

            $sheet->getColumnDimension('A')->setAutoSize(true);
            $sheet->getColumnDimension('B')->setAutoSize(true);

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
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
