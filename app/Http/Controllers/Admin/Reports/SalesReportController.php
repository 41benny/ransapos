<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Outlet;
use App\Models\User;
use App\Models\PaymentMethod;
use App\Services\HppJournalExportService;
use App\Services\InventoryMutationJournalExportService;
use App\Services\PurchaseJournalExportService;
use App\Services\SalesJournalExportService;
use App\Support\ReportExport;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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

        $sales = collect();
        $detailRows = collect();

        if ($viewMode === 'detail') {
            $salesBaseQuery = $this->buildSalesTableQuery($request, $dateFrom, $dateTo, $outletIds);
            $summary = $this->buildSalesSummaryFromTableQuery($salesBaseQuery);
            $detailRows = $this->buildSalesDetailPaginator($request, $salesBaseQuery);
        } else {
            $query = Sale::with(['outlet', 'user', 'customer', 'payments.paymentMethod'])
                ->where('status', 'completed')
                ->whereBetween('sale_date', [$dateFrom, $dateTo]);

            if (!empty($outletIds)) {
                $query->whereIn('outlet_id', $outletIds);
            }

            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->filled('payment_method_id')) {
                $query->whereHas('payments', function($q) use ($request) {
                    $q->where('payment_method_id', $request->payment_method_id);
                });
            }

            if ($request->filled('product_id')) {
                $query->whereHas('items', function($q) use ($request) {
                    $q->where('product_id', $request->product_id);
                });
            }

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
            if ($request->filled('filter_customer')) {
                $sales = $sales->filter(fn($s) => stripos($s->resolved_customer_name, $request->filter_customer) !== false);
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
            $summary = [
                'total_transactions' => $sales->count(),
                'total_before_rounding' => $sales->sum(fn ($sale) => (float) $sale->total_amount - (float) ($sale->rounding_amount ?? 0)),
                'total_rounding' => $sales->sum('rounding_amount'),
                'total_amount' => $sales->sum('total_amount'),
                'avg_per_transaction' => $sales->count() > 0 ? $sales->avg('total_amount') : 0,
                'total_cash' => 0,
                'total_non_cash' => 0,
            ];

            foreach ($sales as $sale) {
                foreach ($sale->payments as $payment) {
                    $methodCode = strtoupper(trim((string) ($payment->paymentMethod?->code ?? '')));
                    $methodName = strtolower(trim((string) ($payment->paymentMethod?->name ?? '')));
                    $isCash = $methodCode === 'CASH'
                        || str_contains($methodName, 'cash')
                        || str_contains($methodName, 'tunai');
                    $summary['total_cash'] += $isCash ? $payment->amount : 0;
                    $summary['total_non_cash'] += $isCash ? 0 : $payment->amount;
                }
            }

            $sales = $this->paginateCollection($sales, $request, 100);
        }

        // Data untuk filter
        $outlets = $this->resolveAccessibleOutlets();
        $usersQuery = User::whereHas('role', function($query) {
            $query->whereIn('name', ['kasir', 'admin']);
        });
        if (!empty($outletIds)) {
            $usersQuery->whereIn('outlet_id', $outletIds);
        }
        $users = $usersQuery->get();
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
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
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
            DB::raw("COALESCE(product_categories.name, 'Tanpa Kategori') as category_name"),
            DB::raw('SUM(sale_items.quantity) as total_qty'),
            DB::raw('SUM(sale_items.subtotal) as total_amount')
        ];

        foreach ($outletsForColumns as $outletCol) {
            $selects[] = DB::raw("SUM(CASE WHEN sales.outlet_id = {$outletCol->id} THEN sale_items.quantity ELSE 0 END) as outlet_{$outletCol->id}_qty");
            $selects[] = DB::raw("SUM(CASE WHEN sales.outlet_id = {$outletCol->id} THEN sale_items.subtotal ELSE 0 END) as outlet_{$outletCol->id}_amount");
        }

        // Agregasi per produk
        $products = $query->select($selects)
            ->groupBy('products.id', 'products.name', 'products.sku', 'product_categories.name')
            ->orderBy('category_name', 'asc')
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
        $outletGrandTotals = [];
        foreach ($outletsForColumns as $outletCol) {
            $outletGrandTotals[$outletCol->id] = (float) $products->sum("outlet_{$outletCol->id}_qty");
        }

        $products = $this->paginateCollection($products, $request, 100);

        // Data untuk filter
        $outlets = $this->resolveAccessibleOutlets();
        $usersQuery = User::whereHas('role', function($query) {
            $query->whereIn('name', ['kasir', 'admin']);
        });
        if (!empty($outletIds)) {
            $usersQuery->whereIn('outlet_id', $outletIds);
        }
        $users = $usersQuery->get();

        $filters = $request->only(['date_from', 'date_to', 'user_id']);
        $filters['outlet_ids'] = $outletIds;
        $filters['outlet_id'] = count($outletIds) === 1 ? $outletIds[0] : null;

        return view('admin.reports.sales.products', compact(
            'products',
            'grandTotal',
            'outlets',
            'outletsForColumns',
            'outletGrandTotals',
            'users',
            'filters',
            'dateFrom',
            'dateTo'
        ));
    }

    /**
     * Laporan ringkasan penjualan harian
     */
    public function dailySummary(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $outletIds = $this->resolveOutletIds($request);

        $query = Sale::query()
            ->where('status', 'completed')
            ->whereBetween('sale_date', [$dateFrom, $dateTo]);

        if (!empty($outletIds) && count($outletIds) > 0) {
            $query->whereIn('outlet_id', $outletIds);
        }

        if ($request->filled('sales_type')) {
            $query->where('sales_type', $request->sales_type);
        }

        $dailySales = $query->select(
                'sale_date',
                DB::raw('SUM(total_amount - tax_amount - service_charge_amount + discount_amount - COALESCE(rounding_amount, 0)) as total_sales'),
                DB::raw('SUM(discount_amount) as total_discount'),
                DB::raw('SUM(service_charge_amount) as total_service_charge'),
                DB::raw('SUM(tax_amount) as total_tax'),
                DB::raw('SUM(rounding_amount) as total_adjustment'),
                DB::raw('SUM(total_amount) as total_grand')
            )
            ->groupBy('sale_date')
            ->orderBy('sale_date', 'asc')
            ->get();

        $dailyTotals = [
            'row_count' => $dailySales->count(),
            'total_sales' => (float) $dailySales->sum('total_sales'),
            'total_discount' => (float) $dailySales->sum('total_discount'),
            'total_service_charge' => (float) $dailySales->sum('total_service_charge'),
            'total_tax' => (float) $dailySales->sum('total_tax'),
            'total_adjustment' => (float) $dailySales->sum('total_adjustment'),
            'total_grand' => (float) $dailySales->sum('total_grand'),
        ];

        $dailySales = $this->paginateCollection($dailySales, $request, 100);

        $outlets = $this->resolveAccessibleOutlets();
        $salesTypes = \App\Models\SalesType::where('is_active', true)->pluck('name', 'code');
        
        $filters = $request->only(['date_from', 'date_to', 'sales_type']);
        $filters['outlet_ids'] = $outletIds;
        $filters['outlet_id'] = count($outletIds) === 1 ? $outletIds[0] : null;

        return view('admin.reports.sales.daily', compact('dailySales', 'dailyTotals', 'outlets', 'salesTypes', 'filters', 'dateFrom', 'dateTo'));
    }

    public function exportIndex(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $outletIds = $this->resolveOutletIds($request);
        $viewMode = in_array($request->input('view_mode'), ['ringkas', 'detail'], true)
            ? $request->input('view_mode')
            : 'ringkas';

        $query = Sale::with(['outlet', 'user', 'customer', 'payments.paymentMethod'])
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
                ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
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
                    DB::raw("CASE WHEN TIME(sales.sale_date) = '00:00:00' THEN TIMESTAMP(DATE(sales.sale_date), TIME(sales.created_at)) ELSE sales.sale_date END as tanggal"),
                    'outlets.name as outlet',
                    DB::raw("COALESCE(NULLIF(TRIM(sales.customer_name), ''), NULLIF(TRIM(customers.name), ''), 'Walk-in') as customer"),
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
                $rows = $rows->filter(fn($r) => stripos(\Carbon\Carbon::parse($r->tanggal)->format('d M Y H:i'), $request->filter_tanggal) !== false);
            }
            if ($request->filled('filter_outlet')) {
                $rows = $rows->filter(fn($r) => stripos($r->outlet ?? '', $request->filter_outlet) !== false);
            }
            if ($request->filled('filter_customer')) {
                $rows = $rows->filter(fn($r) => stripos($r->customer ?? '', $request->filter_customer) !== false);
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
                'tanggal' => \Carbon\Carbon::parse($row->tanggal)->format('d M Y H:i'),
                'metode_penjualan' => str_replace('_', ' ', $row->metode_penjualan),
            ]))->all();

            $columns = [
                ['key' => 'no_transaksi', 'label' => 'No Transaksi', 'type' => 'text'],
                ['key' => 'tanggal', 'label' => 'Tanggal & Jam', 'type' => 'text'],
                ['key' => 'outlet', 'label' => 'Outlet', 'type' => 'text'],
                ['key' => 'customer', 'label' => 'Customer', 'type' => 'text'],
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
            if ($request->filled('filter_customer')) {
                $sales = $sales->filter(fn($s) => stripos($s->resolved_customer_name, $request->filter_customer) !== false);
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
                    'customer' => $sale->resolved_customer_name,
                    'kasir' => $sale->user?->name ?? '-',
                    'total' => (float) $sale->total_amount,
                    'metode_bayar' => $paymentMethods ?: '-',
                ];
            })->all();

            $columns = [
                ['key' => 'no_transaksi', 'label' => 'No Transaksi', 'type' => 'text'],
                ['key' => 'tanggal', 'label' => 'Tanggal', 'type' => 'text'],
                ['key' => 'outlet', 'label' => 'Outlet', 'type' => 'text'],
                ['key' => 'customer', 'label' => 'Customer', 'type' => 'text'],
                ['key' => 'kasir', 'label' => 'Kasir', 'type' => 'text'],
                ['key' => 'total', 'label' => 'Total', 'type' => 'number', 'decimals' => 2],
                ['key' => 'metode_bayar', 'label' => 'Metode Bayar', 'type' => 'text'],
            ];
        }

        if ($format === 'pdf') {
            $meta = [
                'Periode Data' => $dateFrom . ' s/d ' . $dateTo,
            ];

            if (!empty($outletIds)) {
                $meta['Outlet'] = Outlet::whereIn('id', $outletIds)->pluck('name')->implode(', ');
            }

            return ReportExport::pdf($filename, 'Laporan Penjualan', $columns, $rows, 'portrait', $meta);
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
            $meta = [
                'Periode Data' => $dateFrom . ' s/d ' . $dateTo,
            ];

            if (!empty($outletIds)) {
                $meta['Outlet'] = Outlet::whereIn('id', $outletIds)->pluck('name')->implode(', ');
            }

            return ReportExport::pdf($filename, 'Laporan Penjualan per Produk', $columns, $rows, 'portrait', $meta);
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

    public function exportDailySummary(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $outletIds = $this->resolveOutletIds($request);

        $query = Sale::query()
            ->join('outlets', 'sales.outlet_id', '=', 'outlets.id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sale_date', [$dateFrom, $dateTo]);

        if (!empty($outletIds) && count($outletIds) > 0) {
            $query->whereIn('sales.outlet_id', $outletIds);
        }

        if ($request->filled('sales_type')) {
            $query->where('sales.sales_type', $request->sales_type);
        }

        $dailySales = $query->select(
                'sales.sale_date',
                'outlets.name as outlet_name',
                DB::raw('SUM(sales.total_amount - sales.tax_amount - sales.service_charge_amount + sales.discount_amount - COALESCE(sales.rounding_amount, 0)) as total_sales'),
                DB::raw('SUM(sales.discount_amount) as total_discount'),
                DB::raw('SUM(sales.service_charge_amount) as total_service_charge'),
                DB::raw('SUM(sales.tax_amount) as total_tax'),
                DB::raw('SUM(sales.rounding_amount) as total_adjustment'),
                DB::raw('SUM(sales.total_amount) as total_grand')
            )
            ->groupBy('sales.sale_date', 'outlets.name')
            ->orderBy('sales.sale_date', 'asc')
            ->orderBy('outlets.name', 'asc')
            ->get();

        $rows = $dailySales->map(function ($item) {
            return [
                'tanggal' => optional($item->sale_date)->format('d/m/Y'),
                'outlet' => $item->outlet_name,
                'total_sales' => (float) $item->total_sales,
                'discount' => (float) $item->total_discount,
                'service_charge' => (float) $item->total_service_charge,
                'tax' => (float) $item->total_tax,
                'adjustment' => (float) $item->total_adjustment,
                'total' => (float) $item->total_grand,
            ];
        })->all();

        $columns = [
            ['key' => 'tanggal', 'label' => 'Tanggal', 'type' => 'text'],
            ['key' => 'outlet', 'label' => 'Outlet', 'type' => 'text'],
            ['key' => 'total_sales', 'label' => 'Total Sales', 'type' => 'number', 'decimals' => 2],
            ['key' => 'discount', 'label' => 'Discount', 'type' => 'number', 'decimals' => 2],
            ['key' => 'service_charge', 'label' => 'Service Charge', 'type' => 'number', 'decimals' => 2],
            ['key' => 'tax', 'label' => 'Tax', 'type' => 'number', 'decimals' => 2],
            ['key' => 'adjustment', 'label' => 'Adjustment', 'type' => 'number', 'decimals' => 2],
            ['key' => 'total', 'label' => 'Total', 'type' => 'number', 'decimals' => 2],
        ];

        $format = $request->input('format', 'xlsx');
        $filename = sprintf('ringkasan-penjualan-harian-%s-sd-%s.%s', str_replace('-', '', $dateFrom), str_replace('-', '', $dateTo), $format);

        if ($format === 'pdf') {
            $meta = [
                'Periode Data' => $dateFrom . ' s/d ' . $dateTo,
            ];

            if (!empty($outletIds)) {
                $meta['Outlet'] = Outlet::whereIn('id', $outletIds)->pluck('name')->implode(', ');
            }

            return ReportExport::pdf($filename, 'Ringkasan Penjualan Harian', $columns, $rows, 'portrait', $meta);
        }

        return ReportExport::xlsx($filename, 'Ringkasan Penjualan Harian', $columns, $rows);
    }

    public function hppJournalIndex(Request $request)
    {
        $outlets = $this->resolveAccessibleOutlets();
        $outletsByMapping = app(SalesJournalExportService::class)->partitionOutletsByMapping($outlets);
        $selectedOutletIds = collect((array) $request->input('outlet_ids', []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if (empty($selectedOutletIds) && $request->filled('outlet_id') && is_numeric($request->input('outlet_id'))) {
            $selectedOutletIds = [(int) $request->input('outlet_id')];
        }

        $month = $request->input('month');
        if (!is_string($month) || !preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }

        return view('admin.reports.hpp-journal', [
            'month' => $month,
            'outlets' => $outletsByMapping['mapped'],
            'unmappedOutlets' => $outletsByMapping['unmapped'],
            'selectedOutletIds' => $selectedOutletIds,
        ]);
    }

    public function exportHppJournal(Request $request)
    {
        $validated = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'outlet_id' => ['nullable', 'integer'],
            'outlet_ids' => ['nullable', 'array'],
            'outlet_ids.*' => ['integer'],
        ]);

        $month = (string) $validated['month'];
        $outletIds = $this->resolveOutletIds($request);

        try {
            $salesRows = app(SalesJournalExportService::class)->buildMonthlyRows($month, $outletIds);
            $hppRows = app(HppJournalExportService::class)->buildMonthlyRows($month, $outletIds);
            $inventoryMutationRows = app(InventoryMutationJournalExportService::class)->buildMonthlyRows($month, $outletIds);
            $purchaseRows = app(PurchaseJournalExportService::class)->buildMonthlyRows($month, $outletIds);
            $rows = array_merge($salesRows, $hppRows, $inventoryMutationRows, $purchaseRows);
        } catch (\InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'sales_journal' => [$exception->getMessage()],
            ]);
        }

        $columns = [
            ['key' => 'STATUS', 'label' => 'STATUS', 'type' => 'text'],
            ['key' => 'NO_AKUN', 'label' => 'NO_AKUN', 'type' => 'number', 'format_code' => '0'],
            ['key' => '_VOUCHER', 'label' => '_VOUCHER', 'type' => 'text'],
            ['key' => 'J_TANGGAL', 'label' => 'J_TANGGAL', 'type' => 'text'],
            ['key' => 'J_JUMLAH', 'label' => 'J_JUMLAH', 'type' => 'number', 'decimals' => 2, 'format_code' => '[$-421]#,##0.00'],
            ['key' => 'D', 'label' => 'D', 'type' => 'number', 'decimals' => 2, 'format_code' => '[$-421]#,##0.00'],
            ['key' => 'K', 'label' => 'K', 'type' => 'number', 'decimals' => 2, 'format_code' => '[$-421]#,##0.00'],
            ['key' => 'J_MUTASI', 'label' => 'J_MUTASI', 'type' => 'text'],
            ['key' => 'J_NAMA', 'label' => 'J_NAMA', 'type' => 'text'],
            ['key' => 'J_KET1', 'label' => 'J_KET1', 'type' => 'text'],
            ['key' => 'KET 2', 'label' => 'KET 2', 'type' => 'text'],
        ];

        $filename = sprintf('jurnal-bulanan-%s.xlsx', str_replace('-', '', $month));

        return ReportExport::xlsx($filename, 'Jurnal Bulanan', $columns, $rows);
    }

    private function resolveOutletIds(Request $request): array
    {
        $accessibleOutletIds = $this->resolveAccessibleOutletIds();
        if (empty($accessibleOutletIds)) {
            return [0];
        }

        if ($request->has('outlet_ids')) {
            $rawOutletIds = $request->input('outlet_ids', []);
            if (!is_array($rawOutletIds)) {
                return $accessibleOutletIds;
            }

            $requestedOutletIds = collect($rawOutletIds)
                ->filter(fn($id) => $id !== null && $id !== '')
                ->map(fn($id) => is_numeric($id) ? (int) $id : null)
                ->filter(fn($id) => is_int($id) && $id > 0)
                ->unique()
                ->values()
                ->all();

            if (empty($requestedOutletIds)) {
                return $accessibleOutletIds;
            }

            $resolved = array_values(array_intersect($requestedOutletIds, $accessibleOutletIds));
            return !empty($resolved) ? $resolved : $accessibleOutletIds;
        }

        if ($request->filled('outlet_id') && is_numeric($request->input('outlet_id'))) {
            $outletId = (int) $request->input('outlet_id');
            if ($outletId <= 0) {
                return $accessibleOutletIds;
            }

            return in_array($outletId, $accessibleOutletIds, true)
                ? [$outletId]
                : $accessibleOutletIds;
        }

        return $accessibleOutletIds;
    }

    private function resolveAccessibleOutlets()
    {
        $accessibleOutletIds = $this->resolveAccessibleOutletIds();
        if (empty($accessibleOutletIds)) {
            return collect();
        }

        return Outlet::query()
            ->where('is_active', true)
            ->whereIn('id', $accessibleOutletIds)
            ->orderBy('name')
            ->get();
    }

    private function resolveAccessibleOutletIds(): array
    {
        $user = auth()->user();
        if (!$user) {
            return [];
        }

        if ($user->hasRole(['admin', 'manager', 'superadmin'])) {
            return Outlet::query()
                ->where('is_active', true)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
        }

        $userOutletId = (int) ($user->outlet_id ?? 0);
        if ($userOutletId <= 0) {
            return [];
        }

        $outletExists = Outlet::query()
            ->where('is_active', true)
            ->where('id', $userOutletId)
            ->exists();

        return $outletExists ? [$userOutletId] : [];
    }

    private function buildSalesTableQuery(Request $request, string $dateFrom, string $dateTo, array $outletIds)
    {
        $query = DB::table('sales')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sale_date', [$dateFrom, $dateTo]);

        if (!empty($outletIds)) {
            $query->whereIn('sales.outlet_id', $outletIds);
        }

        if ($request->filled('user_id')) {
            $query->where('sales.user_id', $request->user_id);
        }

        if ($request->filled('payment_method_id')) {
            $paymentMethodId = $request->payment_method_id;
            $query->whereExists(function ($subQuery) use ($paymentMethodId) {
                $subQuery->select(DB::raw(1))
                    ->from('payments as payment_filter')
                    ->whereColumn('payment_filter.sale_id', 'sales.id')
                    ->where('payment_filter.payment_method_id', $paymentMethodId);
            });
        }

        if ($request->filled('product_id')) {
            $query->whereExists(function ($subQuery) use ($request) {
                $subQuery->select(DB::raw(1))
                    ->from('sale_items as product_filter')
                    ->whereColumn('product_filter.sale_id', 'sales.id')
                    ->where('product_filter.product_id', $request->product_id);
            });
        }

        return $query;
    }

    private function buildSalesSummaryFromTableQuery($salesBaseQuery): array
    {
        $totals = (clone $salesBaseQuery)
            ->selectRaw('COUNT(*) as total_transactions')
            ->selectRaw('COALESCE(SUM(sales.total_amount - COALESCE(sales.rounding_amount, 0)), 0) as total_before_rounding')
            ->selectRaw('COALESCE(SUM(sales.rounding_amount), 0) as total_rounding')
            ->selectRaw('COALESCE(SUM(sales.total_amount), 0) as total_amount')
            ->selectRaw('COALESCE(AVG(sales.total_amount), 0) as avg_per_transaction')
            ->first();

        $paymentTotals = DB::table('payments')
            ->joinSub(
                (clone $salesBaseQuery)->select('sales.id'),
                'filtered_sales',
                function ($join) {
                    $join->on('payments.sale_id', '=', 'filtered_sales.id');
                }
            )
            ->leftJoin('payment_methods', 'payments.payment_method_id', '=', 'payment_methods.id')
            ->selectRaw("COALESCE(SUM(CASE WHEN payment_methods.code = 'CASH' OR LOWER(payment_methods.name) LIKE '%cash%' OR LOWER(payment_methods.name) LIKE '%tunai%' THEN payments.amount ELSE 0 END), 0) as total_cash")
            ->selectRaw("COALESCE(SUM(CASE WHEN payment_methods.code = 'CASH' OR LOWER(payment_methods.name) LIKE '%cash%' OR LOWER(payment_methods.name) LIKE '%tunai%' THEN 0 ELSE payments.amount END), 0) as total_non_cash")
            ->first();

        return [
            'total_transactions' => (int) ($totals->total_transactions ?? 0),
            'total_before_rounding' => (float) ($totals->total_before_rounding ?? 0),
            'total_rounding' => (float) ($totals->total_rounding ?? 0),
            'total_amount' => (float) ($totals->total_amount ?? 0),
            'avg_per_transaction' => (float) ($totals->avg_per_transaction ?? 0),
            'total_cash' => (float) ($paymentTotals->total_cash ?? 0),
            'total_non_cash' => (float) ($paymentTotals->total_non_cash ?? 0),
        ];
    }

    private function buildSalesDetailPaginator(Request $request, $salesBaseQuery): LengthAwarePaginator
    {
        $paymentAggSub = DB::table('payments')
            ->join('payment_methods', 'payments.payment_method_id', '=', 'payment_methods.id')
            ->select('payments.sale_id')
            ->selectRaw('COALESCE(SUM(payments.amount), 0) as paid_amount')
            ->selectRaw("GROUP_CONCAT(DISTINCT payment_methods.name ORDER BY payment_methods.name SEPARATOR ', ') as payment_methods")
            ->groupBy('payments.sale_id');

        $filteredSalesSub = (clone $salesBaseQuery)->select(
            'sales.id',
            'sales.invoice_number',
            'sales.sale_date',
            'sales.outlet_id',
            'sales.customer_id',
            'sales.customer_name',
            'sales.tax_amount',
            'sales.service_charge_amount',
            'sales.rounding_amount',
            'sales.total_amount',
            'sales.sales_type',
            'sales.created_at'
        );

        $detailQuery = DB::table('sale_items')
            ->joinSub($filteredSalesSub, 'filtered_sales', function ($join) {
                $join->on('sale_items.sale_id', '=', 'filtered_sales.id');
            })
            ->join('outlets', 'filtered_sales.outlet_id', '=', 'outlets.id')
            ->leftJoin('customers', 'filtered_sales.customer_id', '=', 'customers.id')
            ->leftJoinSub($paymentAggSub, 'pay_agg', function ($join) {
                $join->on('filtered_sales.id', '=', 'pay_agg.sale_id');
            })
            ->select(
                'filtered_sales.invoice_number as transaction_number',
                'filtered_sales.sale_date',
                DB::raw("CASE WHEN TIME(filtered_sales.sale_date) = '00:00:00' THEN TIMESTAMP(DATE(filtered_sales.sale_date), TIME(filtered_sales.created_at)) ELSE filtered_sales.sale_date END as sale_datetime"),
                'outlets.name as outlet_name',
                DB::raw("COALESCE(NULLIF(TRIM(filtered_sales.customer_name), ''), NULLIF(TRIM(customers.name), ''), 'Walk-in') as customer_name"),
                'sale_items.product_name',
                'sale_items.quantity as qty',
                'sale_items.unit_price as price',
                DB::raw('COALESCE(sale_items.discount_amount, 0) as item_discount'),
                'sale_items.subtotal as item_subtotal',
                'sale_items.subtotal as item_total',
                'filtered_sales.tax_amount',
                'filtered_sales.service_charge_amount',
                'filtered_sales.rounding_amount',
                'filtered_sales.total_amount',
                'filtered_sales.sales_type as metode_penjualan',
                DB::raw("COALESCE(pay_agg.payment_methods, '-') as payment_methods")
            )
            ->selectRaw(
                "CASE
                    WHEN COALESCE(pay_agg.paid_amount, 0) >= filtered_sales.total_amount AND filtered_sales.total_amount > 0 THEN 'Lunas'
                    WHEN COALESCE(pay_agg.paid_amount, 0) > 0 THEN 'Parsial'
                    ELSE 'Belum Bayar'
                END as payment_status"
            );

        if ($request->filled('product_id')) {
            $detailQuery->where('sale_items.product_id', $request->product_id);
        }

        $this->applySalesDetailFilters($detailQuery, $request);

        return $detailQuery
            ->orderByDesc('filtered_sales.sale_date')
            ->orderByDesc('filtered_sales.created_at')
            ->orderBy('sale_items.id')
            ->paginate(100)
            ->withQueryString();
    }

    private function applySalesDetailFilters($detailQuery, Request $request): void
    {
        if ($request->filled('filter_transaksi')) {
            $detailQuery->where('filtered_sales.invoice_number', 'like', $this->likeValue($request->input('filter_transaksi')));
        }

        if ($request->filled('filter_tanggal')) {
            $like = $this->likeValue($request->input('filter_tanggal'));
            $detailQuery->where(function ($query) use ($like) {
                $query->whereRaw("CAST(filtered_sales.sale_date AS CHAR) LIKE ?", [$like])
                    ->orWhereRaw("DATE_FORMAT(CASE WHEN TIME(filtered_sales.sale_date) = '00:00:00' THEN TIMESTAMP(DATE(filtered_sales.sale_date), TIME(filtered_sales.created_at)) ELSE filtered_sales.sale_date END, '%d %b %Y %H:%i') LIKE ?", [$like]);
            });
        }

        if ($request->filled('filter_outlet')) {
            $detailQuery->where('outlets.name', 'like', $this->likeValue($request->input('filter_outlet')));
        }

        if ($request->filled('filter_customer')) {
            $detailQuery->whereRaw(
                "COALESCE(NULLIF(TRIM(filtered_sales.customer_name), ''), NULLIF(TRIM(customers.name), ''), 'Walk-in') LIKE ?",
                [$this->likeValue($request->input('filter_customer'))]
            );
        }

        if ($request->filled('filter_produk')) {
            $detailQuery->where('sale_items.product_name', 'like', $this->likeValue($request->input('filter_produk')));
        }

        if ($request->filled('filter_qty')) {
            $detailQuery->whereRaw("CAST(sale_items.quantity AS CHAR) LIKE ?", [$this->likeValue($request->input('filter_qty'))]);
        }

        if ($request->filled('filter_harga')) {
            $detailQuery->whereRaw("CAST(sale_items.unit_price AS CHAR) LIKE ?", [$this->likeValue($request->input('filter_harga'))]);
        }

        if ($request->filled('filter_diskon')) {
            $detailQuery->whereRaw("CAST(COALESCE(sale_items.discount_amount, 0) AS CHAR) LIKE ?", [$this->likeValue($request->input('filter_diskon'))]);
        }

        if ($request->filled('filter_subtotal')) {
            $detailQuery->whereRaw("CAST(sale_items.subtotal AS CHAR) LIKE ?", [$this->likeValue($request->input('filter_subtotal'))]);
        }

        if ($request->filled('filter_total')) {
            $detailQuery->whereRaw("CAST(sale_items.subtotal AS CHAR) LIKE ?", [$this->likeValue($request->input('filter_total'))]);
        }

        if ($request->filled('filter_status')) {
            $detailQuery->whereRaw(
                "CASE
                    WHEN COALESCE(pay_agg.paid_amount, 0) >= filtered_sales.total_amount AND filtered_sales.total_amount > 0 THEN 'Lunas'
                    WHEN COALESCE(pay_agg.paid_amount, 0) > 0 THEN 'Parsial'
                    ELSE 'Belum Bayar'
                END LIKE ?",
                [$this->likeValue($request->input('filter_status'))]
            );
        }

        if ($request->filled('filter_metode_bayar')) {
            $detailQuery->whereRaw("COALESCE(pay_agg.payment_methods, '-') LIKE ?", [$this->likeValue($request->input('filter_metode_bayar'))]);
        }

        if ($request->filled('filter_metode_jual')) {
            $detailQuery->where('filtered_sales.sales_type', 'like', $this->likeValue($request->input('filter_metode_jual')));
        }
    }

    private function likeValue(?string $keyword): string
    {
        return '%' . trim((string) $keyword) . '%';
    }

    private function paginateCollection($items, Request $request, int $perPage = 100): LengthAwarePaginator
    {
        $collection = $items instanceof Collection
            ? $items->values()
            : collect($items)->values();

        $page = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $collection->forPage($page, $perPage)->values(),
            $collection->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }
}
