<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Support\ReportExport;
use App\Services\BalanceSheetReportService;
use App\Services\ProfitLossReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatalogReportController extends Controller
{
    public function __construct(
        private readonly BalanceSheetReportService $balanceSheetReportService,
        private readonly ProfitLossReportService $profitLossReportService
    ) {
    }

    /**
     * Konfigurasi daftar laporan per kategori.
     *
     * @return array<string, array<string, mixed>>
     */
    private function categories(): array
    {
        return [
            'ikhtisar' => [
                'label' => 'Ikhtisar Bisnis',
                'items' => [
                    'balance-sheet',
                    'profit-loss',
                    'cash-bank',
                    'cash-bank-detail',
                    'ledger-detail',
                    'cash-flow',
                ],
            ],
            'penjualan' => [
                'label' => 'Penjualan',
                'items' => [
                    'sales-summary',
                    'sales',
                    'sales-order',
                    'sales-by-customer',
                    'sales-by-product',
                    'sales-by-type',
                    'sales-by-category',
                    'sales-by-payment-method',
                    'sales-by-hour',
                    'cancelled-sales',
                    'sales-stock-out',
                    'sales-modifier',
                    'sales-vs-hpp',
                    'sales-discount',
                    'shift-sessions',
                    'sales-custom-item',
                    'promo',
                    'credit-card',
                ],
            ],
            'pembelian' => [
                'label' => 'Pembelian',
                'items' => [
                    'purchase-summary',
                    'purchase-by-supplier',
                    'purchase-by-product',
                    'purchase-by-category',
                    'purchase-unpaid',
                ],
            ],
            'produk' => [
                'label' => 'Produk',
                'items' => [
                    'stock-movement',
                    'top-products',
                    'low-selling-products',
                    'inventory-value',
                ],
            ],
            'lain' => [
                'label' => 'Pendapatan & Pengeluaran Lain-Lain',
                'items' => [
                    'receivables',
                    'sales-by-service-charge',
                    'other-income-expense',
                ],
            ],
            'sdm' => [
                'label' => 'SDM & Operasional',
                'items' => [
                    'attendance-recap',
                ],
            ],
        ];
    }

    /**
     * Registry item laporan katalog.
     *
     * @return array<string, array<string, mixed>>
     */
    private function reports(): array
    {
        return [
            'balance-sheet' => ['title' => 'Neraca', 'implemented' => true],
            'profit-loss' => ['title' => 'Laba & Rugi', 'implemented' => true, 'existing_route' => 'admin.reports.profit-loss.index'],
            'cash-bank' => ['title' => 'Kas dan Bank', 'implemented' => true],
            'cash-bank-detail' => ['title' => 'Kas dan Bank Detil', 'implemented' => true],
            'ledger-detail' => ['title' => 'Detil Ledger', 'implemented' => true],
            'cash-flow' => ['title' => 'Arus Kas', 'implemented' => true],
            'sales-summary' => ['title' => 'Ringkasan Penjualan', 'implemented' => true],
            'sales' => ['title' => 'Penjualan', 'implemented' => true, 'existing_route' => 'admin.reports.sales.index'],
            'sales-daily-summary' => ['title' => 'Ringkasan Penjualan Harian', 'implemented' => false],
            'sales-order' => ['title' => 'Order Penjualan', 'implemented' => false],
            'sales-by-customer' => ['title' => 'Penjualan per Pelanggan', 'implemented' => false],
            'sales-by-product' => ['title' => 'Penjualan per Produk', 'implemented' => false, 'existing_route' => 'admin.reports.sales.products'],
            'sales-by-type' => ['title' => 'Penjualan per Tipe Penjualan', 'implemented' => false],
            'sales-by-payment-method' => ['title' => 'Penjualan per Metode Pembayaran', 'implemented' => true],
            'sales-by-category' => ['title' => 'Penjualan per Kategori Produk', 'implemented' => false],
            'sales-by-hour' => ['title' => 'Penjualan per Jam', 'implemented' => false],
            'cancelled-sales' => ['title' => 'Penjualan yang Dibatalkan', 'implemented' => false],
            'sales-stock-out' => ['title' => 'Stok Keluar dari Penjualan', 'implemented' => false],
            'sales-modifier' => ['title' => 'Penjualan Modifier', 'implemented' => false],
            'sales-vs-hpp' => ['title' => 'Penjualan Vs HPP', 'implemented' => true],
            'waiter-performance' => ['title' => 'Kinerja Pelayan Berdasarkan Penjualan', 'implemented' => false],
            'sales-discount' => ['title' => 'Laporan Diskon Penjualan', 'implemented' => false],
            'shift-sessions' => ['title' => 'Sesi Shift POS', 'implemented' => false],
            'sales-custom-item' => ['title' => 'Penjualan per Custom Item', 'implemented' => false],
            'receivables' => ['title' => 'Piutang', 'implemented' => false],
            'promo' => ['title' => 'Promo', 'implemented' => false],
            'sales-by-service-charge' => ['title' => 'Penjualan per Biaya Layanan', 'implemented' => false],
            'daily-outlet-summary' => ['title' => 'Ringkasan Penjualan Harian Per Outlet', 'implemented' => false],
            'credit-card' => ['title' => 'Kartu Kredit', 'implemented' => false],
            'purchase-summary' => ['title' => 'Ringkasan Pembelian', 'implemented' => false],
            'purchase-by-supplier' => ['title' => 'Pembelian per Supplier', 'implemented' => false],
            'purchase-by-product' => ['title' => 'Pembelian per Produk', 'implemented' => false],
            'purchase-by-category' => ['title' => 'Pembelian per Kategori', 'implemented' => false],
            'purchase-unpaid' => ['title' => 'Pembelian Belum Lunas', 'implemented' => false],
            'stock-movement' => ['title' => 'Pergerakan Stok Produk', 'implemented' => false],
            'top-products' => ['title' => 'Produk Terlaris', 'implemented' => false],
            'low-selling-products' => ['title' => 'Produk Kurang Laku', 'implemented' => false],
            'inventory-value' => ['title' => 'Nilai Persediaan', 'implemented' => false],
            'other-income-expense' => ['title' => 'Pendapatan Lain-Lain', 'implemented' => false],
            'attendance-recap' => ['title' => 'Rekap Absensi Karyawan', 'implemented' => true, 'existing_route' => 'admin.reports.attendance.index'],
        ];
    }

    /**
     * Halaman daftar katalog laporan.
     */
    public function index()
    {
        return view('admin.reports.index', [
            'categories' => $this->categories(),
            'reports' => $this->reports(),
        ]);
    }

    /**
     * Halaman detail laporan per item katalog.
     */
    public function show(Request $request, string $slug)
    {
        $reports = $this->reports();
        abort_unless(isset($reports[$slug]), 404);

        if ($slug === 'sales') {
            return redirect()->route('admin.reports.sales.index', array_filter([
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
                'outlet_id' => $request->input('outlet_id'),
                'view_mode' => $request->input('view_mode'),
                'tab' => $request->input('tab', 'penjualan'),
            ]));
        }

        if ($slug === 'attendance-recap') {
            return redirect()->route('admin.reports.attendance.index', array_filter([
                'period' => $request->input('period'),
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
                'outlet_id' => $request->input('outlet_id'),
                'user_id' => $request->input('user_id'),
                'status' => $request->input('status'),
                'tab' => $request->input('tab', 'sdm'),
            ]));
        }

        $report = $reports[$slug];
        $financeSlugs = ['balance-sheet', 'profit-loss', 'cash-bank', 'cash-bank-detail', 'ledger-detail', 'cash-flow', 'sales-summary'];
        $defaultDateFrom = in_array($slug, $financeSlugs, true) ? now()->startOfMonth()->toDateString() : now()->toDateString();
        $defaultDateTo = in_array($slug, $financeSlugs, true) ? now()->endOfMonth()->toDateString() : now()->toDateString();

        $dateFrom = $request->input('date_from', $defaultDateFrom);
        $dateTo = $request->input('date_to', $defaultDateTo);
        $outletId = $request->input('outlet_id');
        $outlets = Outlet::where('is_active', true)->orderBy('name')->get();

        $rows = collect();
        $summary = [];
        $meta = [];
        $viewType = 'placeholder';

        // Implementasi: Penjualan per Metode Pembayaran
        if ($slug === 'sales-by-payment-method') {
            $viewType = 'payment-method';
            $query = DB::table('payments')
                ->join('sales', 'payments.sale_id', '=', 'sales.id')
                ->join('payment_methods', 'payments.payment_method_id', '=', 'payment_methods.id')
                ->where('sales.status', 'completed')
                ->whereBetween('sales.sale_date', [$dateFrom, $dateTo]);

            if (!empty($outletId)) {
                $query->where('sales.outlet_id', $outletId);
            }

            $rows = $query
                ->select(
                    'payment_methods.id',
                    'payment_methods.name as payment_method_name',
                    DB::raw('COUNT(DISTINCT sales.id) as total_transactions'),
                    DB::raw('SUM(payments.amount) as total_amount')
                )
                ->groupBy('payment_methods.id', 'payment_methods.name')
                ->orderByDesc('total_amount')
                ->get();

            $summary = [
                'total_transactions' => (int) $rows->sum('total_transactions'),
                'total_amount' => (float) $rows->sum('total_amount'),
            ];
        }

        // Implementasi: Neraca (Balance Sheet) v1
        if ($slug === 'balance-sheet') {
            $viewType = 'balance-sheet-final';
            $summary = $this->balanceSheetReportService->generate($dateFrom, $dateTo, !empty($outletId) ? (int) $outletId : null);
            $meta = [
                'notes' => [
                    'Sumber data neraca saat ini menggunakan mutasi cash transaction yang terhubung ke COA tipe aset/kewajiban/ekuitas.',
                    'Jika akun neraca masih nol, pastikan transaksi sudah diposting ke COA neraca yang sesuai.',
                    'Kontrol Kas & Bank dipakai sebagai pembanding untuk validasi mapping akun aset.',
                ],
            ];
        }

        if ($slug === 'profit-loss') {
            $viewType = 'profit-loss';
            $summary = $this->profitLossReportService->generate($dateFrom, $dateTo, !empty($outletId) ? (int) $outletId : null);
        }

        if ($slug === 'sales-summary') {
            $viewType = 'sales-summary';

            $salesBaseQuery = DB::table('sales')
                ->where('sales.status', 'completed')
                ->whereBetween('sales.sale_date', [$dateFrom, $dateTo]);

            if (!empty($outletId)) {
                $salesBaseQuery->where('sales.outlet_id', $outletId);
            }

            $totals = (clone $salesBaseQuery)
                ->selectRaw('COUNT(*) as total_transactions')
                ->selectRaw('COALESCE(SUM(sales.subtotal), 0) as subtotal')
                ->selectRaw('COALESCE(SUM(sales.discount_amount), 0) as total_discount')
                ->selectRaw('COALESCE(SUM(sales.tax_amount), 0) as total_tax')
                ->selectRaw('COALESCE(SUM(sales.service_charge_amount), 0) as total_service_charge')
                ->selectRaw('COALESCE(SUM(sales.total_amount), 0) as total_amount')
                ->selectRaw('COALESCE(AVG(sales.total_amount), 0) as avg_transaction')
                ->first();

            $dailyRows = (clone $salesBaseQuery)
                ->selectRaw('sales.sale_date')
                ->selectRaw('COUNT(*) as total_transactions')
                ->selectRaw('COALESCE(SUM(sales.total_amount), 0) as total_amount')
                ->groupBy('sales.sale_date')
                ->orderByDesc('sales.sale_date')
                ->get();

            $outletRows = (clone $salesBaseQuery)
                ->join('outlets', 'sales.outlet_id', '=', 'outlets.id')
                ->select(
                    'sales.outlet_id',
                    'outlets.name as outlet_name'
                )
                ->selectRaw('COUNT(*) as total_transactions')
                ->selectRaw('COALESCE(SUM(sales.total_amount), 0) as total_amount')
                ->groupBy('sales.outlet_id', 'outlets.name')
                ->orderByDesc('total_amount')
                ->get();

            $paymentRowsQuery = DB::table('payments')
                ->join('sales', 'payments.sale_id', '=', 'sales.id')
                ->join('payment_methods', 'payments.payment_method_id', '=', 'payment_methods.id')
                ->where('sales.status', 'completed')
                ->whereBetween('sales.sale_date', [$dateFrom, $dateTo]);

            if (!empty($outletId)) {
                $paymentRowsQuery->where('sales.outlet_id', $outletId);
            }

            $paymentRows = $paymentRowsQuery
                ->select(
                    'payment_methods.id as payment_method_id',
                    'payment_methods.name as payment_method_name'
                )
                ->selectRaw('COUNT(DISTINCT sales.id) as total_transactions')
                ->selectRaw('COALESCE(SUM(payments.amount), 0) as total_amount')
                ->groupBy('payment_methods.id', 'payment_methods.name')
                ->orderByDesc('total_amount')
                ->get();

            $productRows = (clone $salesBaseQuery)
                ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                ->join('products', 'sale_items.product_id', '=', 'products.id')
                ->select(
                    'products.id as product_id',
                    'products.name as product_name',
                    DB::raw("COALESCE(sales.sales_type, 'Normal') as sales_type")
                )
                ->selectRaw('COALESCE(SUM(sale_items.quantity), 0) as total_qty')
                ->selectRaw('COALESCE(SUM(sale_items.subtotal), 0) as total_amount')
                ->groupBy('products.id', 'products.name', 'sales.sales_type')
                ->orderByDesc('total_qty')
                ->limit(30)
                ->get();

            $voidBaseQuery = DB::table('sales')
                ->whereBetween('sales.sale_date', [$dateFrom, $dateTo])
                ->whereIn('sales.status', ['cancelled', 'void']);

            if (!empty($outletId)) {
                $voidBaseQuery->where('sales.outlet_id', $outletId);
            }

            $voidTotals = (clone $voidBaseQuery)
                ->selectRaw('COUNT(*) as total_invoices')
                ->selectRaw('COALESCE(SUM(sales.total_amount), 0) as total_amount')
                ->first();

            $voidItems = (clone $voidBaseQuery)
                ->leftJoin('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                ->selectRaw('COALESCE(SUM(sale_items.quantity), 0) as total_items')
                ->value('total_items');

            $salesTypeRows = (clone $salesBaseQuery)
                ->selectRaw("COALESCE(sales.sales_type, 'Normal') as sales_type")
                ->selectRaw('COALESCE(SUM(sales.total_amount), 0) as total_amount')
                ->groupBy('sales.sales_type')
                ->orderBy('sales_type')
                ->get();

            $totalDays = max(
                1,
                \Carbon\Carbon::parse($dateFrom)->diffInDays(\Carbon\Carbon::parse($dateTo)) + 1
            );
            $totalPax = (int) ($totals->total_transactions ?? 0);
            $avgPaxPerDay = $totalPax / $totalDays;
            $avgBillPerPax = $totalPax > 0 ? ((float) ($totals->total_amount ?? 0) / $totalPax) : 0;

            $selectedOutletName = null;
            if (!empty($outletId)) {
                $selectedOutletName = Outlet::query()->where('id', $outletId)->value('name');
            }

            $summary = [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'selected_outlet_name' => $selectedOutletName,
                'total_transactions' => (int) ($totals->total_transactions ?? 0),
                'total_sales' => (float) ($totals->subtotal ?? 0),
                'total_discount' => (float) ($totals->total_discount ?? 0),
                'total_tax' => (float) ($totals->total_tax ?? 0),
                'total_service_charge' => (float) ($totals->total_service_charge ?? 0),
                'total_adjustment' => 0.0,
                'total_amount' => (float) ($totals->total_amount ?? 0),
                'avg_transaction' => (float) ($totals->avg_transaction ?? 0),
                'void_invoices' => (int) ($voidTotals->total_invoices ?? 0),
                'void_items' => (float) ($voidItems ?? 0),
                'void_total' => (float) ($voidTotals->total_amount ?? 0),
                'total_pax' => $totalPax,
                'avg_pax_per_day' => $avgPaxPerDay,
                'avg_bill_per_pax' => $avgBillPerPax,
                'daily_rows' => $dailyRows,
                'outlet_rows' => $outletRows,
                'payment_rows' => $paymentRows,
                'sales_type_rows' => $salesTypeRows,
                'product_rows' => $productRows,
            ];
        }

        if ($slug === 'sales-vs-hpp') {
            $viewType = 'sales-vs-hpp';

            $salesItemBase = DB::table('sale_items')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->join('outlets', 'sales.outlet_id', '=', 'outlets.id')
                ->where('sales.status', 'completed')
                ->whereBetween('sales.sale_date', [$dateFrom, $dateTo]);

            if (!empty($outletId)) {
                $salesItemBase->where('sales.outlet_id', $outletId);
            }

            $rows = (clone $salesItemBase)
                ->select(
                    'sales.invoice_number as transaction_number',
                    'sales.sale_date',
                    'outlets.name as outlet_name',
                    'sale_items.product_name',
                    'sale_items.quantity as qty',
                    'sale_items.subtotal as total_amount',
                    'sale_items.cogs as hpp_amount'
                )
                ->orderByDesc('sales.sale_date')
                ->orderByDesc('sales.id')
                ->orderByDesc('sale_items.id')
                ->get()
                ->map(function ($row) {
                    $grossProfit = (float) $row->total_amount - (float) $row->hpp_amount;
                    $margin = (float) $row->total_amount > 0
                        ? round(($grossProfit / (float) $row->total_amount) * 100, 2)
                        : 0;

                    $row->gross_profit = $grossProfit;
                    $row->margin_percent = $margin;

                    return $row;
                });

            $totalSales = (float) $rows->sum('total_amount');
            $totalHpp = (float) $rows->sum('hpp_amount');
            $totalGrossProfit = $totalSales - $totalHpp;

            $summary = [
                'total_items' => (int) $rows->pluck('product_name')->unique()->count(),
                'total_qty' => (float) $rows->sum('qty'),
                'total_sales' => $totalSales,
                'total_hpp' => $totalHpp,
                'total_gross_profit' => $totalGrossProfit,
                'gross_margin_percent' => $totalSales > 0 ? round(($totalGrossProfit / $totalSales) * 100, 2) : 0,
            ];
        }

        if ($slug === 'cash-bank') {
            $viewType = 'cash-bank-summary';
            $rows = $this->cashAccountSnapshots($dateFrom, $dateTo, !empty($outletId) ? (int) $outletId : null);
            $summary = [
                'total_cash' => (float) $rows->where('type', 'cash')->sum('ending_balance'),
                'total_bank' => (float) $rows->where('type', 'bank')->sum('ending_balance'),
                'total_balance' => (float) $rows->sum('ending_balance'),
                'as_of' => $dateTo,
            ];
        }

        if ($slug === 'cash-bank-detail') {
            $viewType = 'cash-bank-detail';
            $rows = $this->cashAccountSnapshots($dateFrom, $dateTo, !empty($outletId) ? (int) $outletId : null);
            $summary = [
                'beginning_balance' => (float) $rows->sum('beginning_balance'),
                'total_in' => (float) $rows->sum('total_in'),
                'total_out' => (float) $rows->sum('total_out'),
                'ending_balance' => (float) $rows->sum('ending_balance'),
            ];
        }

        if ($slug === 'ledger-detail') {
            $viewType = 'ledger-detail';

            $ledgerQuery = DB::table('cash_transactions')
                ->leftJoin('coa_accounts', 'cash_transactions.coa_account_id', '=', 'coa_accounts.id')
                ->leftJoin('cash_accounts', 'cash_transactions.cash_account_id', '=', 'cash_accounts.id')
                ->leftJoin('outlets', 'cash_accounts.outlet_id', '=', 'outlets.id')
                ->whereBetween('cash_transactions.transaction_date', [$dateFrom, $dateTo]);

            if (!empty($outletId)) {
                $ledgerQuery->where('cash_accounts.outlet_id', $outletId);
            }

            $totals = (clone $ledgerQuery)
                ->selectRaw("SUM(CASE WHEN cash_transactions.type = 'in' THEN cash_transactions.amount ELSE 0 END) as total_in")
                ->selectRaw("SUM(CASE WHEN cash_transactions.type = 'out' THEN cash_transactions.amount ELSE 0 END) as total_out")
                ->first();

            $rows = $ledgerQuery
                ->select(
                    'cash_transactions.transaction_date',
                    'cash_transactions.transaction_number',
                    'cash_transactions.type',
                    'cash_transactions.amount',
                    'cash_transactions.description',
                    'cash_transactions.reference_type',
                    'coa_accounts.code as coa_code',
                    'coa_accounts.name as coa_name',
                    'cash_accounts.name as cash_account_name',
                    'outlets.name as outlet_name'
                )
                ->orderByDesc('cash_transactions.transaction_date')
                ->orderByDesc('cash_transactions.created_at')
                ->limit(500)
                ->get();

            $summary = [
                'total_in' => (float) ($totals->total_in ?? 0),
                'total_out' => (float) ($totals->total_out ?? 0),
                'net' => (float) (($totals->total_in ?? 0) - ($totals->total_out ?? 0)),
                'row_count' => $rows->count(),
            ];
        }

        if ($slug === 'cash-flow') {
            $viewType = 'cash-flow';

            $flowQuery = DB::table('cash_transactions')
                ->leftJoin('coa_accounts', 'cash_transactions.coa_account_id', '=', 'coa_accounts.id')
                ->leftJoin('cash_accounts', 'cash_transactions.cash_account_id', '=', 'cash_accounts.id')
                ->whereBetween('cash_transactions.transaction_date', [$dateFrom, $dateTo]);

            if (!empty($outletId)) {
                $flowQuery->where('cash_accounts.outlet_id', $outletId);
            }

            $rows = $flowQuery
                ->selectRaw("COALESCE(coa_accounts.`group`, 'TANPA COA') as flow_group")
                ->selectRaw("SUM(CASE WHEN cash_transactions.type = 'in' THEN cash_transactions.amount ELSE 0 END) as total_in")
                ->selectRaw("SUM(CASE WHEN cash_transactions.type = 'out' THEN cash_transactions.amount ELSE 0 END) as total_out")
                ->selectRaw("SUM(CASE WHEN cash_transactions.type = 'in' THEN cash_transactions.amount ELSE -cash_transactions.amount END) as net_cash_flow")
                ->groupBy('flow_group')
                ->orderBy('flow_group')
                ->get();

            $summary = [
                'total_in' => (float) $rows->sum('total_in'),
                'total_out' => (float) $rows->sum('total_out'),
                'net' => (float) $rows->sum('net_cash_flow'),
            ];
        }

        $format = $request->input('format');
        if (in_array($format, ['xlsx', 'pdf'], true)) {
            [$exportColumns, $exportRows] = $this->buildExportPayload($viewType, $rows, $summary);
            $safeSlug = str_replace('_', '-', $slug);
            $filename = sprintf('%s-%s-sd-%s.%s', $safeSlug, str_replace('-', '', $dateFrom), str_replace('-', '', $dateTo), $format);

            if ($format === 'pdf') {
                return ReportExport::pdf($filename, $report['title'], $exportColumns, $exportRows);
            }

            return ReportExport::xlsx($filename, $report['title'], $exportColumns, $exportRows);
        }

        return view('admin.reports.catalog-show', [
            'slug' => $slug,
            'report' => $report,
            'categories' => $this->categories(),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'outletId' => $outletId,
            'outlets' => $outlets,
            'rows' => $rows,
            'summary' => $summary,
            'meta' => $meta,
            'viewType' => $viewType,
        ]);
    }

    private function buildExportPayload(string $viewType, $rows, array $summary): array
    {
        $rowsCollection = $rows instanceof \Illuminate\Support\Collection ? $rows : collect($rows);

        if ($viewType === 'sales-vs-hpp') {
            return [[
                ['key' => 'transaction_number', 'label' => 'No_Transaksi', 'type' => 'text'],
                ['key' => 'sale_date', 'label' => 'Tanggal', 'type' => 'text'],
                ['key' => 'outlet_name', 'label' => 'Outlet', 'type' => 'text'],
                ['key' => 'product_name', 'label' => 'Produk', 'type' => 'text'],
                ['key' => 'qty', 'label' => 'Qty', 'type' => 'number', 'decimals' => 2],
                ['key' => 'total_amount', 'label' => 'Total', 'type' => 'number', 'decimals' => 2],
                ['key' => 'hpp_amount', 'label' => 'Hpp', 'type' => 'number', 'decimals' => 2],
                ['key' => 'gross_profit', 'label' => 'Laba Kotor', 'type' => 'number', 'decimals' => 2],
                ['key' => 'margin_percent', 'label' => 'Margin', 'type' => 'number', 'decimals' => 2],
            ], $rowsCollection->map(fn ($row) => (array) $row)->all()];
        }

        if ($rowsCollection->isNotEmpty()) {
            $first = (array) $rowsCollection->first();
            $columns = collect(array_keys($first))->map(function ($key) use ($first) {
                $value = $first[$key] ?? null;
                return [
                    'key' => $key,
                    'label' => ucwords(str_replace('_', ' ', (string) $key)),
                    'type' => is_numeric($value) ? 'number' : 'text',
                    'decimals' => is_numeric($value) && ((float) $value !== (float) ((int) $value)) ? 2 : 0,
                ];
            })->all();

            return [$columns, $rowsCollection->map(fn ($row) => (array) $row)->all()];
        }

        $fallbackRows = collect($summary)->map(function ($value, $key) {
            return ['metric' => (string) $key, 'value' => is_scalar($value) ? $value : json_encode($value)];
        })->values()->all();

        return [[
            ['key' => 'metric', 'label' => 'Metric', 'type' => 'text'],
            ['key' => 'value', 'label' => 'Value', 'type' => 'text'],
        ], $fallbackRows];
    }

    private function cashAccountSnapshots(string $dateFrom, string $dateTo, ?int $outletId = null)
    {
        $beforeSub = DB::table('cash_transactions')
            ->select(
                'cash_account_id',
                DB::raw("SUM(CASE WHEN type = 'in' THEN amount ELSE -amount END) as movement_before")
            )
            ->whereDate('transaction_date', '<', $dateFrom)
            ->groupBy('cash_account_id');

        $inSub = DB::table('cash_transactions')
            ->select('cash_account_id', DB::raw('SUM(amount) as total_in'))
            ->where('type', 'in')
            ->whereBetween('transaction_date', [$dateFrom, $dateTo])
            ->groupBy('cash_account_id');

        $outSub = DB::table('cash_transactions')
            ->select('cash_account_id', DB::raw('SUM(amount) as total_out'))
            ->where('type', 'out')
            ->whereBetween('transaction_date', [$dateFrom, $dateTo])
            ->groupBy('cash_account_id');

        $query = DB::table('cash_accounts')
            ->leftJoinSub($beforeSub, 'mov_before', function ($join) {
                $join->on('cash_accounts.id', '=', 'mov_before.cash_account_id');
            })
            ->leftJoinSub($inSub, 'mov_in', function ($join) {
                $join->on('cash_accounts.id', '=', 'mov_in.cash_account_id');
            })
            ->leftJoinSub($outSub, 'mov_out', function ($join) {
                $join->on('cash_accounts.id', '=', 'mov_out.cash_account_id');
            })
            ->leftJoin('outlets', 'cash_accounts.outlet_id', '=', 'outlets.id')
            ->where('cash_accounts.is_active', true);

        if (!empty($outletId)) {
            $query->where('cash_accounts.outlet_id', $outletId);
        }

        return $query
            ->select(
                'cash_accounts.id',
                'cash_accounts.name',
                'cash_accounts.code',
                'cash_accounts.type',
                'outlets.name as outlet_name',
                DB::raw('cash_accounts.opening_balance + COALESCE(mov_before.movement_before, 0) as beginning_balance'),
                DB::raw('COALESCE(mov_in.total_in, 0) as total_in'),
                DB::raw('COALESCE(mov_out.total_out, 0) as total_out'),
                DB::raw('(cash_accounts.opening_balance + COALESCE(mov_before.movement_before, 0) + COALESCE(mov_in.total_in, 0) - COALESCE(mov_out.total_out, 0)) as ending_balance')
            )
            ->orderBy('cash_accounts.type')
            ->orderBy('cash_accounts.name')
            ->get();
    }
}
