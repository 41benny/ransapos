<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Exports\GeneratorReportExport;
use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\User;
use App\Support\ReportExport;
use App\Support\SpecialPromotion;
use App\Services\BalanceSheetReportService;
use App\Services\ProfitLossReportService;
use Illuminate\Http\Request;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Generator;

class CatalogReportController extends Controller
{
    public function __construct(
        private readonly BalanceSheetReportService $balanceSheetReportService,
        private readonly ProfitLossReportService $profitLossReportService
    ) {}

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
                    'sales-daily-summary',
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
                    'stock-transfer',
                    'stock-adjustments',
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
            'sales-daily-summary' => ['title' => 'Ringkasan Penjualan Harian', 'implemented' => true, 'existing_route' => 'admin.reports.sales.daily'],
            'sales-order' => ['title' => 'Order Penjualan', 'implemented' => false],
            'sales-by-customer' => ['title' => 'Penjualan per Pelanggan', 'implemented' => false],
            'sales-by-product' => ['title' => 'Penjualan per Produk', 'implemented' => true, 'existing_route' => 'admin.reports.sales.products'],
            'sales-by-type' => ['title' => 'Penjualan per Tipe Penjualan', 'implemented' => false],
            'sales-by-payment-method' => ['title' => 'Penjualan per Metode Pembayaran', 'implemented' => true],
            'sales-by-category' => ['title' => 'Penjualan per Kategori Produk', 'implemented' => false],
            'sales-by-hour' => ['title' => 'Penjualan per Jam', 'implemented' => false],
            'cancelled-sales' => ['title' => 'Penjualan yang Dibatalkan', 'implemented' => true],
            'sales-stock-out' => ['title' => 'Stok Keluar dari Penjualan', 'implemented' => false],
            'sales-modifier' => ['title' => 'Penjualan Modifier', 'implemented' => false],
            'sales-vs-hpp' => ['title' => 'Penjualan Vs HPP', 'implemented' => true],
            'waiter-performance' => ['title' => 'Kinerja Pelayan Berdasarkan Penjualan', 'implemented' => false],
            'sales-discount' => ['title' => 'Laporan Diskon Penjualan', 'implemented' => true],
            'shift-sessions' => ['title' => 'Sesi Shift POS', 'implemented' => true, 'existing_route' => 'admin.reports.shifts.index'],
            'sales-custom-item' => ['title' => 'Penjualan per Custom Item', 'implemented' => false],
            'receivables' => ['title' => 'Piutang', 'implemented' => false],
            'promo' => ['title' => 'Promo', 'implemented' => true],
            'sales-by-service-charge' => ['title' => 'Penjualan per Biaya Layanan', 'implemented' => false],
            'daily-outlet-summary' => ['title' => 'Ringkasan Penjualan Harian Per Outlet', 'implemented' => false],
            'credit-card' => ['title' => 'Kartu Kredit', 'implemented' => false],
            'purchase-summary' => ['title' => 'Ringkasan Pembelian', 'implemented' => true],
            'purchase-by-supplier' => ['title' => 'Pembelian per Supplier', 'implemented' => true],
            'purchase-by-product' => ['title' => 'Pembelian per Produk', 'implemented' => true],
            'purchase-by-category' => ['title' => 'Pembelian per Kategori', 'implemented' => true],
            'purchase-unpaid' => ['title' => 'Pembelian Belum Lunas', 'implemented' => true],
            'stock-movement' => ['title' => 'Pergerakan Stok Produk', 'implemented' => true],
            'stock-transfer' => ['title' => 'Mutasi Persediaan Antar Outlet', 'implemented' => true],
            'stock-adjustments' => ['title' => 'Riwayat Adjustment Stok', 'implemented' => true],
            'top-products' => ['title' => 'Produk Terlaris', 'implemented' => false],
            'low-selling-products' => ['title' => 'Produk Kurang Laku', 'implemented' => false],
            'inventory-value' => ['title' => 'Nilai Persediaan', 'implemented' => true],
            'other-income-expense' => ['title' => 'Pendapatan Lain-Lain', 'implemented' => false],
            'attendance-recap' => ['title' => 'Rekap Absensi Karyawan', 'implemented' => true, 'existing_route' => 'admin.reports.attendance.index'],
        ];
    }

    /**
     * Mapping slug laporan ke permission view yang relevan.
     *
     * Jika slug tidak ada di mapping, akses ditolak (default deny).
     *
     * @return array<string, string>
     */
    private function reportViewPermissions(): array
    {
        return [
            // Ikhtisar
            'balance-sheet' => 'reports.profit.view',
            'profit-loss' => 'reports.profit.view',
            'cash-bank' => 'reports.profit.view',
            'cash-bank-detail' => 'reports.profit.view',
            'ledger-detail' => 'reports.profit.view',
            'cash-flow' => 'reports.profit.view',

            // Penjualan
            'sales-summary' => 'reports.sales.view',
            'sales' => 'reports.sales.view',
            'sales-daily-summary' => 'reports.daily.view',
            'sales-order' => 'reports.sales.view',
            'sales-by-customer' => 'reports.sales.view',
            'sales-by-product' => 'reports.product.view',
            'sales-by-type' => 'reports.sales.view',
            'sales-by-payment-method' => 'reports.sales.view',
            'sales-by-category' => 'reports.sales.view',
            'sales-by-hour' => 'reports.sales.view',
            'cancelled-sales' => 'reports.sales.view',
            'sales-stock-out' => 'reports.sales.view',
            'sales-modifier' => 'reports.sales.view',
            'sales-vs-hpp' => 'reports.sales.view',
            'waiter-performance' => 'reports.sales.view',
            'sales-discount' => 'reports.sales.view',
            'shift-sessions' => 'reports.shift.view',
            'sales-custom-item' => 'reports.sales.view',
            'daily-outlet-summary' => 'reports.daily.view',
            'credit-card' => 'reports.sales.view',
            'promo' => 'promo-vouchers.view',
            'sales-by-service-charge' => 'reports.sales.view',

            // Pembelian
            'purchase-summary' => 'purchases.view',
            'purchase-by-supplier' => 'purchases.view',
            'purchase-by-product' => 'purchases.view',
            'purchase-by-category' => 'purchases.view',
            'purchase-unpaid' => 'purchases.view',
            'receivables' => 'purchases.view',

            // Produk
            'stock-movement' => 'stocks.view',
            'stock-transfer' => 'stocks.view',
            'stock-adjustments' => 'stocks.view',
            'top-products' => 'stocks.view',
            'low-selling-products' => 'stocks.view',
            'inventory-value' => 'stocks.view',

            // Lain-lain / SDM
            'other-income-expense' => 'expenses.report.view',
            'attendance-recap' => 'reports.attendance.view',
        ];
    }

    private function canAccessReportSlug(string $slug): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        $permission = $this->reportViewPermissions()[$slug] ?? null;
        // Default deny: setiap slug baru wajib dimapping ke permission agar tidak bocor akses.
        if (!$permission) {
            return false;
        }

        return $user->hasPermission($permission);
    }

    /**
     * @param  array<string, array<string, mixed>>  $categories
     * @param  array<string, array<string, mixed>>  $reports
     * @return array{0: array<string, array<string, mixed>>, 1: array<string, array<string, mixed>>}
     */
    private function filterReportsByPermission(array $categories, array $reports): array
    {
        $allowedReports = [];
        foreach ($reports as $slug => $report) {
            if ($this->canAccessReportSlug($slug)) {
                $allowedReports[$slug] = $report;
            }
        }

        foreach ($categories as $key => $category) {
            $allowedItems = array_values(array_filter(
                $category['items'] ?? [],
                fn ($slug) => isset($allowedReports[$slug])
            ));

            if ($allowedItems === []) {
                unset($categories[$key]);
                continue;
            }

            $categories[$key]['items'] = $allowedItems;
        }

        return [$categories, $allowedReports];
    }

    /**
     * Halaman daftar katalog laporan.
     */
    public function index()
    {
        [$categories, $reports] = $this->filterReportsByPermission(
            $this->categories(),
            $this->reports()
        );

        return view('admin.reports.index', [
            'categories' => $categories,
            'reports' => $reports,
        ]);
    }

    /**
     * Detail penjualan per bill (JSON) untuk modal popup.
     */
    public function saleDetail(\App\Models\Sale $sale)
    {
        $sale->load(['items.product', 'payments.paymentMethod', 'outlet', 'user', 'customer', 'promotion', 'voucher']);

        $items = $sale->items->map(function ($item) use ($sale) {
            $unitPrice = (float) $item->unit_price;
            $originalPrice = $unitPrice;

            // Reconstruct original selling price if unit_price was zeroed (e.g. compliment)
            if ($unitPrice <= 0 && $item->product) {
                $originalPrice = (float) $item->product->selling_price;
            }

            $grossAmount = $originalPrice * (float) $item->quantity;
            $paidAmount = (float) $item->subtotal;
            $itemDiscount = (float) $item->discount_amount;

            // If originalPrice was reconstructed and gross < paid, use paid amount
            if ($grossAmount < $paidAmount) {
                $grossAmount = $paidAmount;
            }

            return [
                'product_name' => $item->product_name,
                'product_sku' => $item->product_sku,
                'quantity' => (float) $item->quantity,
                'unit_price' => $unitPrice,
                'original_price' => $originalPrice,
                'discount_amount' => $itemDiscount,
                'subtotal' => $paidAmount,
                'gross_amount' => $grossAmount,
                'notes' => $item->notes,
            ];
        });

        $payments = $sale->payments->map(fn($p) => [
            'method' => $p->paymentMethod->name ?? '-',
            'amount' => (float) $p->amount,
            'reference_number' => $p->reference_number,
        ]);

        $grossValue = $items->sum('gross_amount');
        $itemsPaid = $items->sum('subtotal');
        $itemLevelDiscount = max(0, $grossValue - $itemsPaid);
        $headerDiscount = (float) $sale->discount_amount;
        $effectiveDiscount = $itemLevelDiscount + $headerDiscount;

        return response()->json([
            'id' => $sale->id,
            'invoice_number' => $sale->invoice_number,
            'sale_date' => $this->resolveSaleDateLabel($sale),
            'status' => $sale->status,
            'sales_type' => $sale->sales_type ?? 'regular',
            'sales_type_label' => $this->formatSalesTypeLabel($sale->sales_type),
            'outlet_name' => $this->resolveDisplayText(data_get($sale, 'outlet.name')),
            'cashier_name' => $this->resolveDisplayText(data_get($sale, 'user.name')),
            'customer_name' => $this->resolveDisplayText($sale->resolved_customer_name, 'Walk-in'),
            'promotion_name' => $sale->promotion->name ?? null,
            'voucher_name' => $sale->voucher->name ?? null,
            'voucher_code' => $sale->voucher_code ?? $sale->voucher?->code,
            'discount_type' => $sale->discount_type,
            'discount_value' => (float) $sale->discount_value,
            'header_discount' => $headerDiscount,
            'subtotal' => (float) $sale->subtotal,
            'tax_amount' => (float) $sale->tax_amount,
            'service_charge_amount' => (float) $sale->service_charge_amount,
            'rounding_amount' => (float) $sale->rounding_amount,
            'total_amount' => (float) $sale->total_amount,
            'gross_value' => $grossValue,
            'item_level_discount' => $itemLevelDiscount,
            'effective_discount' => $effectiveDiscount,
            'notes' => $sale->notes,
            'items' => $items->values(),
            'payments' => $payments->values(),
        ]);
    }

    /**
     * Halaman detail laporan per item katalog.
     */
    public function show(Request $request, string $slug)
    {
        $allReports = $this->reports();
        abort_unless(isset($allReports[$slug]), 404);
        abort_unless($this->canAccessReportSlug($slug), 403, 'Anda tidak memiliki permission untuk laporan ini.');

        [$categories, $reports] = $this->filterReportsByPermission(
            $this->categories(),
            $allReports
        );

        if ($slug === 'sales') {
            return redirect()->route('admin.reports.sales.index', array_filter([
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
                'outlet_id' => $request->input('outlet_id'),
                'view_mode' => $request->input('view_mode'),
                'tab' => $request->input('tab', 'penjualan'),
            ]));
        }

        if ($slug === 'sales-by-product') {
            return redirect()->route('admin.reports.sales.products', array_filter([
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
                'outlet_id' => $request->input('outlet_id'),
                'user_id' => $request->input('user_id'),
                'tab' => $request->input('tab', 'penjualan'),
            ]));
        }

        if ($slug === 'sales-daily-summary') {
            return redirect()->route('admin.reports.sales.daily', array_filter([
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
                'outlet_id' => $request->input('outlet_id'),
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

        if ($slug === 'shift-sessions') {
            return redirect()->route('admin.reports.shifts.index', array_filter([
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
                'outlet_id' => $request->input('outlet_id'),
                'user_id' => $request->input('user_id'),
                'status' => $request->input('status'),
                'tab' => $request->input('tab', 'penjualan'),
            ]));
        }

        if ($slug === 'profit-loss') {
            return redirect()->route('admin.reports.profit-loss.index', array_filter([
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
                'outlet_id' => $request->input('outlet_id'),
                'tab' => $request->input('tab', 'ikhtisar'),
            ]));
        }

        $report = $reports[$slug] ?? $allReports[$slug];
        $financeSlugs = ['balance-sheet', 'profit-loss', 'cash-bank', 'cash-bank-detail', 'ledger-detail', 'cash-flow', 'sales-summary', 'stock-movement', 'stock-adjustments', 'inventory-value'];
        $defaultDateFrom = in_array($slug, $financeSlugs, true) ? now()->startOfMonth()->toDateString() : now()->toDateString();
        $defaultDateTo = in_array($slug, $financeSlugs, true) ? now()->endOfMonth()->toDateString() : now()->toDateString();

        $dateFrom = $request->input('date_from', $defaultDateFrom);
        $dateTo = $request->input('date_to', $defaultDateTo);
        $stockMovementFilterApplied = $slug !== 'stock-movement'
            || ($request->filled('date_from') && $request->filled('date_to'));
        $outletId = $request->input('outlet_id');
        $selectedUserId = $request->input('user_id');
        $outlets = Outlet::where('is_active', true)->orderBy('name')->get();
        $selectedOutletIds = $slug === 'sales-vs-hpp'
            ? $this->resolveCatalogOutletIds($request, $outlets->pluck('id'))
            : [];
        $selectedFromOutletIds = $slug === 'stock-transfer'
            ? $this->resolveCatalogOutletIds($request, $outlets->pluck('id'), 'from_outlet_ids', 'from_outlet_id')
            : [];
        $selectedToOutletIds = $slug === 'stock-transfer'
            ? $this->resolveCatalogOutletIds($request, $outlets->pluck('id'), 'to_outlet_ids', 'to_outlet_id')
            : [];
        $selectedProductId = $request->input('product_id');
        $products = collect();
        $users = collect();
        if (in_array($slug, ['stock-movement', 'stock-adjustments'], true)) {
            $products = Product::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'sku']);
        }
        if ($slug === 'stock-adjustments') {
            $users = User::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']);
        }

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

            $saleItemMetricsSub = DB::table('sale_items')
                ->selectRaw('sale_items.sale_id')
                ->selectRaw('COALESCE(SUM(sale_items.subtotal + sale_items.discount_amount), 0) as gross_value')
                ->selectRaw('COALESCE(SUM(sale_items.discount_amount), 0) as item_discount_amount')
                ->groupBy('sale_items.sale_id');

            $salesSummaryBaseQuery = (clone $salesBaseQuery)
                ->leftJoinSub($saleItemMetricsSub, 'sale_item_metrics', function ($join) {
                    $join->on('sale_item_metrics.sale_id', '=', 'sales.id');
                });

            $totals = (clone $salesSummaryBaseQuery)
                ->selectRaw('COUNT(*) as total_transactions')
                ->selectRaw('COALESCE(SUM(COALESCE(sale_item_metrics.gross_value, sales.subtotal)), 0) as gross_sales')
                ->selectRaw('COALESCE(SUM(sales.discount_amount), 0) as header_discount_total')
                ->selectRaw('COALESCE(SUM(COALESCE(sale_item_metrics.item_discount_amount, 0)), 0) as item_discount_total')
                ->selectRaw('COALESCE(SUM(COALESCE(sale_item_metrics.item_discount_amount, 0) + sales.discount_amount), 0) as total_discount')
                ->selectRaw('COALESCE(SUM(sales.tax_amount), 0) as total_tax')
                ->selectRaw('COALESCE(SUM(sales.service_charge_amount), 0) as total_service_charge')
                ->selectRaw('COALESCE(SUM(sales.total_amount), 0) as total_amount')
                ->selectRaw('COALESCE(AVG(sales.total_amount), 0) as avg_transaction')
                ->first();

            $discountAnomalyTransactions = (clone $salesSummaryBaseQuery)
                ->whereIn('sales.sales_type', SpecialPromotion::blockedRuntimeSalesTypes())
                ->whereNull('sales.promotion_id')
                ->whereNull('sales.voucher_id')
                ->whereRaw('COALESCE(sale_item_metrics.item_discount_amount, 0) + COALESCE(sales.discount_amount, 0) = 0')
                ->count();

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
                'total_sales' => (float) ($totals->gross_sales ?? 0),
                'total_discount' => (float) ($totals->total_discount ?? 0),
                'header_discount_total' => (float) ($totals->header_discount_total ?? 0),
                'item_discount_total' => (float) ($totals->item_discount_total ?? 0),
                'total_tax' => (float) ($totals->total_tax ?? 0),
                'total_service_charge' => (float) ($totals->total_service_charge ?? 0),
                'total_adjustment' => 0.0,
                'total_amount' => (float) ($totals->total_amount ?? 0),
                'avg_transaction' => (float) ($totals->avg_transaction ?? 0),
                'discount_anomaly_transactions' => (int) $discountAnomalyTransactions,
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

        if ($slug === 'cancelled-sales') {
            $viewType = 'cancelled-sales';

            $cancelledStatuses = ['cancelled', 'void'];

            $saleItemAggSub = DB::table('sale_items')
                ->select('sale_items.sale_id')
                ->selectRaw('COUNT(*) as line_count')
                ->selectRaw('COALESCE(SUM(sale_items.quantity), 0) as item_qty')
                ->selectRaw('COALESCE(SUM(sale_items.subtotal + sale_items.discount_amount), 0) as gross_amount')
                ->groupBy('sale_items.sale_id');

            $paymentAggSub = DB::table('payments')
                ->join('payment_methods', 'payments.payment_method_id', '=', 'payment_methods.id')
                ->select('payments.sale_id')
                ->selectRaw('COALESCE(SUM(payments.amount), 0) as paid_amount')
                ->selectRaw("GROUP_CONCAT(DISTINCT payment_methods.name ORDER BY payment_methods.name SEPARATOR ', ') as payment_methods")
                ->groupBy('payments.sale_id');

            $cancelledBaseQuery = DB::table('sales')
                ->join('outlets', 'sales.outlet_id', '=', 'outlets.id')
                ->join('users', 'sales.user_id', '=', 'users.id')
                ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
                ->leftJoinSub($saleItemAggSub, 'sale_item_agg', function ($join) {
                    $join->on('sales.id', '=', 'sale_item_agg.sale_id');
                })
                ->leftJoinSub($paymentAggSub, 'payment_agg', function ($join) {
                    $join->on('sales.id', '=', 'payment_agg.sale_id');
                })
                ->whereIn('sales.status', $cancelledStatuses)
                ->whereBetween('sales.sale_date', [$dateFrom, $dateTo]);

            if (!empty($outletId)) {
                $cancelledBaseQuery->where('sales.outlet_id', $outletId);
            }

            $rows = (clone $cancelledBaseQuery)
                ->select(
                    'sales.id as sale_id',
                    'sales.invoice_number',
                    'sales.sale_date',
                    'sales.outlet_id',
                    'outlets.name as outlet_name',
                    'users.name as cashier_name',
                    'sales.sales_type',
                    'sales.notes',
                    'sales.status',
                    'sales.updated_at as cancelled_at',
                    'sales.total_amount',
                    DB::raw("COALESCE(NULLIF(TRIM(sales.customer_name), ''), NULLIF(TRIM(customers.name), ''), 'Walk-in') as customer_name"),
                    DB::raw('COALESCE(sale_item_agg.line_count, 0) as line_count'),
                    DB::raw('COALESCE(sale_item_agg.item_qty, 0) as item_qty'),
                    DB::raw('COALESCE(sale_item_agg.gross_amount, sales.subtotal + sales.discount_amount, sales.subtotal, 0) as gross_amount'),
                    DB::raw("COALESCE(payment_agg.payment_methods, '-') as payment_methods"),
                    DB::raw('COALESCE(payment_agg.paid_amount, 0) as paid_amount')
                )
                ->orderByDesc('sales.sale_date')
                ->orderByDesc('sales.updated_at')
                ->orderByDesc('sales.id')
                ->get()
                ->map(function ($row) {
                    $row->status_label = strtoupper((string) $row->status) === 'VOID'
                        ? 'Void'
                        : 'Cancelled';
                    $row->sales_type_label = filled($row->sales_type)
                        ? ucwords(str_replace(['_', '-'], ' ', (string) $row->sales_type))
                        : 'Regular';

                    return $row;
                });

            $statusBreakdown = $rows
                ->groupBy(fn($row) => (string) $row->status_label)
                ->map(fn($group) => [
                    'total_transactions' => $group->count(),
                    'total_amount' => (float) $group->sum('total_amount'),
                ])
                ->all();

            $summary = [
                'total_transactions' => $rows->count(),
                'total_amount' => (float) $rows->sum('total_amount'),
                'total_items' => (float) $rows->sum('item_qty'),
                'avg_amount' => $rows->count() > 0 ? (float) $rows->avg('total_amount') : 0,
                'outlet_count' => $rows->pluck('outlet_id')->filter()->unique()->count(),
                'status_breakdown' => $statusBreakdown,
            ];

            $meta = [
                'notes' => [
                    'Report ini mengambil transaksi dengan status cancelled dan kompatibel juga untuk data legacy berstatus void jika ada.',
                    'Karena tabel sales tidak menyimpan kolom cancelled_at terpisah, kolom waktu batal memakai updated_at sebagai jejak perubahan terakhir transaksi.',
                ],
            ];
        }

        if ($slug === 'sales-vs-hpp') {
            $viewType = 'sales-vs-hpp';
            $format = $request->input('format');
            $isExporting = in_array($format, ['xlsx', 'pdf'], true);

            $salesBaseQuery = DB::table('sales')
                ->where('sales.status', 'completed')
                ->whereBetween('sales.sale_date', [$dateFrom, $dateTo]);

            if (!empty($selectedOutletIds)) {
                $salesBaseQuery->whereIn('sales.outlet_id', $selectedOutletIds);
            } elseif (!empty($outletId)) {
                $salesBaseQuery->where('sales.outlet_id', $outletId);
            }

            $saleItemCountSub = DB::table('sale_items')
                ->select('sale_items.sale_id')
                ->selectRaw('COUNT(*) as sale_item_count')
                ->groupBy('sale_items.sale_id');

            $salesItemBase = (clone $salesBaseQuery)
                ->join('sale_items', 'sale_items.sale_id', '=', 'sales.id')
                ->join('outlets', 'sales.outlet_id', '=', 'outlets.id')
                ->leftJoinSub($saleItemCountSub, 'sale_item_counts', function ($join) {
                    $join->on('sale_item_counts.sale_id', '=', 'sales.id');
                });

            $itemTotals = (clone $salesItemBase)
                ->selectRaw('COUNT(DISTINCT sale_items.product_name) as total_items')
                ->selectRaw('COALESCE(SUM(sale_items.quantity), 0) as total_qty')
                ->selectRaw('COALESCE(SUM(sale_items.cogs), 0) as total_hpp')
                ->first();

            $totalSales = (float) (clone $salesBaseQuery)->sum('sales.total_amount');

            $rowsQuery = (clone $salesItemBase)
                ->select(
                    'sales.id as sale_id',
                    'sales.invoice_number as transaction_number',
                    'sales.sale_date',
                    'sales.outlet_id',
                    'outlets.name as outlet_name',
                    'sale_items.product_name',
                    'sale_items.quantity as qty',
                    'sale_items.subtotal as item_subtotal',
                    'sales.subtotal as sale_subtotal',
                    'sales.total_amount as sale_total_amount',
                    'sale_item_counts.sale_item_count',
                    'sale_items.cogs as hpp_amount'
                )
                ->orderByDesc('sales.sale_date')
                ->orderByDesc('sales.id')
                ->orderByDesc('sale_items.id');

            $this->applySalesVsHppTableFilters($rowsQuery, $request);

            if ($format === 'xlsx') {
                return $this->downloadSalesVsHppXlsx(
                    rowsQuery: $rowsQuery,
                    filename: sprintf('sales-vs-hpp-%s-sd-%s.xlsx', str_replace('-', '', $dateFrom), str_replace('-', '', $dateTo)),
                );
            }

            $rows = $isExporting
                ? $this->transformSalesVsHppRows($rowsQuery->get())
                : $this->transformSalesVsHppPaginator($rowsQuery->paginate(250)->withQueryString());

            $totalHpp = (float) ($itemTotals->total_hpp ?? 0);
            $totalGrossProfit = $totalSales - $totalHpp;

            $summary = [
                'total_items' => (int) ($itemTotals->total_items ?? 0),
                'total_qty' => (float) ($itemTotals->total_qty ?? 0),
                'total_sales' => $totalSales,
                'total_hpp' => $totalHpp,
                'total_gross_profit' => $totalGrossProfit,
                'gross_margin_percent' => $totalSales > 0 ? round(($totalGrossProfit / $totalSales) * 100, 2) : 0,
            ];
        }

        if ($slug === 'stock-movement') {
            $viewType = 'stock-movement';
            $selectedProductId = $request->filled('product_id') ? (int) $request->input('product_id') : null;

            if ($stockMovementFilterApplied) {
                [$rows, $summary] = $this->stockMovementReport(
                    dateFrom: $dateFrom,
                    dateTo: $dateTo,
                    outletId: !empty($outletId) ? (int) $outletId : null,
                    productId: $selectedProductId,
                );
            } else {
                $summary = [
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'outlet_id' => !empty($outletId) ? (int) $outletId : null,
                    'product_id' => $selectedProductId,
                    'selected_product_name' => null,
                    'row_count' => 0,
                ];
            }

            $meta = [
                'notes' => [
                    'Nominal mutasi memakai snapshot total_cost; jika kosong akan fallback ke avg_cost outlet atau purchase_price produk.',
                    'Kolom Penjualan Keluar (Nominal) dapat dipakai untuk rekonsiliasi HPP dengan laporan Laba Rugi pada filter tanggal/outlet yang sama.',
                    'Metrik HPP eksplisit: hpp_penjualan_kotor (out sale), hpp_reversal_void (in sale_cancellation), hpp_penjualan_bersih (kotor - reversal).',
                ],
            ];
        }

        if ($slug === 'stock-adjustments') {
            $viewType = 'stock-adjustment';
            $selectedProductId = $request->filled('product_id') ? (int) $request->input('product_id') : null;
            $selectedUserId = $request->filled('user_id') ? (int) $request->input('user_id') : null;

            [$rows, $summary] = $this->stockAdjustmentReport(
                dateFrom: $dateFrom,
                dateTo: $dateTo,
                outletId: !empty($outletId) ? (int) $outletId : null,
                productId: $selectedProductId,
                userId: $selectedUserId,
            );

            $meta = [
                'notes' => [
                    'Laporan ini menampilkan detail adjustment stok manual per baris mutasi, termasuk user input, stok sebelum/sesudah, selisih, dan catatan.',
                    'Nilai nominal adjustment memakai total_cost snapshot pada mutasi; jika qty berubah tetapi total_cost nol, periksa histori cost produk/outlet terkait.',
                    'Satu aksi bulk adjustment dapat menghasilkan beberapa baris jika user menyesuaikan lebih dari satu produk sekaligus.',
                ],
            ];
        }

        if ($slug === 'stock-transfer') {
            $viewType = 'stock-transfer';
            $viewMode = $request->input('view_mode', 'summary');

            $query = DB::table('stock_transfers')
                ->join('outlets as from_outlet', 'stock_transfers.from_outlet_id', '=', 'from_outlet.id')
                ->join('outlets as to_outlet', 'stock_transfers.to_outlet_id', '=', 'to_outlet.id')
                ->whereBetween('stock_transfers.transfer_date', [$dateFrom, $dateTo])
                ->whereIn('stock_transfers.status', ['in_transit', 'received']);

            if (!empty($selectedFromOutletIds)) {
                $query->whereIn('stock_transfers.from_outlet_id', $selectedFromOutletIds);
            } elseif (!empty($outletId)) {
                $query->where('stock_transfers.from_outlet_id', $outletId);
            }

            if (!empty($selectedToOutletIds)) {
                $query->whereIn('stock_transfers.to_outlet_id', $selectedToOutletIds);
            }

            if ($viewMode === 'summary') {
                $rows = (clone $query)
                    ->select(
                        'stock_transfers.transfer_date',
                        DB::raw('COUNT(stock_transfers.id) as total_transfers')
                    )
                    ->groupBy('stock_transfers.transfer_date')
                    ->orderByDesc('stock_transfers.transfer_date')
                    ->get();
                
                $itemAgg = (clone $query)
                    ->join('stock_transfer_items', 'stock_transfers.id', '=', 'stock_transfer_items.stock_transfer_id')
                    ->leftJoin('stock_mutations', function ($join) {
                        $join->on('stock_transfers.id', '=', 'stock_mutations.reference_id')
                            ->where('stock_mutations.reference_type', '=', 'stock_transfer')
                            ->where('stock_mutations.mutation_type', '=', 'transfer_out')
                            ->on('stock_transfer_items.product_id', '=', 'stock_mutations.product_id');
                    })
                    ->select(
                        'stock_transfers.transfer_date',
                        DB::raw('SUM(stock_transfer_items.quantity) as total_qty'),
                        DB::raw('SUM(stock_transfer_items.received_quantity) as total_received_qty'),
                        DB::raw('SUM(COALESCE(ABS(stock_mutations.total_cost), ABS(stock_mutations.quantity) * COALESCE(stock_mutations.unit_cost, 0), 0)) as total_nominal')
                    )
                    ->groupBy('stock_transfers.transfer_date')
                    ->get()
                    ->keyBy('transfer_date');
                    
                $rows->transform(function ($row) use ($itemAgg) {
                    $item = $itemAgg->get($row->transfer_date);
                    $row->total_qty = $item ? (float) $item->total_qty : 0;
                    $row->total_received_qty = $item ? (float) $item->total_received_qty : 0;
                    $row->total_nominal = $item ? (float) $item->total_nominal : 0;
                    return $row;
                });

                $summary = [
                    'view_mode' => 'summary',
                    'total_transfers' => (int) $rows->sum('total_transfers'),
                    'total_qty' => (float) $rows->sum('total_qty'),
                    'total_received_qty' => (float) $rows->sum('total_received_qty'),
                    'total_nominal' => (float) $rows->sum('total_nominal'),
                ];
            } else {
                $query->join('stock_transfer_items', 'stock_transfers.id', '=', 'stock_transfer_items.stock_transfer_id')
                    ->join('products', 'stock_transfer_items.product_id', '=', 'products.id')
                    ->leftJoin('stock_mutations', function ($join) {
                        $join->on('stock_transfers.id', '=', 'stock_mutations.reference_id')
                            ->where('stock_mutations.reference_type', '=', 'stock_transfer')
                            ->where('stock_mutations.mutation_type', '=', 'transfer_out')
                            ->on('stock_transfer_items.product_id', '=', 'stock_mutations.product_id');
                    })
                    ->select(
                        'stock_transfers.id',
                        'stock_transfers.transfer_number',
                        'stock_transfers.transfer_date',
                        'stock_transfers.status',
                        'from_outlet.name as from_outlet_name',
                        'to_outlet.name as to_outlet_name',
                        'products.name as product_name',
                        'products.sku as product_sku',
                        'stock_transfer_items.quantity',
                        'stock_transfer_items.received_quantity',
                        'stock_transfers.notes',
                        DB::raw('COALESCE(ABS(stock_mutations.total_cost), ABS(stock_mutations.quantity) * COALESCE(stock_mutations.unit_cost, 0), 0) as nominal_value')
                    )
                    ->orderByDesc('stock_transfers.transfer_date')
                    ->orderByDesc('stock_transfers.id');
                    
                $rows = $query->get();
                $summary = [
                    'view_mode' => 'detail',
                    'total_transfers' => $rows->pluck('transfer_number')->unique()->count(),
                    'total_qty' => (float) $rows->sum('quantity'),
                    'total_received_qty' => (float) $rows->sum('received_quantity'),
                    'total_nominal' => (float) $rows->sum('nominal_value'),
                ];
            }
        }

        if ($slug === 'inventory-value') {
            $viewType = 'inventory-reconciliation';
            [$rows, $summary, $meta] = $this->inventoryReconciliationReport(
                dateFrom: $dateFrom,
                dateTo: $dateTo,
                outletId: !empty($outletId) ? (int) $outletId : null,
            );
        }

        // Implementasi: Laporan Diskon Penjualan (Compliment / Meal Karyawan / dll)
        if ($slug === 'sales-discount') {
            $viewType = 'sales-discount';
            $format = $request->input('format');
            $viewMode = $request->input('view_mode', 'summary'); // summary | detail
            $filterType = $request->input('filter_type', 'all'); // all | compliment | meal_karyawan
            $specialFilterType = $filterType !== 'all' ? SpecialPromotion::classify($filterType) : null;
            $matchesText = static function ($value, ?string $keyword): bool {
                $keyword = trim((string) $keyword);

                if ($keyword === '') {
                    return true;
                }

                return stripos((string) $value, $keyword) !== false;
            };
            $matchesNumber = static function ($value, ?string $keyword): bool {
                $keyword = trim((string) $keyword);

                if ($keyword === '') {
                    return true;
                }

                $number = (float) $value;
                $variants = array_unique([
                    (string) $value,
                    (string) $number,
                    number_format($number, 0, ',', '.'),
                    number_format($number, 2, ',', '.'),
                ]);

                foreach ($variants as $variant) {
                    if (stripos($variant, $keyword) !== false) {
                        return true;
                    }
                }

                return false;
            };

            $saleItemMetricsSub = DB::table('sale_items')
                ->selectRaw('sale_items.sale_id')
                ->selectRaw('COALESCE(SUM(sale_items.subtotal + sale_items.discount_amount), 0) as gross_value')
                ->selectRaw('COALESCE(SUM(sale_items.discount_amount), 0) as item_discount_amount')
                ->groupBy('sale_items.sale_id');

            $salesQuery = DB::table('sales')
                ->leftJoinSub($saleItemMetricsSub, 'sale_item_metrics', function ($join) {
                    $join->on('sale_item_metrics.sale_id', '=', 'sales.id');
                })
                ->leftJoin('outlets', 'sales.outlet_id', '=', 'outlets.id')
                ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
                ->leftJoin('promotions', 'sales.promotion_id', '=', 'promotions.id')
                ->leftJoin('vouchers', 'sales.voucher_id', '=', 'vouchers.id')
                ->where('sales.status', 'completed')
                ->whereBetween('sales.sale_date', [$dateFrom, $dateTo])
                ->where(function ($query) {
                    $query
                        ->whereRaw('COALESCE(sale_item_metrics.item_discount_amount, 0) + COALESCE(sales.discount_amount, 0) > 0');

                    foreach (SpecialPromotion::blockedRuntimeSalesTypes() as $salesType) {
                        $query->orWhere('sales.sales_type', $salesType);
                    }
                });

            if (!empty($outletId)) {
                $salesQuery->where('sales.outlet_id', $outletId);
            }

            $salesQuery->select(
                'sales.id',
                'sales.invoice_number',
                'sales.sale_date',
                'sales.sales_type',
                'sales.discount_type',
                'sales.discount_amount',
                'sales.total_amount',
                'sales.customer_name',
                'sales.notes',
                'outlets.name as outlet_name',
                'customers.name as customer_relation_name',
                'promotions.name as promotion_name',
                'promotions.code as promotion_code',
                'vouchers.name as voucher_name',
                'vouchers.code as voucher_table_code',
                'sales.voucher_code'
            )
                ->selectRaw('COALESCE(sale_item_metrics.gross_value, sales.subtotal + sales.discount_amount, sales.subtotal, 0) as gross_value')
                ->selectRaw('COALESCE(sale_item_metrics.item_discount_amount, 0) as item_discount_amount')
                ->orderByDesc('sales.sale_date')
                ->orderByDesc('sales.created_at');

            if ($format === 'xlsx') {
                return $this->downloadSalesDiscountXlsx(
                    salesQuery: $salesQuery,
                    request: $request,
                    filename: sprintf('sales-discount-%s-sd-%s.xlsx', str_replace('-', '', $dateFrom), str_replace('-', '', $dateTo)),
                    viewMode: $viewMode,
                    filterType: $filterType,
                    specialFilterType: $specialFilterType,
                );
            }

            if ($viewMode === 'summary') {
                $groupedRows = [];

                foreach ($salesQuery->lazy() as $sale) {
                    $row = $this->buildSalesDiscountRowFromRecord($sale);

                    if ($filterType !== 'all') {
                        $matchesFilter = $row['sales_type'] === $filterType
                            || ($specialFilterType !== null && ($row['special_discount_type'] ?? null) === $specialFilterType);

                        if (!$matchesFilter) {
                            continue;
                        }
                    } elseif (($row['effective_discount'] ?? 0) <= 0 && empty($row['is_discount_anomaly'])) {
                        continue;
                    }

                    $groupKey = implode('|', [
                        $row['discount_source_key'] ?? 'none',
                        $row['sales_type'] ?? 'regular',
                        !empty($row['is_discount_anomaly']) ? 'anomaly' : 'valid',
                    ]);

                    if (!isset($groupedRows[$groupKey])) {
                        $groupedRows[$groupKey] = [
                            'discount_source_key' => $row['discount_source_key'] ?? 'none',
                            'discount_source_kind' => $row['discount_source_kind'] ?? 'none',
                            'discount_type' => $row['discount_type'] ?? 'none',
                            'discount_type_label' => $row['discount_type_label'] ?? 'None',
                            'discount_source_label' => $row['discount_source_label'] ?? 'None',
                            'sales_type' => $row['sales_type'] ?? 'regular',
                            'sales_type_label' => $row['sales_type_label'] ?? 'Regular',
                            'is_discount_anomaly' => !empty($row['is_discount_anomaly']),
                            'data_status_label' => $row['data_status_label'] ?? 'Valid',
                            'transaction_count' => 0,
                            'gross_value' => 0.0,
                            'net_sales' => 0.0,
                            'effective_discount' => 0.0,
                            'total_discount' => 0.0,
                        ];
                    }

                    $groupedRows[$groupKey]['transaction_count']++;
                    $groupedRows[$groupKey]['gross_value'] += (float) ($row['gross_value'] ?? 0);
                    $groupedRows[$groupKey]['net_sales'] += (float) ($row['net_sales'] ?? 0);
                    $groupedRows[$groupKey]['effective_discount'] += (float) ($row['effective_discount'] ?? 0);
                    $groupedRows[$groupKey]['total_discount'] += (float) ($row['effective_discount'] ?? 0);
                }

                $rows = collect(array_values($groupedRows));

                if ($request->filled('filter_tipe_diskon')) {
                    $rows = $rows->filter(fn($row) => $matchesText($row['discount_source_label'] ?? '', $request->input('filter_tipe_diskon')));
                }
                if ($request->filled('filter_metode_penjualan')) {
                    $rows = $rows->filter(fn($row) => $matchesText($row['sales_type_label'] ?? '', $request->input('filter_metode_penjualan')));
                }
                if ($request->filled('filter_status_data')) {
                    $rows = $rows->filter(fn($row) => $matchesText($row['data_status_label'] ?? '', $request->input('filter_status_data')));
                }
                if ($request->filled('filter_jumlah_transaksi')) {
                    $rows = $rows->filter(fn($row) => $matchesNumber($row['transaction_count'] ?? 0, $request->input('filter_jumlah_transaksi')));
                }
                if ($request->filled('filter_gross')) {
                    $rows = $rows->filter(fn($row) => $matchesNumber($row['gross_value'] ?? 0, $request->input('filter_gross')));
                }
                if ($request->filled('filter_diskon')) {
                    $rows = $rows->filter(fn($row) => $matchesNumber($row['effective_discount'] ?? 0, $request->input('filter_diskon')));
                }
                if ($request->filled('filter_net_sales')) {
                    $rows = $rows->filter(fn($row) => $matchesNumber($row['net_sales'] ?? 0, $request->input('filter_net_sales')));
                }

                $rows = $rows->values();
            } else {
                $rows = $salesQuery
                    ->get()
                    ->map(fn($sale) => $this->buildSalesDiscountRowFromRecord($sale))
                    ->filter(function ($row) use ($filterType, $specialFilterType) {
                        if ($filterType !== 'all') {
                            return $row['sales_type'] === $filterType
                                || ($specialFilterType !== null && ($row['special_discount_type'] ?? null) === $specialFilterType);
                        }

                        return $row['effective_discount'] > 0 || $row['is_discount_anomaly'];
                    })
                    ->values();

                if ($request->filled('filter_tipe_diskon')) {
                    $rows = $rows->filter(fn($row) => $matchesText($row['discount_source_label'] ?? '', $request->input('filter_tipe_diskon')));
                }
                if ($request->filled('filter_transaksi')) {
                    $rows = $rows->filter(fn($row) => $matchesText($row['invoice_number'] ?? '', $request->input('filter_transaksi')));
                }
                if ($request->filled('filter_tanggal')) {
                    $rows = $rows->filter(fn($row) => $matchesText($row['sale_date'] ?? '', $request->input('filter_tanggal')));
                }
                if ($request->filled('filter_outlet')) {
                    $rows = $rows->filter(fn($row) => $matchesText($row['outlet_name'] ?? '', $request->input('filter_outlet')));
                }
                if ($request->filled('filter_metode_penjualan')) {
                    $rows = $rows->filter(fn($row) => $matchesText($row['sales_type_label'] ?? '', $request->input('filter_metode_penjualan')));
                }
                if ($request->filled('filter_status_data')) {
                    $rows = $rows->filter(fn($row) => $matchesText($row['data_status_label'] ?? '', $request->input('filter_status_data')));
                }
                if ($request->filled('filter_pelanggan')) {
                    $rows = $rows->filter(fn($row) => $matchesText($row['customer_name'] ?? '', $request->input('filter_pelanggan')));
                }
                if ($request->filled('filter_gross')) {
                    $rows = $rows->filter(fn($row) => $matchesNumber($row['gross_value'] ?? 0, $request->input('filter_gross')));
                }
                if ($request->filled('filter_diskon')) {
                    $rows = $rows->filter(fn($row) => $matchesNumber($row['effective_discount'] ?? 0, $request->input('filter_diskon')));
                }
                if ($request->filled('filter_net_sales')) {
                    $rows = $rows->filter(fn($row) => $matchesNumber($row['net_sales'] ?? 0, $request->input('filter_net_sales')));
                }

                $rows = $rows->values();
            }

            $summary = [
                'total_transactions' => $viewMode === 'summary'
                    ? $rows->sum('transaction_count')
                    : $rows->count(),
                'total_gross_value' => $rows->sum('gross_value'),
                'total_net_sales' => $rows->sum('net_sales'),
                'total_discount' => $rows->sum('effective_discount'),
                'discount_anomaly_transactions' => $viewMode === 'summary'
                    ? $rows->where('is_discount_anomaly', true)->sum('transaction_count')
                    : $rows->where('is_discount_anomaly', true)->count(),
                'view_mode' => $viewMode,
            ];
        }

        if ($slug === 'promo') {
            $viewType = 'promo-report';
            $viewMode = in_array($request->input('view_mode'), ['summary', 'detail'], true)
                ? $request->input('view_mode')
                : 'summary';

            $matchesText = static function ($value, ?string $keyword): bool {
                $keyword = trim((string) $keyword);

                if ($keyword === '') {
                    return true;
                }

                return stripos((string) $value, $keyword) !== false;
            };
            $matchesNumber = static function ($value, ?string $keyword): bool {
                $keyword = trim((string) $keyword);

                if ($keyword === '') {
                    return true;
                }

                $number = (float) $value;
                $variants = array_unique([
                    (string) $value,
                    (string) $number,
                    number_format($number, 0, ',', '.'),
                    number_format($number, 2, ',', '.'),
                ]);

                foreach ($variants as $variant) {
                    if (stripos($variant, $keyword) !== false) {
                        return true;
                    }
                }

                return false;
            };

            $salesQuery = \App\Models\Sale::query()
                ->with(['items.product', 'outlet', 'user', 'customer', 'promotion:id,name,code', 'voucher:id,name,code'])
                ->where('status', 'completed')
                ->whereBetween('sale_date', [$dateFrom, $dateTo])
                ->where(function ($query) {
                    $query
                        ->whereNotNull('promotion_id')
                        ->orWhereNotNull('voucher_id')
                        ->orWhereNotNull('voucher_code');
                });

            if (!empty($outletId)) {
                $salesQuery->where('outlet_id', $outletId);
            }

            $processedData = $salesQuery
                ->orderByDesc('sale_date')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn($sale) => $this->buildSalesDiscountRow($sale))
                ->filter(fn($row) => in_array($row['discount_source_kind'] ?? '', ['promotion', 'voucher', 'voucher_code'], true))
                ->map(function ($row) {
                    $row['promo_source_label'] = $row['discount_source_label'] ?? 'Tanpa Promo';
                    $row['promo_source_kind_label'] = $this->formatPromoSourceKindLabel($row['discount_source_kind'] ?? null);

                    return $row;
                })
                ->values();

            if ($viewMode === 'summary') {
                $rows = $processedData
                    ->groupBy(function ($row) {
                        return implode('|', [
                            $row['discount_source_kind'] ?? 'none',
                            $row['discount_source_key'] ?? 'none',
                        ]);
                    })
                    ->map(function ($group) {
                        $first = $group->first();

                        return [
                            'promo_source_key' => $first['discount_source_key'] ?? 'none',
                            'promo_source_kind' => $first['discount_source_kind'] ?? 'none',
                            'promo_source_kind_label' => $first['promo_source_kind_label'] ?? 'Promo',
                            'promo_source_label' => $first['promo_source_label'] ?? 'Tanpa Promo',
                            'transaction_count' => $group->count(),
                            'gross_value' => $group->sum('gross_value'),
                            'effective_discount' => $group->sum('effective_discount'),
                            'net_sales' => $group->sum('net_sales'),
                        ];
                    })
                    ->sortByDesc('effective_discount')
                    ->values();

                if ($request->filled('filter_jenis_promo')) {
                    $rows = $rows->filter(fn($row) => $matchesText($row['promo_source_kind_label'] ?? '', $request->input('filter_jenis_promo')));
                }
                if ($request->filled('filter_promo')) {
                    $rows = $rows->filter(fn($row) => $matchesText($row['promo_source_label'] ?? '', $request->input('filter_promo')));
                }
                if ($request->filled('filter_jumlah_transaksi')) {
                    $rows = $rows->filter(fn($row) => $matchesNumber($row['transaction_count'] ?? 0, $request->input('filter_jumlah_transaksi')));
                }
                if ($request->filled('filter_gross')) {
                    $rows = $rows->filter(fn($row) => $matchesNumber($row['gross_value'] ?? 0, $request->input('filter_gross')));
                }
                if ($request->filled('filter_diskon')) {
                    $rows = $rows->filter(fn($row) => $matchesNumber($row['effective_discount'] ?? 0, $request->input('filter_diskon')));
                }
                if ($request->filled('filter_net_sales')) {
                    $rows = $rows->filter(fn($row) => $matchesNumber($row['net_sales'] ?? 0, $request->input('filter_net_sales')));
                }

                $rows = $rows->values();
            } else {
                $rows = $processedData->values();

                if ($request->filled('filter_jenis_promo')) {
                    $rows = $rows->filter(fn($row) => $matchesText($row['promo_source_kind_label'] ?? '', $request->input('filter_jenis_promo')));
                }
                if ($request->filled('filter_promo')) {
                    $rows = $rows->filter(fn($row) => $matchesText($row['promo_source_label'] ?? '', $request->input('filter_promo')));
                }
                if ($request->filled('filter_transaksi')) {
                    $rows = $rows->filter(fn($row) => $matchesText($row['invoice_number'] ?? '', $request->input('filter_transaksi')));
                }
                if ($request->filled('filter_tanggal')) {
                    $rows = $rows->filter(fn($row) => $matchesText($row['sale_date'] ?? '', $request->input('filter_tanggal')));
                }
                if ($request->filled('filter_outlet')) {
                    $rows = $rows->filter(fn($row) => $matchesText($row['outlet_name'] ?? '', $request->input('filter_outlet')));
                }
                if ($request->filled('filter_metode_penjualan')) {
                    $rows = $rows->filter(fn($row) => $matchesText($row['sales_type_label'] ?? '', $request->input('filter_metode_penjualan')));
                }
                if ($request->filled('filter_pelanggan')) {
                    $rows = $rows->filter(fn($row) => $matchesText($row['customer_name'] ?? '', $request->input('filter_pelanggan')));
                }
                if ($request->filled('filter_gross')) {
                    $rows = $rows->filter(fn($row) => $matchesNumber($row['gross_value'] ?? 0, $request->input('filter_gross')));
                }
                if ($request->filled('filter_diskon')) {
                    $rows = $rows->filter(fn($row) => $matchesNumber($row['effective_discount'] ?? 0, $request->input('filter_diskon')));
                }
                if ($request->filled('filter_net_sales')) {
                    $rows = $rows->filter(fn($row) => $matchesNumber($row['net_sales'] ?? 0, $request->input('filter_net_sales')));
                }

                $rows = $rows->values();
            }

            $sourceKindBreakdown = ($viewMode === 'summary'
                ? $rows->groupBy('promo_source_kind_label')->map(fn($group) => [
                    'source_count' => $group->count(),
                    'transaction_count' => (int) $group->sum('transaction_count'),
                    'discount_total' => (float) $group->sum('effective_discount'),
                ])
                : $rows->groupBy('promo_source_kind_label')->map(fn($group) => [
                    'source_count' => $group->pluck('discount_source_key')->filter()->unique()->count(),
                    'transaction_count' => $group->count(),
                    'discount_total' => (float) $group->sum('effective_discount'),
                ]))
                ->all();

            $summary = [
                'total_sources' => $viewMode === 'summary'
                    ? $rows->count()
                    : $rows->pluck('discount_source_key')->filter()->unique()->count(),
                'total_transactions' => $viewMode === 'summary'
                    ? (int) $rows->sum('transaction_count')
                    : $rows->count(),
                'total_gross_value' => (float) $rows->sum('gross_value'),
                'total_discount' => (float) $rows->sum('effective_discount'),
                'total_net_sales' => (float) $rows->sum('net_sales'),
                'source_kind_breakdown' => $sourceKindBreakdown,
                'view_mode' => $viewMode,
            ];

            $meta = [
                'notes' => [
                    'Report ini hanya menampilkan transaksi yang memakai promo kategori atau voucher eksplisit.',
                    'Diskon manual, diskon item biasa, dan transaksi tanpa promotion/voucher tidak masuk ke laporan promo.',
                ],
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

        if (in_array($slug, ['purchase-summary', 'purchase-by-supplier', 'purchase-by-product', 'purchase-by-category', 'purchase-unpaid'], true)) {
            $purchasePaymentSub = DB::table('cash_transactions')
                ->select(
                    'reference_id',
                    DB::raw('SUM(amount) as paid_amount')
                )
                ->where('reference_type', 'purchase')
                ->groupBy('reference_id');

            if ($slug === 'purchase-summary') {
                $viewType = 'purchase-summary';

                $purchaseBase = DB::table('purchases')
                    ->leftJoinSub($purchasePaymentSub, 'purchase_payments', function ($join) {
                        $join->on('purchase_payments.reference_id', '=', 'purchases.id');
                    })
                    ->where('purchases.status', 'received')
                    ->whereBetween('purchases.purchase_date', [$dateFrom, $dateTo]);

                if (!empty($outletId)) {
                    $purchaseBase->where('purchases.outlet_id', $outletId);
                }

                $totals = (clone $purchaseBase)
                    ->selectRaw('COUNT(*) as total_purchase_count')
                    ->selectRaw('COALESCE(SUM(purchases.subtotal), 0) as subtotal')
                    ->selectRaw('COALESCE(SUM(purchases.discount_amount), 0) as total_discount')
                    ->selectRaw('COALESCE(SUM(purchases.tax_amount), 0) as total_tax')
                    ->selectRaw('COALESCE(SUM(purchases.total_amount), 0) as total_amount')
                    ->selectRaw('COALESCE(SUM(COALESCE(purchase_payments.paid_amount, 0)), 0) as total_paid')
                    ->first();

                $rows = (clone $purchaseBase)
                    ->select('purchases.purchase_date')
                    ->selectRaw('COUNT(*) as total_purchase_count')
                    ->selectRaw('COALESCE(SUM(purchases.total_amount), 0) as total_amount')
                    ->selectRaw('COALESCE(SUM(COALESCE(purchase_payments.paid_amount, 0)), 0) as total_paid')
                    ->groupBy('purchases.purchase_date')
                    ->orderByDesc('purchases.purchase_date')
                    ->get()
                    ->map(function ($row) {
                        $row->outstanding_amount = max(0, (float) $row->total_amount - (float) $row->total_paid);
                        return $row;
                    });

                $summary = [
                    'total_purchase_count' => (int) ($totals->total_purchase_count ?? 0),
                    'subtotal' => (float) ($totals->subtotal ?? 0),
                    'total_discount' => (float) ($totals->total_discount ?? 0),
                    'total_tax' => (float) ($totals->total_tax ?? 0),
                    'total_amount' => (float) ($totals->total_amount ?? 0),
                    'total_paid' => (float) ($totals->total_paid ?? 0),
                    'total_outstanding' => max(0, (float) ($totals->total_amount ?? 0) - (float) ($totals->total_paid ?? 0)),
                ];
            }

            if ($slug === 'purchase-by-supplier') {
                $viewType = 'purchase-by-supplier';

                $query = DB::table('purchases')
                    ->join('suppliers', 'purchases.supplier_id', '=', 'suppliers.id')
                    ->leftJoinSub($purchasePaymentSub, 'purchase_payments', function ($join) {
                        $join->on('purchase_payments.reference_id', '=', 'purchases.id');
                    })
                    ->where('purchases.status', 'received')
                    ->whereBetween('purchases.purchase_date', [$dateFrom, $dateTo]);

                if (!empty($outletId)) {
                    $query->where('purchases.outlet_id', $outletId);
                }

                $rows = $query
                    ->select(
                        'suppliers.id as supplier_id',
                        'suppliers.code as supplier_code',
                        'suppliers.name as supplier_name'
                    )
                    ->selectRaw('COUNT(purchases.id) as total_purchase_count')
                    ->selectRaw('COALESCE(SUM(purchases.total_amount), 0) as total_amount')
                    ->selectRaw('COALESCE(SUM(COALESCE(purchase_payments.paid_amount, 0)), 0) as total_paid')
                    ->groupBy('suppliers.id', 'suppliers.code', 'suppliers.name')
                    ->orderByDesc('total_amount')
                    ->get()
                    ->map(function ($row) {
                        $row->outstanding_amount = max(0, (float) $row->total_amount - (float) $row->total_paid);
                        return $row;
                    });

                $summary = [
                    'supplier_count' => (int) $rows->count(),
                    'total_purchase_count' => (int) $rows->sum('total_purchase_count'),
                    'total_amount' => (float) $rows->sum('total_amount'),
                    'total_paid' => (float) $rows->sum('total_paid'),
                    'total_outstanding' => (float) $rows->sum('outstanding_amount'),
                ];
            }

            if ($slug === 'purchase-by-product') {
                $viewType = 'purchase-by-product';

                $query = DB::table('purchase_items')
                    ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
                    ->join('products', 'purchase_items.product_id', '=', 'products.id')
                    ->where('purchases.status', 'received')
                    ->whereBetween('purchases.purchase_date', [$dateFrom, $dateTo]);

                if (!empty($outletId)) {
                    $query->where('purchases.outlet_id', $outletId);
                }

                $rows = $query
                    ->select(
                        'products.id as product_id',
                        'products.sku as product_sku',
                        'products.name as product_name',
                        'products.unit as product_unit'
                    )
                    ->selectRaw('COUNT(DISTINCT purchases.id) as total_purchase_count')
                    ->selectRaw('COALESCE(SUM(purchase_items.quantity), 0) as total_qty')
                    ->selectRaw('COALESCE(SUM(purchase_items.subtotal), 0) as total_amount')
                    ->groupBy('products.id', 'products.sku', 'products.name', 'products.unit')
                    ->orderByDesc('total_amount')
                    ->get()
                    ->map(function ($row) {
                        $qty = (float) ($row->total_qty ?? 0);
                        $amount = (float) ($row->total_amount ?? 0);
                        $row->avg_unit_price = $qty > 0 ? $amount / $qty : 0;
                        return $row;
                    });

                $rows = $rows->filter(fn($row) => $this->passesPurchaseByProductFilters($row, $request))
                    ->values();

                $summary = [
                    'product_count' => (int) $rows->count(),
                    'total_purchase_count' => (int) $rows->sum('total_purchase_count'),
                    'total_qty' => (float) $rows->sum('total_qty'),
                    'total_amount' => (float) $rows->sum('total_amount'),
                ];
            }

            if ($slug === 'purchase-by-category') {
                $viewType = 'purchase-by-category';

                $query = DB::table('purchase_items')
                    ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
                    ->join('products', 'purchase_items.product_id', '=', 'products.id')
                    ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
                    ->where('purchases.status', 'received')
                    ->whereBetween('purchases.purchase_date', [$dateFrom, $dateTo]);

                if (!empty($outletId)) {
                    $query->where('purchases.outlet_id', $outletId);
                }

                $rows = $query
                    ->selectRaw("COALESCE(product_categories.name, 'Tanpa Kategori') as category_name")
                    ->selectRaw('COUNT(DISTINCT purchases.id) as total_purchase_count')
                    ->selectRaw('COUNT(DISTINCT products.id) as total_product_count')
                    ->selectRaw('COALESCE(SUM(purchase_items.quantity), 0) as total_qty')
                    ->selectRaw('COALESCE(SUM(purchase_items.subtotal), 0) as total_amount')
                    ->groupBy('category_name')
                    ->orderByDesc('total_amount')
                    ->get();

                $summary = [
                    'category_count' => (int) $rows->count(),
                    'total_purchase_count' => (int) $rows->sum('total_purchase_count'),
                    'total_product_count' => (int) $rows->sum('total_product_count'),
                    'total_qty' => (float) $rows->sum('total_qty'),
                    'total_amount' => (float) $rows->sum('total_amount'),
                ];
            }

            if ($slug === 'purchase-unpaid') {
                $viewType = 'purchase-unpaid';

                $query = DB::table('purchases')
                    ->join('suppliers', 'purchases.supplier_id', '=', 'suppliers.id')
                    ->join('outlets', 'purchases.outlet_id', '=', 'outlets.id')
                    ->leftJoinSub($purchasePaymentSub, 'purchase_payments', function ($join) {
                        $join->on('purchase_payments.reference_id', '=', 'purchases.id');
                    })
                    ->where('purchases.status', 'received')
                    ->whereBetween('purchases.purchase_date', [$dateFrom, $dateTo]);

                if (!empty($outletId)) {
                    $query->where('purchases.outlet_id', $outletId);
                }

                $rows = $query
                    ->select(
                        'purchases.id as purchase_id',
                        'purchases.purchase_number',
                        'purchases.purchase_date',
                        'outlets.name as outlet_name',
                        'suppliers.name as supplier_name',
                        'purchases.total_amount'
                    )
                    ->selectRaw('COALESCE(purchase_payments.paid_amount, 0) as total_paid')
                    ->orderByDesc('purchases.purchase_date')
                    ->get()
                    ->map(function ($row) {
                        $row->outstanding_amount = max(0, (float) $row->total_amount - (float) $row->total_paid);
                        return $row;
                    })
                    ->filter(fn($row) => (float) $row->outstanding_amount > 0)
                    ->values();

                $summary = [
                    'total_purchase_count' => (int) $rows->count(),
                    'total_amount' => (float) $rows->sum('total_amount'),
                    'total_paid' => (float) $rows->sum('total_paid'),
                    'total_outstanding' => (float) $rows->sum('outstanding_amount'),
                ];
            }
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

        $rows = $this->paginateRowsForView($rows, $request);

        return view('admin.reports.catalog-show', [
            'slug' => $slug,
            'report' => $report,
            'categories' => $categories,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'outletId' => $outletId,
            'selectedOutletIds' => $selectedOutletIds,
            'selectedFromOutletIds' => $selectedFromOutletIds,
            'selectedToOutletIds' => $selectedToOutletIds,
            'selectedProductId' => $selectedProductId,
            'selectedUserId' => $selectedUserId,
            'outlets' => $outlets,
            'products' => $products,
            'users' => $users,
            'rows' => $rows,
            'summary' => $summary,
            'meta' => $meta,
            'viewType' => $viewType,
            'stockMovementFilterApplied' => $stockMovementFilterApplied,
        ]);
    }

    private function downloadSalesVsHppXlsx($rowsQuery, string $filename)
    {
        return (new GeneratorReportExport(
            headings: ['No Transaksi', 'Tanggal', 'Outlet', 'Produk', 'Qty', 'Total', 'HPP', 'Laba Kotor', 'Margin'],
            generatorFactory: fn () => $this->generateSalesVsHppExportRows($rowsQuery),
            columnFormats: [
                'E' => '#,##0.00',
                'F' => '#,##0.00',
                'G' => '#,##0.00',
                'H' => '#,##0.00',
                'I' => '#,##0.00',
            ],
        ))->download($filename, ExcelWriter::XLSX);
    }

    private function applySalesVsHppTableFilters($rowsQuery, Request $request): void
    {
        $lineTotalExpr = $this->salesVsHppLineTotalSql();
        $grossProfitExpr = '(' . $lineTotalExpr . ' - COALESCE(sale_items.cogs, 0))';
        $marginExpr = "CASE WHEN {$lineTotalExpr} > 0 THEN ROUND(({$grossProfitExpr} / {$lineTotalExpr}) * 100, 2) ELSE 0 END";

        if ($request->filled('filter_transaksi')) {
            $rowsQuery->where('sales.invoice_number', 'like', $this->reportLikeValue($request->input('filter_transaksi')));
        }

        if ($request->filled('filter_tanggal')) {
            $like = $this->reportLikeValue($request->input('filter_tanggal'));
            $rowsQuery->where(function ($query) use ($like) {
                $query->whereRaw("CAST(sales.sale_date AS CHAR) LIKE ?", [$like])
                    ->orWhereRaw("DATE_FORMAT(sales.sale_date, '%d/%m/%Y') LIKE ?", [$like])
                    ->orWhereRaw("DATE_FORMAT(sales.sale_date, '%d %b %Y') LIKE ?", [$like]);
            });
        }

        if ($request->filled('filter_outlet')) {
            $rowsQuery->where('outlets.name', 'like', $this->reportLikeValue($request->input('filter_outlet')));
        }

        if ($request->filled('filter_product')) {
            $like = $this->reportLikeValue($request->input('filter_product'));
            $rowsQuery->where(function ($query) use ($like) {
                $query->where('sale_items.product_name', 'like', $like)
                    ->orWhere('sale_items.product_sku', 'like', $like);
            });
        }

        if ($request->filled('filter_qty')) {
            $rowsQuery->whereRaw("CAST(sale_items.quantity AS CHAR) LIKE ?", [$this->reportLikeValue($request->input('filter_qty'))]);
        }

        if ($request->filled('filter_amount')) {
            $rowsQuery->whereRaw("CAST({$lineTotalExpr} AS CHAR) LIKE ?", [$this->reportLikeValue($request->input('filter_amount'))]);
        }

        if ($request->filled('filter_hpp')) {
            $rowsQuery->whereRaw("CAST(COALESCE(sale_items.cogs, 0) AS CHAR) LIKE ?", [$this->reportLikeValue($request->input('filter_hpp'))]);
        }

        if ($request->filled('filter_laba_kotor')) {
            $rowsQuery->whereRaw("CAST({$grossProfitExpr} AS CHAR) LIKE ?", [$this->reportLikeValue($request->input('filter_laba_kotor'))]);
        }

        if ($request->filled('filter_margin')) {
            $rowsQuery->whereRaw("CAST({$marginExpr} AS CHAR) LIKE ?", [$this->reportLikeValue($request->input('filter_margin'))]);
        }
    }

    private function salesVsHppLineTotalSql(): string
    {
        return "CASE
            WHEN sales.subtotal > 0 THEN ROUND((sale_items.subtotal / sales.subtotal) * sales.total_amount, 2)
            WHEN COALESCE(sale_item_counts.sale_item_count, 0) > 0 THEN ROUND(sales.total_amount / sale_item_counts.sale_item_count, 2)
            ELSE sale_items.subtotal
        END";
    }

    private function reportLikeValue(?string $value): string
    {
        return '%' . trim((string) $value) . '%';
    }

    private function generateSalesVsHppExportRows($rowsQuery): Generator
    {
        foreach ((clone $rowsQuery)->lazy(1000) as $row) {
            $row = $this->transformSalesVsHppRow($row);

            yield [
                $row->transaction_number,
                $row->sale_date,
                $row->outlet_name,
                $row->product_name,
                (float) ($row->qty ?? 0),
                (float) ($row->total_amount ?? 0),
                (float) ($row->hpp_amount ?? 0),
                (float) ($row->gross_profit ?? 0),
                (float) ($row->margin_percent ?? 0),
            ];
        }
    }

    private function transformSalesVsHppPaginator(LengthAwarePaginator $paginator): LengthAwarePaginator
    {
        $paginator->setCollection($this->transformSalesVsHppRows($paginator->getCollection()));

        return $paginator;
    }

    private function transformSalesVsHppRows(Collection $rows): Collection
    {
        return $rows->map(fn ($row) => $this->transformSalesVsHppRow($row));
    }

    private function transformSalesVsHppRow(object $row): object
    {
        $totalAmount = $this->allocateSalesVsHppLineTotal(
            itemSubtotal: (float) ($row->item_subtotal ?? $row->total_amount ?? 0),
            saleSubtotal: (float) ($row->sale_subtotal ?? 0),
            saleTotalAmount: (float) ($row->sale_total_amount ?? $row->total_amount ?? 0),
            saleItemCount: (int) ($row->sale_item_count ?? 0),
        );

        $hppAmount = (float) ($row->hpp_amount ?? 0);
        $grossProfit = $totalAmount - $hppAmount;

        $row->total_amount = $totalAmount;
        $row->gross_profit = $grossProfit;
        $row->margin_percent = $totalAmount > 0
            ? round(($grossProfit / $totalAmount) * 100, 2)
            : 0.0;

        return $row;
    }

    private function allocateSalesVsHppLineTotal(
        float $itemSubtotal,
        float $saleSubtotal,
        float $saleTotalAmount,
        int $saleItemCount
    ): float {
        if ($saleSubtotal > 0) {
            return $itemSubtotal + (($saleTotalAmount - $saleSubtotal) * ($itemSubtotal / $saleSubtotal));
        }

        if ($saleItemCount > 0) {
            return $saleTotalAmount / $saleItemCount;
        }

        return 0.0;
    }

    private function downloadSalesDiscountXlsx(
        $salesQuery,
        Request $request,
        string $filename,
        string $viewMode,
        string $filterType,
        ?string $specialFilterType
    ) {
        $isSummary = $viewMode === 'summary';

        return (new GeneratorReportExport(
            headings: $isSummary
                ? ['Tipe Diskon', 'Status Data', 'Metode Penjualan', 'Jumlah Transaksi', 'Nilai Jual (Gross)', 'Total Diskon', 'Net Sales']
                : ['Tipe Diskon', 'Status Data', 'No Transaksi', 'Tanggal', 'Outlet', 'Metode Penjualan', 'Pelanggan', 'Nilai Jual (Gross)', 'Total Diskon', 'Total Bayar', 'Catatan'],
            generatorFactory: $isSummary
                ? fn () => $this->generateSalesDiscountSummaryExportRows($salesQuery, $request, $filterType, $specialFilterType)
                : fn () => $this->generateSalesDiscountDetailExportRows($salesQuery, $request, $filterType, $specialFilterType),
            columnFormats: $isSummary
                ? [
                    'D' => '#,##0',
                    'E' => '#,##0.00',
                    'F' => '#,##0.00',
                    'G' => '#,##0.00',
                ]
                : [
                    'H' => '#,##0.00',
                    'I' => '#,##0.00',
                    'J' => '#,##0.00',
                ],
        ))->download($filename, ExcelWriter::XLSX);
    }

    private function generateSalesDiscountSummaryExportRows(
        $salesQuery,
        Request $request,
        string $filterType,
        ?string $specialFilterType
    ): Generator {
        $groupedRows = [];

        foreach ((clone $salesQuery)->lazy(1000) as $sale) {
            $row = $this->buildSalesDiscountRowFromRecord($sale);

            if (!$this->passesSalesDiscountPrimaryFilter($row, $filterType, $specialFilterType)) {
                continue;
            }

            $groupKey = implode('|', [
                $row['discount_source_key'] ?? 'none',
                $row['sales_type'] ?? 'regular',
                !empty($row['is_discount_anomaly']) ? 'anomaly' : 'valid',
            ]);

            if (!isset($groupedRows[$groupKey])) {
                $groupedRows[$groupKey] = [
                    'discount_source_label' => $row['discount_source_label'] ?? 'None',
                    'data_status_label' => $row['data_status_label'] ?? 'Valid',
                    'sales_type_label' => $row['sales_type_label'] ?? 'Regular',
                    'transaction_count' => 0,
                    'gross_value' => 0.0,
                    'effective_discount' => 0.0,
                    'net_sales' => 0.0,
                ];
            }

            $groupedRows[$groupKey]['transaction_count']++;
            $groupedRows[$groupKey]['gross_value'] += (float) ($row['gross_value'] ?? 0);
            $groupedRows[$groupKey]['effective_discount'] += (float) ($row['effective_discount'] ?? 0);
            $groupedRows[$groupKey]['net_sales'] += (float) ($row['net_sales'] ?? 0);
        }

        foreach ($groupedRows as $row) {
            if (!$this->passesSalesDiscountSummaryFilters($row, $request)) {
                continue;
            }

            yield [
                $row['discount_source_label'] ?? '',
                $row['data_status_label'] ?? '',
                $row['sales_type_label'] ?? '',
                (int) ($row['transaction_count'] ?? 0),
                (float) ($row['gross_value'] ?? 0),
                (float) ($row['effective_discount'] ?? 0),
                (float) ($row['net_sales'] ?? 0),
            ];
        }
    }

    private function generateSalesDiscountDetailExportRows(
        $salesQuery,
        Request $request,
        string $filterType,
        ?string $specialFilterType
    ): Generator {
        foreach ((clone $salesQuery)->lazy(1000) as $sale) {
            $row = $this->buildSalesDiscountRowFromRecord($sale);

            if (
                !$this->passesSalesDiscountPrimaryFilter($row, $filterType, $specialFilterType)
                || !$this->passesSalesDiscountDetailFilters($row, $request)
            ) {
                continue;
            }

            yield [
                $row['discount_source_label'] ?? '',
                $row['data_status_label'] ?? '',
                $row['invoice_number'] ?? '',
                $row['sale_date'] ?? '',
                $row['outlet_name'] ?? '',
                $row['sales_type_label'] ?? '',
                $row['customer_name'] ?? '',
                (float) ($row['gross_value'] ?? 0),
                (float) ($row['effective_discount'] ?? 0),
                (float) ($row['net_sales'] ?? 0),
                $row['notes'] ?? '',
            ];
        }
    }

    private function passesSalesDiscountPrimaryFilter(array $row, string $filterType, ?string $specialFilterType): bool
    {
        if ($filterType !== 'all') {
            return ($row['sales_type'] ?? null) === $filterType
                || ($specialFilterType !== null && ($row['special_discount_type'] ?? null) === $specialFilterType);
        }

        return (float) ($row['effective_discount'] ?? 0) > 0 || !empty($row['is_discount_anomaly']);
    }

    private function passesSalesDiscountSummaryFilters(array $row, Request $request): bool
    {
        return $this->matchesReportText($row['discount_source_label'] ?? '', $request->input('filter_tipe_diskon'))
            && $this->matchesReportText($row['sales_type_label'] ?? '', $request->input('filter_metode_penjualan'))
            && $this->matchesReportText($row['data_status_label'] ?? '', $request->input('filter_status_data'))
            && $this->matchesReportNumber($row['transaction_count'] ?? 0, $request->input('filter_jumlah_transaksi'))
            && $this->matchesReportNumber($row['gross_value'] ?? 0, $request->input('filter_gross'))
            && $this->matchesReportNumber($row['effective_discount'] ?? 0, $request->input('filter_diskon'))
            && $this->matchesReportNumber($row['net_sales'] ?? 0, $request->input('filter_net_sales'));
    }

    private function passesSalesDiscountDetailFilters(array $row, Request $request): bool
    {
        return $this->matchesReportText($row['discount_source_label'] ?? '', $request->input('filter_tipe_diskon'))
            && $this->matchesReportText($row['invoice_number'] ?? '', $request->input('filter_transaksi'))
            && $this->matchesReportText($row['sale_date'] ?? '', $request->input('filter_tanggal'))
            && $this->matchesReportText($row['outlet_name'] ?? '', $request->input('filter_outlet'))
            && $this->matchesReportText($row['sales_type_label'] ?? '', $request->input('filter_metode_penjualan'))
            && $this->matchesReportText($row['data_status_label'] ?? '', $request->input('filter_status_data'))
            && $this->matchesReportText($row['customer_name'] ?? '', $request->input('filter_pelanggan'))
            && $this->matchesReportNumber($row['gross_value'] ?? 0, $request->input('filter_gross'))
            && $this->matchesReportNumber($row['effective_discount'] ?? 0, $request->input('filter_diskon'))
            && $this->matchesReportNumber($row['net_sales'] ?? 0, $request->input('filter_net_sales'));
    }

    private function passesPurchaseByProductFilters(object $row, Request $request): bool
    {
        $productSearch = trim(implode(' ', array_filter([
            (string) ($row->product_name ?? ''),
            (string) ($row->product_sku ?? ''),
        ])));

        return $this->matchesReportText($productSearch, $request->input('filter_product'))
            && $this->matchesReportNumber($row->total_purchase_count ?? 0, $request->input('filter_jumlah_po'))
            && $this->matchesReportNumber($row->total_qty ?? 0, $request->input('filter_qty'))
            && $this->matchesReportNumber($row->avg_unit_price ?? 0, $request->input('filter_avg'))
            && $this->matchesReportNumber($row->total_amount ?? 0, $request->input('filter_amount'));
    }

    private function matchesReportText($value, ?string $keyword): bool
    {
        $keyword = trim((string) $keyword);

        if ($keyword === '') {
            return true;
        }

        return stripos((string) $value, $keyword) !== false;
    }

    private function matchesReportNumber($value, ?string $keyword): bool
    {
        $keyword = trim((string) $keyword);

        if ($keyword === '') {
            return true;
        }

        $number = (float) $value;
        $variants = array_unique([
            (string) $value,
            (string) $number,
            number_format($number, 0, ',', '.'),
            number_format($number, 2, ',', '.'),
        ]);

        foreach ($variants as $variant) {
            if (stripos($variant, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    private function paginateRowsForView($rows, Request $request, int $perPage = 100)
    {
        if ($rows instanceof AbstractPaginator || $rows === null) {
            return $rows;
        }

        $collection = $rows instanceof Collection
            ? $rows->values()
            : collect($rows)->values();

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

    private function buildExportPayload(string $viewType, $rows, array $summary): array
    {
        if ($rows instanceof AbstractPaginator) {
            $rowsCollection = $rows->getCollection();
        } elseif ($rows instanceof \Illuminate\Support\Collection) {
            $rowsCollection = $rows;
        } else {
            $rowsCollection = collect($rows);
        }

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
            ], $rowsCollection->map(fn($row) => (array) $row)->all()];
        }

        if ($viewType === 'sales-summary') {
            return [[
                ['key' => 'gross_value', 'label' => 'Gross Sales', 'type' => 'number', 'decimals' => 2],
                ['key' => 'effective_discount', 'label' => 'Total Diskon Efektif', 'type' => 'number', 'decimals' => 2],
                ['key' => 'item_discount_total', 'label' => 'Diskon Item', 'type' => 'number', 'decimals' => 2],
                ['key' => 'header_discount_total', 'label' => 'Diskon Header', 'type' => 'number', 'decimals' => 2],
                ['key' => 'total_service_charge', 'label' => 'Service Charge', 'type' => 'number', 'decimals' => 2],
                ['key' => 'total_tax', 'label' => 'Tax', 'type' => 'number', 'decimals' => 2],
                ['key' => 'net_sales', 'label' => 'Net Sales', 'type' => 'number', 'decimals' => 2],
                ['key' => 'total_transactions', 'label' => 'Jumlah Transaksi', 'type' => 'number', 'decimals' => 0],
                ['key' => 'avg_transaction', 'label' => 'Rata-rata per Invoice', 'type' => 'number', 'decimals' => 2],
                ['key' => 'discount_anomaly_transactions', 'label' => 'Transaksi Anomali Diskon', 'type' => 'number', 'decimals' => 0],
            ], [[
                'gross_value' => (float) ($summary['total_sales'] ?? 0),
                'effective_discount' => (float) ($summary['total_discount'] ?? 0),
                'item_discount_total' => (float) ($summary['item_discount_total'] ?? 0),
                'header_discount_total' => (float) ($summary['header_discount_total'] ?? 0),
                'total_service_charge' => (float) ($summary['total_service_charge'] ?? 0),
                'total_tax' => (float) ($summary['total_tax'] ?? 0),
                'net_sales' => (float) ($summary['total_amount'] ?? 0),
                'total_transactions' => (int) ($summary['total_transactions'] ?? 0),
                'avg_transaction' => (float) ($summary['avg_transaction'] ?? 0),
                'discount_anomaly_transactions' => (int) ($summary['discount_anomaly_transactions'] ?? 0),
            ]]];
        }

        if ($viewType === 'cancelled-sales') {
            return [[
                ['key' => 'invoice_number', 'label' => 'No Transaksi', 'type' => 'text'],
                ['key' => 'sale_date', 'label' => 'Tanggal', 'type' => 'text'],
                ['key' => 'status_label', 'label' => 'Status', 'type' => 'text'],
                ['key' => 'outlet_name', 'label' => 'Outlet', 'type' => 'text'],
                ['key' => 'cashier_name', 'label' => 'Kasir', 'type' => 'text'],
                ['key' => 'customer_name', 'label' => 'Pelanggan', 'type' => 'text'],
                ['key' => 'sales_type_label', 'label' => 'Metode Penjualan', 'type' => 'text'],
                ['key' => 'line_count', 'label' => 'Jumlah Baris', 'type' => 'number', 'decimals' => 0],
                ['key' => 'item_qty', 'label' => 'Jumlah Item', 'type' => 'number', 'decimals' => 2],
                ['key' => 'gross_amount', 'label' => 'Gross', 'type' => 'number', 'decimals' => 2],
                ['key' => 'total_amount', 'label' => 'Nilai Invoice', 'type' => 'number', 'decimals' => 2],
                ['key' => 'paid_amount', 'label' => 'Sudah Dibayar', 'type' => 'number', 'decimals' => 2],
                ['key' => 'payment_methods', 'label' => 'Metode Pembayaran', 'type' => 'text'],
                ['key' => 'cancelled_at', 'label' => 'Waktu Update', 'type' => 'text'],
                ['key' => 'notes', 'label' => 'Catatan', 'type' => 'text'],
            ], $rowsCollection->map(fn($row) => (array) $row)->all()];
        }

        if ($viewType === 'sales-discount') {
            if (($summary['view_mode'] ?? 'summary') === 'summary') {
                return [[
                    ['key' => 'discount_source_label', 'label' => 'Tipe Diskon', 'type' => 'text'],
                    ['key' => 'data_status_label', 'label' => 'Status Data', 'type' => 'text'],
                    ['key' => 'sales_type_label', 'label' => 'Metode Penjualan', 'type' => 'text'],
                    ['key' => 'transaction_count', 'label' => 'Jumlah Transaksi', 'type' => 'number', 'decimals' => 0],
                    ['key' => 'gross_value', 'label' => 'Nilai Jual (Gross)', 'type' => 'number', 'decimals' => 2],
                    ['key' => 'effective_discount', 'label' => 'Total Diskon', 'type' => 'number', 'decimals' => 2],
                    ['key' => 'net_sales', 'label' => 'Net Sales', 'type' => 'number', 'decimals' => 2],
                ], $rowsCollection->map(fn($row) => (array) $row)->all()];
            }

            return [[
                ['key' => 'discount_source_label', 'label' => 'Tipe Diskon', 'type' => 'text'],
                ['key' => 'data_status_label', 'label' => 'Status Data', 'type' => 'text'],
                ['key' => 'invoice_number', 'label' => 'No Transaksi', 'type' => 'text'],
                ['key' => 'sale_date', 'label' => 'Tanggal', 'type' => 'text'],
                ['key' => 'outlet_name', 'label' => 'Outlet', 'type' => 'text'],
                ['key' => 'sales_type_label', 'label' => 'Metode Penjualan', 'type' => 'text'],
                ['key' => 'customer_name', 'label' => 'Pelanggan', 'type' => 'text'],
                ['key' => 'gross_value', 'label' => 'Nilai Jual (Gross)', 'type' => 'number', 'decimals' => 2],
                ['key' => 'effective_discount', 'label' => 'Total Diskon', 'type' => 'number', 'decimals' => 2],
                ['key' => 'net_sales', 'label' => 'Total Bayar', 'type' => 'number', 'decimals' => 2],
                ['key' => 'notes', 'label' => 'Catatan', 'type' => 'text'],
            ], $rowsCollection->map(fn($row) => (array) $row)->all()];
        }

        if ($viewType === 'promo-report') {
            if (($summary['view_mode'] ?? 'summary') === 'summary') {
                return [[
                    ['key' => 'promo_source_kind_label', 'label' => 'Jenis', 'type' => 'text'],
                    ['key' => 'promo_source_label', 'label' => 'Promo/Voucher', 'type' => 'text'],
                    ['key' => 'transaction_count', 'label' => 'Jumlah Transaksi', 'type' => 'number', 'decimals' => 0],
                    ['key' => 'gross_value', 'label' => 'Nilai Jual (Gross)', 'type' => 'number', 'decimals' => 2],
                    ['key' => 'effective_discount', 'label' => 'Total Diskon', 'type' => 'number', 'decimals' => 2],
                    ['key' => 'net_sales', 'label' => 'Net Sales', 'type' => 'number', 'decimals' => 2],
                ], $rowsCollection->map(fn($row) => (array) $row)->all()];
            }

            return [[
                ['key' => 'promo_source_kind_label', 'label' => 'Jenis', 'type' => 'text'],
                ['key' => 'promo_source_label', 'label' => 'Promo/Voucher', 'type' => 'text'],
                ['key' => 'invoice_number', 'label' => 'No Transaksi', 'type' => 'text'],
                ['key' => 'sale_date', 'label' => 'Tanggal', 'type' => 'text'],
                ['key' => 'outlet_name', 'label' => 'Outlet', 'type' => 'text'],
                ['key' => 'sales_type_label', 'label' => 'Metode Penjualan', 'type' => 'text'],
                ['key' => 'customer_name', 'label' => 'Pelanggan', 'type' => 'text'],
                ['key' => 'gross_value', 'label' => 'Nilai Jual (Gross)', 'type' => 'number', 'decimals' => 2],
                ['key' => 'effective_discount', 'label' => 'Total Diskon', 'type' => 'number', 'decimals' => 2],
                ['key' => 'net_sales', 'label' => 'Net Sales', 'type' => 'number', 'decimals' => 2],
                ['key' => 'notes', 'label' => 'Catatan', 'type' => 'text'],
            ], $rowsCollection->map(fn($row) => (array) $row)->all()];
        }

        if ($viewType === 'stock-movement') {
            return [[
                ['key' => 'product_name', 'label' => 'Produk', 'type' => 'text'],
                ['key' => 'product_sku', 'label' => 'SKU', 'type' => 'text'],
                ['key' => 'outlet_name', 'label' => 'Outlet', 'type' => 'text'],
                ['key' => 'opening_qty', 'label' => 'Stok Awal Qty', 'type' => 'number', 'decimals' => 2],
                ['key' => 'opening_value', 'label' => 'Stok Awal Nominal', 'type' => 'number', 'decimals' => 2],
                ['key' => 'purchase_in_qty', 'label' => 'Pembelian Masuk Qty', 'type' => 'number', 'decimals' => 2],
                ['key' => 'purchase_in_value', 'label' => 'Pembelian Masuk Nominal', 'type' => 'number', 'decimals' => 2],
                ['key' => 'sale_return_in_qty', 'label' => 'Retur Penjualan Masuk Qty', 'type' => 'number', 'decimals' => 2],
                ['key' => 'sale_return_in_value', 'label' => 'Retur Penjualan Masuk Nominal', 'type' => 'number', 'decimals' => 2],
                ['key' => 'transfer_in_qty', 'label' => 'Mutasi In Qty', 'type' => 'number', 'decimals' => 2],
                ['key' => 'transfer_in_value', 'label' => 'Mutasi In Nominal', 'type' => 'number', 'decimals' => 2],
                ['key' => 'adjustment_in_qty', 'label' => 'Adjustment Plus Qty', 'type' => 'number', 'decimals' => 2],
                ['key' => 'adjustment_in_value', 'label' => 'Adjustment Plus Nominal', 'type' => 'number', 'decimals' => 2],
                ['key' => 'sale_out_qty', 'label' => 'Penjualan Keluar Qty', 'type' => 'number', 'decimals' => 2],
                ['key' => 'sale_out_value', 'label' => 'Penjualan Keluar Nominal', 'type' => 'number', 'decimals' => 2],
                ['key' => 'hpp_penjualan_kotor', 'label' => 'HPP Penjualan Kotor', 'type' => 'number', 'decimals' => 2],
                ['key' => 'hpp_reversal_void', 'label' => 'HPP Reversal Void', 'type' => 'number', 'decimals' => 2],
                ['key' => 'hpp_penjualan_bersih', 'label' => 'HPP Penjualan Bersih', 'type' => 'number', 'decimals' => 2],
                ['key' => 'transfer_out_qty', 'label' => 'Mutasi Out Qty', 'type' => 'number', 'decimals' => 2],
                ['key' => 'transfer_out_value', 'label' => 'Mutasi Out Nominal', 'type' => 'number', 'decimals' => 2],
                ['key' => 'adjustment_out_qty', 'label' => 'Adjustment Minus Qty', 'type' => 'number', 'decimals' => 2],
                ['key' => 'adjustment_out_value', 'label' => 'Adjustment Minus Nominal', 'type' => 'number', 'decimals' => 2],
                ['key' => 'other_in_qty', 'label' => 'Lainnya Masuk Qty', 'type' => 'number', 'decimals' => 2],
                ['key' => 'other_in_value', 'label' => 'Lainnya Masuk Nominal', 'type' => 'number', 'decimals' => 2],
                ['key' => 'other_out_qty', 'label' => 'Lainnya Keluar Qty', 'type' => 'number', 'decimals' => 2],
                ['key' => 'other_out_value', 'label' => 'Lainnya Keluar Nominal', 'type' => 'number', 'decimals' => 2],
                ['key' => 'total_in_qty', 'label' => 'Total Masuk Qty', 'type' => 'number', 'decimals' => 2],
                ['key' => 'total_in_value', 'label' => 'Total Masuk Nominal', 'type' => 'number', 'decimals' => 2],
                ['key' => 'total_out_qty', 'label' => 'Total Keluar Qty', 'type' => 'number', 'decimals' => 2],
                ['key' => 'total_out_value', 'label' => 'Total Keluar Nominal', 'type' => 'number', 'decimals' => 2],
                ['key' => 'closing_qty', 'label' => 'Stok Akhir Qty', 'type' => 'number', 'decimals' => 2],
                ['key' => 'closing_value', 'label' => 'Stok Akhir Nominal', 'type' => 'number', 'decimals' => 2],
                ['key' => 'current_avg_cost', 'label' => 'Avg Cost Saat Ini', 'type' => 'number', 'decimals' => 4],
            ], $rowsCollection->map(fn($row) => (array) $row)->all()];
        }

        if ($viewType === 'stock-adjustment') {
            return [[
                ['key' => 'mutation_date_display', 'label' => 'Tanggal', 'type' => 'text'],
                ['key' => 'mutation_time_display', 'label' => 'Jam Input', 'type' => 'text'],
                ['key' => 'product_name', 'label' => 'Produk', 'type' => 'text'],
                ['key' => 'product_sku', 'label' => 'SKU', 'type' => 'text'],
                ['key' => 'product_unit', 'label' => 'Satuan', 'type' => 'text'],
                ['key' => 'outlet_name', 'label' => 'Outlet', 'type' => 'text'],
                ['key' => 'stock_before', 'label' => 'Stok Sebelum', 'type' => 'number', 'decimals' => 2],
                ['key' => 'stock_after', 'label' => 'Stok Sesudah', 'type' => 'number', 'decimals' => 2],
                ['key' => 'quantity', 'label' => 'Selisih Qty', 'type' => 'number', 'decimals' => 2],
                ['key' => 'quantity_abs', 'label' => 'Qty Absolut', 'type' => 'number', 'decimals' => 2],
                ['key' => 'direction_label', 'label' => 'Arah', 'type' => 'text'],
                ['key' => 'unit_cost', 'label' => 'Unit Cost', 'type' => 'number', 'decimals' => 2],
                ['key' => 'total_cost', 'label' => 'Nominal Adjustment', 'type' => 'number', 'decimals' => 2],
                ['key' => 'creator_name', 'label' => 'Diinput Oleh', 'type' => 'text'],
                ['key' => 'notes', 'label' => 'Catatan', 'type' => 'text'],
            ], $rowsCollection->map(fn($row) => (array) $row)->all()];
        }

        if ($viewType === 'inventory-reconciliation') {
            return [[
                ['key' => 'outlet_name', 'label' => 'Outlet', 'type' => 'text'],
                ['key' => 'mutation_inventory_value', 'label' => 'Nilai Mutasi Persediaan', 'type' => 'number', 'decimals' => 2],
                ['key' => 'neraca_inventory_value', 'label' => 'Nilai Persediaan Neraca', 'type' => 'number', 'decimals' => 2],
                ['key' => 'gap_value', 'label' => 'Selisih', 'type' => 'number', 'decimals' => 2],
                ['key' => 'is_balanced', 'label' => 'Balance', 'type' => 'text'],
            ], $rowsCollection->map(function ($row) {
                $arr = (array) $row;
                $arr['is_balanced'] = !empty($arr['is_balanced']) ? 'YES' : 'NO';
                return $arr;
            })->all()];
        }

        if ($viewType === 'stock-transfer') {
            if (($summary['view_mode'] ?? 'summary') === 'summary') {
                return [[
                    ['key' => 'transfer_date', 'label' => 'Tanggal', 'type' => 'text'],
                    ['key' => 'total_transfers', 'label' => 'Total Mutasi', 'type' => 'number', 'decimals' => 0],
                    ['key' => 'total_qty', 'label' => 'Total Qty Dikirim', 'type' => 'number', 'decimals' => 2],
                    ['key' => 'total_received_qty', 'label' => 'Total Qty Diterima', 'type' => 'number', 'decimals' => 2],
                    ['key' => 'total_nominal', 'label' => 'Total Nilai (Rp)', 'type' => 'number', 'decimals' => 2],
                ], $rowsCollection->map(fn($row) => (array) $row)->all()];
            }
            return [[
                ['key' => 'transfer_number', 'label' => 'No Transaksi', 'type' => 'text'],
                ['key' => 'transfer_date', 'label' => 'Tanggal', 'type' => 'text'],
                ['key' => 'status', 'label' => 'Status', 'type' => 'text'],
                ['key' => 'from_outlet_name', 'label' => 'Dari Outlet', 'type' => 'text'],
                ['key' => 'to_outlet_name', 'label' => 'Ke Outlet', 'type' => 'text'],
                ['key' => 'product_name', 'label' => 'Produk', 'type' => 'text'],
                ['key' => 'quantity', 'label' => 'Qty Dikirim', 'type' => 'number', 'decimals' => 2],
                ['key' => 'received_quantity', 'label' => 'Qty Diterima', 'type' => 'number', 'decimals' => 2],
                ['key' => 'nominal_value', 'label' => 'HPP (Nilai Rp)', 'type' => 'number', 'decimals' => 2],
                ['key' => 'notes', 'label' => 'Catatan', 'type' => 'text'],
            ], $rowsCollection->map(fn($row) => (array) $row)->all()];
        }

        if ($viewType === 'purchase-summary') {
            return [[
                ['key' => 'purchase_date', 'label' => 'Tanggal', 'type' => 'text'],
                ['key' => 'total_purchase_count', 'label' => 'Jumlah Pembelian', 'type' => 'number', 'decimals' => 0],
                ['key' => 'total_amount', 'label' => 'Total Pembelian', 'type' => 'number', 'decimals' => 2],
                ['key' => 'total_paid', 'label' => 'Total Dibayar', 'type' => 'number', 'decimals' => 2],
                ['key' => 'outstanding_amount', 'label' => 'Sisa Hutang', 'type' => 'number', 'decimals' => 2],
            ], $rowsCollection->map(fn($row) => (array) $row)->all()];
        }

        if ($viewType === 'purchase-by-supplier') {
            return [[
                ['key' => 'supplier_code', 'label' => 'Kode Supplier', 'type' => 'text'],
                ['key' => 'supplier_name', 'label' => 'Supplier', 'type' => 'text'],
                ['key' => 'total_purchase_count', 'label' => 'Jumlah Pembelian', 'type' => 'number', 'decimals' => 0],
                ['key' => 'total_amount', 'label' => 'Total Pembelian', 'type' => 'number', 'decimals' => 2],
                ['key' => 'total_paid', 'label' => 'Total Dibayar', 'type' => 'number', 'decimals' => 2],
                ['key' => 'outstanding_amount', 'label' => 'Sisa Hutang', 'type' => 'number', 'decimals' => 2],
            ], $rowsCollection->map(fn($row) => (array) $row)->all()];
        }

        if ($viewType === 'purchase-by-product') {
            return [[
                ['key' => 'product_sku', 'label' => 'SKU', 'type' => 'text'],
                ['key' => 'product_name', 'label' => 'Produk', 'type' => 'text'],
                ['key' => 'product_unit', 'label' => 'Satuan', 'type' => 'text'],
                ['key' => 'total_purchase_count', 'label' => 'Jumlah Pembelian', 'type' => 'number', 'decimals' => 0],
                ['key' => 'total_qty', 'label' => 'Total Qty', 'type' => 'number', 'decimals' => 2],
                ['key' => 'avg_unit_price', 'label' => 'Rata2 Harga', 'type' => 'number', 'decimals' => 2],
                ['key' => 'total_amount', 'label' => 'Total Pembelian', 'type' => 'number', 'decimals' => 2],
            ], $rowsCollection->map(fn($row) => (array) $row)->all()];
        }

        if ($viewType === 'purchase-by-category') {
            return [[
                ['key' => 'category_name', 'label' => 'Kategori', 'type' => 'text'],
                ['key' => 'total_purchase_count', 'label' => 'Jumlah Pembelian', 'type' => 'number', 'decimals' => 0],
                ['key' => 'total_product_count', 'label' => 'Jumlah Produk', 'type' => 'number', 'decimals' => 0],
                ['key' => 'total_qty', 'label' => 'Total Qty', 'type' => 'number', 'decimals' => 2],
                ['key' => 'total_amount', 'label' => 'Total Pembelian', 'type' => 'number', 'decimals' => 2],
            ], $rowsCollection->map(fn($row) => (array) $row)->all()];
        }

        if ($viewType === 'purchase-unpaid') {
            return [[
                ['key' => 'purchase_number', 'label' => 'No PO', 'type' => 'text'],
                ['key' => 'purchase_date', 'label' => 'Tanggal', 'type' => 'text'],
                ['key' => 'outlet_name', 'label' => 'Outlet', 'type' => 'text'],
                ['key' => 'supplier_name', 'label' => 'Supplier', 'type' => 'text'],
                ['key' => 'total_amount', 'label' => 'Total Pembelian', 'type' => 'number', 'decimals' => 2],
                ['key' => 'total_paid', 'label' => 'Total Dibayar', 'type' => 'number', 'decimals' => 2],
                ['key' => 'outstanding_amount', 'label' => 'Sisa Hutang', 'type' => 'number', 'decimals' => 2],
            ], $rowsCollection->map(fn($row) => (array) $row)->all()];
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

            return [$columns, $rowsCollection->map(fn($row) => (array) $row)->all()];
        }

        $fallbackRows = collect($summary)->map(function ($value, $key) {
            return ['metric' => (string) $key, 'value' => is_scalar($value) ? $value : json_encode($value)];
        })->values()->all();

        return [[
            ['key' => 'metric', 'label' => 'Metric', 'type' => 'text'],
            ['key' => 'value', 'label' => 'Value', 'type' => 'text'],
        ], $fallbackRows];
    }

    private function buildSalesDiscountRow(\App\Models\Sale $sale): array
    {
        $grossValue = 0.0;

        foreach ($sale->items as $item) {
            $itemPrice = (float) $item->unit_price;
            $itemGross = ((float) $item->subtotal) + ((float) $item->discount_amount);

            if ($itemPrice <= 0 && $item->product) {
                $itemGross = (float) $item->quantity * (float) $item->product->selling_price;
            }

            $paidAmount = (float) $item->subtotal;
            if ($itemGross < $paidAmount) {
                $itemGross = $paidAmount;
            }

            $grossValue += $itemGross;
        }

        $itemsPaid = (float) $sale->items->sum('subtotal');
        $itemLevelDiscount = max(0, $grossValue - $itemsPaid);
        $effectiveDiscount = $itemLevelDiscount + (float) $sale->discount_amount;

        $isDiscountAnomaly = SpecialPromotion::isSpecialSalesType($sale->sales_type)
            && $sale->promotion === null
            && $sale->voucher === null
            && $effectiveDiscount <= 0.0001;

        $discountMeta = $this->resolveDiscountTypeMeta(
            promotionName: $sale->promotion?->name,
            voucherName: $sale->voucher?->name,
            voucherCode: $sale->voucher_code ?? $sale->voucher?->code,
            salesType: $sale->sales_type,
            discountType: $sale->discount_type,
            totalDiscount: $effectiveDiscount,
            isDiscountAnomaly: $isDiscountAnomaly,
        );

        return [
            'id' => $sale->id,
            'invoice_number' => $sale->invoice_number,
            'sale_date' => $this->resolveSaleDateLabel($sale),
            'outlet_name' => $this->resolveDisplayText(data_get($sale, 'outlet.name')),
            'sales_type' => $sale->sales_type ?? 'regular',
            'sales_type_label' => $this->formatSalesTypeLabel($sale->sales_type),
            'special_discount_type' => SpecialPromotion::classify(
                $sale->promotion?->code,
                $sale->promotion?->name,
                $sale->sales_type
            ),
            'discount_type' => $sale->discount_type ?? 'none',
            'discount_source_key' => $discountMeta['key'],
            'discount_source_kind' => $discountMeta['kind'] ?? 'none',
            'discount_type_label' => $discountMeta['label'],
            'discount_source_label' => $discountMeta['label'],
            'customer_name' => $this->resolveDisplayText($sale->resolved_customer_name, 'Walk-in'),
            'gross_value' => $grossValue,
            'net_sales' => (float) $sale->total_amount,
            'effective_discount' => $effectiveDiscount,
            'total_discount' => $effectiveDiscount,
            'is_discount_anomaly' => $isDiscountAnomaly,
            'data_status_label' => $isDiscountAnomaly ? 'Anomali' : 'Valid',
            'notes' => $sale->notes,
            'items_count' => $sale->items->count(),
        ];
    }

    private function buildSalesDiscountRowFromRecord(object $sale): array
    {
        $itemLevelDiscount = max(0, (float) ($sale->item_discount_amount ?? 0));
        $effectiveDiscount = $itemLevelDiscount + (float) ($sale->discount_amount ?? 0);

        $isDiscountAnomaly = SpecialPromotion::isSpecialSalesType($sale->sales_type ?? null)
            && blank($sale->promotion_name ?? null)
            && blank($sale->voucher_name ?? null)
            && $effectiveDiscount <= 0.0001;

        $discountMeta = $this->resolveDiscountTypeMeta(
            promotionName: $sale->promotion_name ?? null,
            voucherName: $sale->voucher_name ?? null,
            voucherCode: ($sale->voucher_code ?? null) ?: ($sale->voucher_table_code ?? null),
            salesType: $sale->sales_type ?? null,
            discountType: $sale->discount_type ?? null,
            totalDiscount: $effectiveDiscount,
            isDiscountAnomaly: $isDiscountAnomaly,
        );

        $specialDiscountType = SpecialPromotion::classify(
            $sale->promotion_code ?? null,
            $sale->promotion_name ?? null,
            $sale->sales_type ?? null
        );

        return [
            'id' => $sale->id,
            'invoice_number' => $sale->invoice_number,
            'sale_date' => $this->resolveDisplayDate($sale->sale_date ?? null),
            'outlet_name' => $this->resolveDisplayText($sale->outlet_name ?? null),
            'sales_type' => $sale->sales_type ?? 'regular',
            'sales_type_label' => $this->formatSalesTypeLabel($sale->sales_type ?? null),
            'special_discount_type' => $specialDiscountType,
            'discount_type' => $sale->discount_type ?? 'none',
            'discount_source_key' => $discountMeta['key'],
            'discount_source_kind' => $discountMeta['kind'] ?? 'none',
            'discount_type_label' => $discountMeta['label'],
            'discount_source_label' => $discountMeta['label'],
            'customer_name' => $this->resolveDisplayText(($sale->customer_name ?? null) ?: ($sale->customer_relation_name ?? null), 'Walk-in'),
            'gross_value' => (float) ($sale->gross_value ?? 0),
            'net_sales' => (float) ($sale->total_amount ?? 0),
            'effective_discount' => $effectiveDiscount,
            'total_discount' => $effectiveDiscount,
            'is_discount_anomaly' => $isDiscountAnomaly,
            'data_status_label' => $isDiscountAnomaly ? 'Anomali' : 'Valid',
            'notes' => $sale->notes ?? null,
            'items_count' => null,
        ];
    }

    private function formatSalesTypeLabel(?string $salesType): string
    {
        $normalized = trim((string) $salesType);

        if ($normalized === '') {
            return 'Regular';
        }

        if (SpecialPromotion::isSpecialSalesType($normalized)) {
            return SpecialPromotion::formatLabel($normalized);
        }

        return ucfirst(str_replace('_', ' ', $normalized));
    }

    private function formatPromoSourceKindLabel(?string $kind): string
    {
        return match (trim((string) $kind)) {
            'promotion' => 'Promo',
            'voucher' => 'Voucher',
            'voucher_code' => 'Voucher Code',
            default => 'Promo',
        };
    }

    private function resolveDiscountTypeMeta(
        ?string $promotionName,
        ?string $voucherName,
        ?string $voucherCode,
        ?string $salesType,
        ?string $discountType,
        float $totalDiscount,
        bool $isDiscountAnomaly = false
    ): array
    {
        $promotionName = trim((string) $promotionName);
        if ($promotionName !== '') {
            return [
                'key' => 'promotion:' . strtolower($promotionName),
                'label' => $promotionName,
                'kind' => 'promotion',
            ];
        }

        $voucherName = trim((string) $voucherName);
        if ($voucherName !== '') {
            return [
                'key' => 'voucher:' . strtolower($voucherName),
                'label' => $voucherName,
                'kind' => 'voucher',
            ];
        }

        $voucherCode = trim((string) $voucherCode);
        if ($voucherCode !== '') {
            return [
                'key' => 'voucher-code:' . strtolower($voucherCode),
                'label' => $voucherCode,
                'kind' => 'voucher_code',
            ];
        }

        if ($isDiscountAnomaly) {
            $salesTypeLabel = $this->formatSalesTypeLabel($salesType);
            return [
                'key' => 'anomaly:' . strtolower(trim((string) $salesType)),
                'label' => $salesTypeLabel . ' (Anomali)',
                'kind' => 'anomaly',
            ];
        }

        $discountTypeKey = strtolower(trim((string) $discountType));

        return match ($discountTypeKey) {
            'percentage' => ['key' => 'discount-type:percentage', 'label' => 'Persentase', 'kind' => 'discount_type'],
            'fixed' => ['key' => 'discount-type:fixed', 'label' => 'Nominal', 'kind' => 'discount_type'],
            default => $totalDiscount > 0
                ? ['key' => 'discount-type:item-level', 'label' => 'Diskon Item', 'kind' => 'item_level']
                : ['key' => 'discount-type:none', 'label' => 'Tanpa Diskon', 'kind' => 'none'],
        };
    }

    private function resolveSaleDateLabel(\App\Models\Sale $sale): string
    {
        try {
            return $this->resolveDisplayDate($sale->sale_date);
        } catch (\Throwable) {
            return $this->resolveDisplayDate($sale->getRawOriginal('sale_date'));
        }
    }

    private function resolveDisplayDate(mixed $value, string $fallback = '-'): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return $this->resolveDisplayText($value, $fallback);
    }

    private function resolveDisplayText(mixed $value, string $fallback = '-'): string
    {
        $text = trim((string) $value);

        return $text !== '' ? $text : $fallback;
    }

    private function stockAdjustmentReport(
        string $dateFrom,
        string $dateTo,
        ?int $outletId = null,
        ?int $productId = null,
        ?int $userId = null
    ): array {
        $query = DB::table('stock_mutations')
            ->join('products', 'stock_mutations.product_id', '=', 'products.id')
            ->join('outlets', 'stock_mutations.outlet_id', '=', 'outlets.id')
            ->leftJoin('users', 'stock_mutations.created_by', '=', 'users.id')
            ->where('stock_mutations.mutation_type', 'adjustment')
            ->whereBetween('stock_mutations.mutation_date', [$dateFrom, $dateTo]);

        if (!empty($outletId)) {
            $query->where('stock_mutations.outlet_id', $outletId);
        }

        if (!empty($productId)) {
            $query->where('stock_mutations.product_id', $productId);
        }

        if (!empty($userId)) {
            $query->where('stock_mutations.created_by', $userId);
        }

        $rows = $query
            ->select(
                'stock_mutations.id',
                'stock_mutations.mutation_date',
                'stock_mutations.created_at',
                'stock_mutations.quantity',
                'stock_mutations.unit_cost',
                'stock_mutations.total_cost',
                'stock_mutations.stock_before',
                'stock_mutations.stock_after',
                'stock_mutations.notes',
                'stock_mutations.created_by',
                'products.id as product_id',
                'products.name as product_name',
                'products.sku as product_sku',
                'products.unit as product_unit',
                'outlets.id as outlet_id',
                'outlets.name as outlet_name',
                'users.name as creator_name'
            )
            ->orderByDesc('stock_mutations.mutation_date')
            ->orderByDesc('stock_mutations.created_at')
            ->orderByDesc('stock_mutations.id')
            ->get()
            ->map(function ($row) {
                $createdAt = $row->created_at ? \Carbon\Carbon::parse($row->created_at) : null;
                $mutationDate = $row->mutation_date ? \Carbon\Carbon::parse($row->mutation_date) : null;
                $qty = round((float) ($row->quantity ?? 0), 2);
                $totalCost = round((float) ($row->total_cost ?? 0), 2);

                return (object) [
                    'id' => (int) $row->id,
                    'mutation_date' => $mutationDate?->toDateString(),
                    'mutation_date_display' => $mutationDate?->format('d/m/Y') ?? '-',
                    'mutation_time_display' => $createdAt?->format('H:i') ?? '-',
                    'created_at' => $createdAt?->toDateTimeString(),
                    'product_id' => (int) $row->product_id,
                    'product_name' => $row->product_name,
                    'product_sku' => $row->product_sku,
                    'product_unit' => $row->product_unit ?: 'pcs',
                    'outlet_id' => (int) $row->outlet_id,
                    'outlet_name' => $row->outlet_name,
                    'stock_before' => round((float) ($row->stock_before ?? 0), 2),
                    'stock_after' => round((float) ($row->stock_after ?? 0), 2),
                    'quantity' => $qty,
                    'quantity_abs' => abs($qty),
                    'direction_label' => $qty > 0 ? 'PLUS' : ($qty < 0 ? 'MINUS' : 'NETRAL'),
                    'direction_color' => $qty > 0 ? 'emerald' : ($qty < 0 ? 'rose' : 'slate'),
                    'unit_cost' => round((float) ($row->unit_cost ?? 0), 2),
                    'total_cost' => $totalCost,
                    'creator_name' => $row->creator_name ?: 'System',
                    'created_by' => $row->created_by ? (int) $row->created_by : null,
                    'notes' => $row->notes ?: '-',
                ];
            })
            ->values();

        $selectedProductName = null;
        if (!empty($productId)) {
            $selectedProductName = Product::query()->whereKey($productId)->value('name');
        }

        $selectedUserName = null;
        if (!empty($userId)) {
            $selectedUserName = User::query()->whereKey($userId)->value('name');
        }

        $positiveRows = $rows->filter(fn($row) => (float) $row->quantity > 0);
        $negativeRows = $rows->filter(fn($row) => (float) $row->quantity < 0);

        $summary = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'row_count' => $rows->count(),
            'product_count' => $rows->pluck('product_id')->unique()->count(),
            'user_count' => $rows->pluck('created_by')->filter()->unique()->count(),
            'selected_product_name' => $selectedProductName,
            'selected_user_name' => $selectedUserName,
            'plus_qty' => (float) $positiveRows->sum('quantity_abs'),
            'plus_value' => (float) $positiveRows->sum('total_cost'),
            'minus_qty' => (float) $negativeRows->sum('quantity_abs'),
            'minus_value' => (float) $negativeRows->sum('total_cost'),
            'net_qty' => (float) $rows->sum('quantity'),
            'net_value' => (float) ($positiveRows->sum('total_cost') - $negativeRows->sum('total_cost')),
        ];

        return [$rows, $summary];
    }

    private function stockMovementReport(string $dateFrom, string $dateTo, ?int $outletId = null, ?int $productId = null): array
    {
        $dateFrom = \Carbon\Carbon::parse($dateFrom)->toDateString();
        $dateTo = \Carbon\Carbon::parse($dateTo)->toDateString();

        $effectiveUnitCostExpr = "CASE
            WHEN COALESCE(stock_mutations.unit_cost, 0) > 0 THEN stock_mutations.unit_cost
            WHEN COALESCE(product_costs.avg_cost, 0) > 0 THEN product_costs.avg_cost
            ELSE COALESCE(products.purchase_price, 0)
        END";

        $effectiveTotalCostExpr = "CASE
            WHEN ABS(COALESCE(stock_mutations.total_cost, 0)) > 0 THEN ABS(stock_mutations.total_cost)
            ELSE ABS(stock_mutations.quantity) * ({$effectiveUnitCostExpr})
        END";

        $signedValueExpr = "CASE
            WHEN stock_mutations.quantity >= 0 THEN {$effectiveTotalCostExpr}
            ELSE -{$effectiveTotalCostExpr}
        END";

        $inPeriod = "stock_mutations.mutation_date >= '{$dateFrom}' AND stock_mutations.mutation_date <= '{$dateTo}'";

        $purchaseInCond = "stock_mutations.reference_type = 'purchase' AND stock_mutations.quantity > 0";
        $saleReturnInCond = "stock_mutations.reference_type = 'sale_cancellation' AND stock_mutations.quantity > 0";
        $transferInCond = "(stock_mutations.mutation_type = 'transfer_in' OR (stock_mutations.reference_type = 'stock_transfer' AND stock_mutations.quantity > 0 AND stock_mutations.mutation_type <> 'adjustment'))";
        $adjustmentInCond = "stock_mutations.mutation_type = 'adjustment' AND stock_mutations.quantity > 0";

        $saleOutCond = "stock_mutations.reference_type = 'sale' AND stock_mutations.quantity < 0";
        $transferOutCond = "(stock_mutations.mutation_type = 'transfer_out' OR (stock_mutations.reference_type = 'stock_transfer' AND stock_mutations.quantity < 0 AND stock_mutations.mutation_type <> 'adjustment'))";
        $adjustmentOutCond = "stock_mutations.mutation_type = 'adjustment' AND stock_mutations.quantity < 0";

        $otherInCond = "stock_mutations.quantity > 0 AND NOT (($purchaseInCond) OR ($saleReturnInCond) OR ($transferInCond) OR ($adjustmentInCond))";
        $otherOutCond = "stock_mutations.quantity < 0 AND NOT (($saleOutCond) OR ($transferOutCond) OR ($adjustmentOutCond))";

        $query = DB::table('stock_mutations')
            ->join('products', 'stock_mutations.product_id', '=', 'products.id')
            ->join('outlets', 'stock_mutations.outlet_id', '=', 'outlets.id')
            ->leftJoin('product_costs', function ($join) {
                $join->on('product_costs.product_id', '=', 'stock_mutations.product_id')
                    ->on('product_costs.outlet_id', '=', 'stock_mutations.outlet_id');
            })
            ->whereDate('stock_mutations.mutation_date', '<=', $dateTo);

        if (!empty($outletId)) {
            $query->where('stock_mutations.outlet_id', $outletId);
        }

        if (!empty($productId)) {
            $query->where('stock_mutations.product_id', $productId);
        }

        $rows = $query
            ->select(
                'stock_mutations.product_id',
                'products.name as product_name',
                'products.sku as product_sku',
                'products.unit as product_unit',
                'stock_mutations.outlet_id',
                'outlets.name as outlet_name'
            )
            ->selectRaw('COALESCE(product_costs.avg_cost, 0) as current_avg_cost')
            ->selectRaw("SUM(CASE WHEN stock_mutations.mutation_date < '{$dateFrom}' THEN stock_mutations.quantity ELSE 0 END) as opening_qty")
            ->selectRaw("SUM(CASE WHEN stock_mutations.mutation_date < '{$dateFrom}' THEN {$signedValueExpr} ELSE 0 END) as opening_value")
            ->selectRaw("SUM(CASE WHEN {$inPeriod} AND {$purchaseInCond} THEN stock_mutations.quantity ELSE 0 END) as purchase_in_qty")
            ->selectRaw("SUM(CASE WHEN {$inPeriod} AND {$purchaseInCond} THEN {$effectiveTotalCostExpr} ELSE 0 END) as purchase_in_value")
            ->selectRaw("SUM(CASE WHEN {$inPeriod} AND {$saleReturnInCond} THEN stock_mutations.quantity ELSE 0 END) as sale_return_in_qty")
            ->selectRaw("SUM(CASE WHEN {$inPeriod} AND {$saleReturnInCond} THEN {$effectiveTotalCostExpr} ELSE 0 END) as sale_return_in_value")
            ->selectRaw("SUM(CASE WHEN {$inPeriod} AND {$transferInCond} THEN stock_mutations.quantity ELSE 0 END) as transfer_in_qty")
            ->selectRaw("SUM(CASE WHEN {$inPeriod} AND {$transferInCond} THEN {$effectiveTotalCostExpr} ELSE 0 END) as transfer_in_value")
            ->selectRaw("SUM(CASE WHEN {$inPeriod} AND {$adjustmentInCond} THEN stock_mutations.quantity ELSE 0 END) as adjustment_in_qty")
            ->selectRaw("SUM(CASE WHEN {$inPeriod} AND {$adjustmentInCond} THEN {$effectiveTotalCostExpr} ELSE 0 END) as adjustment_in_value")
            ->selectRaw("SUM(CASE WHEN {$inPeriod} AND {$saleOutCond} THEN ABS(stock_mutations.quantity) ELSE 0 END) as sale_out_qty")
            ->selectRaw("SUM(CASE WHEN {$inPeriod} AND {$saleOutCond} THEN {$effectiveTotalCostExpr} ELSE 0 END) as sale_out_value")
            ->selectRaw("SUM(CASE WHEN {$inPeriod} AND {$transferOutCond} THEN ABS(stock_mutations.quantity) ELSE 0 END) as transfer_out_qty")
            ->selectRaw("SUM(CASE WHEN {$inPeriod} AND {$transferOutCond} THEN {$effectiveTotalCostExpr} ELSE 0 END) as transfer_out_value")
            ->selectRaw("SUM(CASE WHEN {$inPeriod} AND {$adjustmentOutCond} THEN ABS(stock_mutations.quantity) ELSE 0 END) as adjustment_out_qty")
            ->selectRaw("SUM(CASE WHEN {$inPeriod} AND {$adjustmentOutCond} THEN {$effectiveTotalCostExpr} ELSE 0 END) as adjustment_out_value")
            ->selectRaw("SUM(CASE WHEN {$inPeriod} AND {$otherInCond} THEN stock_mutations.quantity ELSE 0 END) as other_in_qty")
            ->selectRaw("SUM(CASE WHEN {$inPeriod} AND {$otherInCond} THEN {$effectiveTotalCostExpr} ELSE 0 END) as other_in_value")
            ->selectRaw("SUM(CASE WHEN {$inPeriod} AND {$otherOutCond} THEN ABS(stock_mutations.quantity) ELSE 0 END) as other_out_qty")
            ->selectRaw("SUM(CASE WHEN {$inPeriod} AND {$otherOutCond} THEN {$effectiveTotalCostExpr} ELSE 0 END) as other_out_value")
            ->groupBy(
                'stock_mutations.product_id',
                'products.name',
                'products.sku',
                'products.unit',
                'stock_mutations.outlet_id',
                'outlets.name',
                'product_costs.avg_cost'
            )
            ->orderBy('products.name')
            ->orderBy('outlets.name')
            ->get()
            ->map(function ($row) {
                $toQty = static fn($value): float => round((float) ($value ?? 0), 4);
                $toMoney = static fn($value): float => round((float) ($value ?? 0), 2);

                $openingQty = $toQty($row->opening_qty);
                $openingValue = $toMoney($row->opening_value);

                $purchaseInQty = $toQty($row->purchase_in_qty);
                $purchaseInValue = $toMoney($row->purchase_in_value);
                $saleReturnInQty = $toQty($row->sale_return_in_qty);
                $saleReturnInValue = $toMoney($row->sale_return_in_value);
                $transferInQty = $toQty($row->transfer_in_qty);
                $transferInValue = $toMoney($row->transfer_in_value);
                $adjustmentInQty = $toQty($row->adjustment_in_qty);
                $adjustmentInValue = $toMoney($row->adjustment_in_value);
                $otherInQty = $toQty($row->other_in_qty);
                $otherInValue = $toMoney($row->other_in_value);

                $saleOutQty = $toQty($row->sale_out_qty);
                $saleOutValue = $toMoney($row->sale_out_value);
                $transferOutQty = $toQty($row->transfer_out_qty);
                $transferOutValue = $toMoney($row->transfer_out_value);
                $adjustmentOutQty = $toQty($row->adjustment_out_qty);
                $adjustmentOutValue = $toMoney($row->adjustment_out_value);
                $otherOutQty = $toQty($row->other_out_qty);
                $otherOutValue = $toMoney($row->other_out_value);

                $totalInQty = round($purchaseInQty + $saleReturnInQty + $transferInQty + $adjustmentInQty + $otherInQty, 4);
                $totalInValue = round($purchaseInValue + $saleReturnInValue + $transferInValue + $adjustmentInValue + $otherInValue, 2);
                $totalOutQty = round($saleOutQty + $transferOutQty + $adjustmentOutQty + $otherOutQty, 4);
                $totalOutValue = round($saleOutValue + $transferOutValue + $adjustmentOutValue + $otherOutValue, 2);
                $closingQty = round($openingQty + $totalInQty - $totalOutQty, 4);
                $closingValue = round($openingValue + $totalInValue - $totalOutValue, 2);
                $hppPenjualanKotor = $saleOutValue;
                $hppReversalVoid = $saleReturnInValue;
                $hppPenjualanBersih = round($hppPenjualanKotor - $hppReversalVoid, 2);

                return (object) [
                    'product_id' => (int) $row->product_id,
                    'product_name' => $row->product_name,
                    'product_sku' => $row->product_sku,
                    'product_unit' => $row->product_unit ?: 'pcs',
                    'outlet_id' => (int) $row->outlet_id,
                    'outlet_name' => $row->outlet_name,
                    'current_avg_cost' => round((float) ($row->current_avg_cost ?? 0), 4),
                    'opening_qty' => $openingQty,
                    'opening_value' => $openingValue,
                    'purchase_in_qty' => $purchaseInQty,
                    'purchase_in_value' => $purchaseInValue,
                    'sale_return_in_qty' => $saleReturnInQty,
                    'sale_return_in_value' => $saleReturnInValue,
                    'transfer_in_qty' => $transferInQty,
                    'transfer_in_value' => $transferInValue,
                    'adjustment_in_qty' => $adjustmentInQty,
                    'adjustment_in_value' => $adjustmentInValue,
                    'sale_out_qty' => $saleOutQty,
                    'sale_out_value' => $saleOutValue,
                    'hpp_penjualan_kotor' => $hppPenjualanKotor,
                    'hpp_reversal_void' => $hppReversalVoid,
                    'hpp_penjualan_bersih' => $hppPenjualanBersih,
                    'transfer_out_qty' => $transferOutQty,
                    'transfer_out_value' => $transferOutValue,
                    'adjustment_out_qty' => $adjustmentOutQty,
                    'adjustment_out_value' => $adjustmentOutValue,
                    'other_in_qty' => $otherInQty,
                    'other_in_value' => $otherInValue,
                    'other_out_qty' => $otherOutQty,
                    'other_out_value' => $otherOutValue,
                    'total_in_qty' => $totalInQty,
                    'total_in_value' => $totalInValue,
                    'total_out_qty' => $totalOutQty,
                    'total_out_value' => $totalOutValue,
                    'closing_qty' => $closingQty,
                    'closing_value' => $closingValue,
                ];
            });

        $summary = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'outlet_id' => $outletId,
            'product_id' => $productId,
            'selected_product_name' => $productId ? Product::query()->whereKey($productId)->value('name') : null,
            'row_count' => $rows->count(),
            'opening_qty' => (float) $rows->sum('opening_qty'),
            'opening_value' => (float) $rows->sum('opening_value'),
            'purchase_in_qty' => (float) $rows->sum('purchase_in_qty'),
            'purchase_in_value' => (float) $rows->sum('purchase_in_value'),
            'sale_return_in_qty' => (float) $rows->sum('sale_return_in_qty'),
            'sale_return_in_value' => (float) $rows->sum('sale_return_in_value'),
            'transfer_in_qty' => (float) $rows->sum('transfer_in_qty'),
            'transfer_in_value' => (float) $rows->sum('transfer_in_value'),
            'adjustment_in_qty' => (float) $rows->sum('adjustment_in_qty'),
            'adjustment_in_value' => (float) $rows->sum('adjustment_in_value'),
            'sale_out_qty' => (float) $rows->sum('sale_out_qty'),
            'sale_out_value' => (float) $rows->sum('sale_out_value'),
            'hpp_penjualan_kotor' => (float) $rows->sum('hpp_penjualan_kotor'),
            'hpp_reversal_void' => (float) $rows->sum('hpp_reversal_void'),
            'hpp_penjualan_bersih' => (float) $rows->sum('hpp_penjualan_bersih'),
            'transfer_out_qty' => (float) $rows->sum('transfer_out_qty'),
            'transfer_out_value' => (float) $rows->sum('transfer_out_value'),
            'adjustment_out_qty' => (float) $rows->sum('adjustment_out_qty'),
            'adjustment_out_value' => (float) $rows->sum('adjustment_out_value'),
            'other_in_qty' => (float) $rows->sum('other_in_qty'),
            'other_in_value' => (float) $rows->sum('other_in_value'),
            'other_out_qty' => (float) $rows->sum('other_out_qty'),
            'other_out_value' => (float) $rows->sum('other_out_value'),
            'total_in_qty' => (float) $rows->sum('total_in_qty'),
            'total_in_value' => (float) $rows->sum('total_in_value'),
            'total_out_qty' => (float) $rows->sum('total_out_qty'),
            'total_out_value' => (float) $rows->sum('total_out_value'),
            'closing_qty' => (float) $rows->sum('closing_qty'),
            'closing_value' => (float) $rows->sum('closing_value'),
        ];

        return [$rows, $summary];
    }

    private function inventoryReconciliationReport(string $dateFrom, string $dateTo, ?int $outletId = null): array
    {
        [$stockRows, $stockSummary] = $this->stockMovementReport($dateFrom, $dateTo, $outletId, null);

        $inventoryAccounts = DB::table('coa_accounts')
            ->where('is_active', true)
            ->where('type', 'asset')
            ->where(function ($q) {
                $q->whereRaw('LOWER(name) like ?', ['%persedia%'])
                    ->orWhereRaw('LOWER(name) like ?', ['%inventory%'])
                    ->orWhereRaw('LOWER(`group`) like ?', ['%persedia%'])
                    ->orWhere('code', 'like', '1-13%');
            })
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'group']);

        $inventoryAccountIds = $inventoryAccounts->pluck('id')->map(fn($id) => (int) $id)->all();

        $neracaByOutlet = collect();
        $neracaByAccount = collect();

        if (!empty($inventoryAccountIds)) {
            $neracaBase = DB::table('cash_transactions')
                ->join('coa_accounts', 'cash_transactions.coa_account_id', '=', 'coa_accounts.id')
                ->leftJoin('cash_accounts', 'cash_transactions.cash_account_id', '=', 'cash_accounts.id')
                ->whereIn('cash_transactions.coa_account_id', $inventoryAccountIds)
                ->whereDate('cash_transactions.transaction_date', '<=', $dateTo);

            if (!empty($outletId)) {
                $neracaBase->where('cash_accounts.outlet_id', $outletId);
            }

            $neracaByOutlet = (clone $neracaBase)
                ->leftJoin('outlets', 'cash_accounts.outlet_id', '=', 'outlets.id')
                ->select(
                    'cash_accounts.outlet_id',
                    DB::raw('COALESCE(outlets.name, "Tanpa Outlet") as outlet_name')
                )
                ->selectRaw("SUM(CASE WHEN cash_transactions.type = 'in' THEN cash_transactions.amount ELSE -cash_transactions.amount END) as neraca_inventory_value")
                ->groupBy('cash_accounts.outlet_id', 'outlets.name')
                ->get();

            $neracaByAccount = (clone $neracaBase)
                ->select(
                    'coa_accounts.id as coa_account_id',
                    'coa_accounts.code as coa_code',
                    'coa_accounts.name as coa_name',
                    'coa_accounts.group as coa_group'
                )
                ->selectRaw("SUM(CASE WHEN cash_transactions.type = 'in' THEN cash_transactions.amount ELSE -cash_transactions.amount END) as balance")
                ->groupBy('coa_accounts.id', 'coa_accounts.code', 'coa_accounts.name', 'coa_accounts.group')
                ->orderBy('coa_accounts.code')
                ->get();
        }

        $stockByOutlet = $stockRows
            ->groupBy(fn($row) => (string) $row->outlet_id)
            ->map(function ($group) {
                $first = $group->first();
                return (object) [
                    'outlet_key' => (string) $first->outlet_id,
                    'outlet_id' => (int) $first->outlet_id,
                    'outlet_name' => $first->outlet_name,
                    'mutation_inventory_value' => round((float) $group->sum('closing_value'), 2),
                ];
            });

        $neracaByOutletMapped = $neracaByOutlet
            ->mapWithKeys(function ($row) {
                $key = (string) ($row->outlet_id ?? 'null');
                return [$key => (object) [
                    'outlet_key' => $key,
                    'outlet_id' => $row->outlet_id !== null ? (int) $row->outlet_id : null,
                    'outlet_name' => $row->outlet_name ?: 'Tanpa Outlet',
                    'neraca_inventory_value' => round((float) ($row->neraca_inventory_value ?? 0), 2),
                ]];
            });

        $outletKeys = $stockByOutlet->keys()->merge($neracaByOutletMapped->keys())->unique()->values();
        $rows = $outletKeys->map(function ($key) use ($stockByOutlet, $neracaByOutletMapped) {
            $stock = $stockByOutlet->get($key);
            $neraca = $neracaByOutletMapped->get($key);

            $mutationValue = (float) ($stock->mutation_inventory_value ?? 0);
            $neracaValue = (float) ($neraca->neraca_inventory_value ?? 0);
            $gap = round($mutationValue - $neracaValue, 2);

            return (object) [
                'outlet_id' => $stock->outlet_id ?? $neraca->outlet_id ?? null,
                'outlet_name' => $stock->outlet_name ?? $neraca->outlet_name ?? 'Tanpa Outlet',
                'mutation_inventory_value' => $mutationValue,
                'neraca_inventory_value' => $neracaValue,
                'gap_value' => $gap,
                'is_balanced' => abs($gap) < 0.01,
            ];
        })->sortBy('outlet_name')->values();

        $mutationTotal = round((float) ($stockSummary['closing_value'] ?? 0), 2);
        $neracaTotal = round((float) $rows->sum('neraca_inventory_value'), 2);
        $gapTotal = round($mutationTotal - $neracaTotal, 2);

        $summary = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'outlet_id' => $outletId,
            'inventory_mutation_value' => $mutationTotal,
            'inventory_neraca_value' => $neracaTotal,
            'gap_value' => $gapTotal,
            'is_balanced' => abs($gapTotal) < 0.01,
            'inventory_account_count' => count($inventoryAccountIds),
            'stock_row_count' => (int) ($stockSummary['row_count'] ?? 0),
            'outlet_row_count' => (int) $rows->count(),
            'inventory_accounts_rows' => $neracaByAccount->map(function ($row) {
                return [
                    'coa_account_id' => (int) $row->coa_account_id,
                    'coa_code' => $row->coa_code,
                    'coa_name' => $row->coa_name,
                    'coa_group' => $row->coa_group,
                    'balance' => round((float) ($row->balance ?? 0), 2),
                ];
            })->all(),
        ];

        $meta = [
            'notes' => [
                'Nilai Mutasi Persediaan dihitung dari nilai stok akhir (closing value) per produk-outlet pada cut-off tanggal.',
                'Nilai Persediaan Neraca diambil dari saldo akun COA aset yang terindikasi persediaan (nama/group mengandung persedia/inventory atau kode 1-13x).',
                'Selisih harus 0 untuk syarat rekonsiliasi bulanan; jika tidak 0, lakukan audit transaksi persediaan dan posting COA.',
            ],
        ];

        if (empty($inventoryAccountIds)) {
            $meta['notes'][] = 'Belum ada akun COA persediaan aktif terdeteksi. Buat/aktifkan akun seperti 1-130 Persediaan agar rekonsiliasi neraca dapat berjalan.';
        }

        return [$rows, $summary, $meta];
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

    private function resolveCatalogOutletIds(
        Request $request,
        Collection $availableOutletIds,
        string $arrayKey = 'outlet_ids',
        string $singleKey = 'outlet_id'
    ): array
    {
        $allowedOutletIds = $availableOutletIds
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($request->has($arrayKey)) {
            return collect($request->input($arrayKey, []))
                ->filter(fn ($id) => $id !== null && $id !== '')
                ->map(fn ($id) => is_numeric($id) ? (int) $id : null)
                ->filter(fn ($id) => is_int($id) && in_array($id, $allowedOutletIds, true))
                ->unique()
                ->values()
                ->all();
        }

        if ($request->filled($singleKey) && is_numeric($request->input($singleKey))) {
            $outletId = (int) $request->input($singleKey);

            return in_array($outletId, $allowedOutletIds, true)
                ? [$outletId]
                : [];
        }

        return [];
    }
}
