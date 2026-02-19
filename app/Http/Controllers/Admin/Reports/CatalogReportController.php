<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\Product;
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
            'sales-by-product' => ['title' => 'Penjualan per Produk', 'implemented' => true, 'existing_route' => 'admin.reports.sales.products'],
            'sales-by-type' => ['title' => 'Penjualan per Tipe Penjualan', 'implemented' => false],
            'sales-by-payment-method' => ['title' => 'Penjualan per Metode Pembayaran', 'implemented' => true],
            'sales-by-category' => ['title' => 'Penjualan per Kategori Produk', 'implemented' => false],
            'sales-by-hour' => ['title' => 'Penjualan per Jam', 'implemented' => false],
            'cancelled-sales' => ['title' => 'Penjualan yang Dibatalkan', 'implemented' => false],
            'sales-stock-out' => ['title' => 'Stok Keluar dari Penjualan', 'implemented' => false],
            'sales-modifier' => ['title' => 'Penjualan Modifier', 'implemented' => false],
            'sales-vs-hpp' => ['title' => 'Penjualan Vs HPP', 'implemented' => true],
            'waiter-performance' => ['title' => 'Kinerja Pelayan Berdasarkan Penjualan', 'implemented' => false],
            'sales-discount' => ['title' => 'Laporan Diskon Penjualan', 'implemented' => true],
            'shift-sessions' => ['title' => 'Sesi Shift POS', 'implemented' => true, 'existing_route' => 'admin.reports.shifts.index'],
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
            'stock-movement' => ['title' => 'Pergerakan Stok Produk', 'implemented' => true],
            'top-products' => ['title' => 'Produk Terlaris', 'implemented' => false],
            'low-selling-products' => ['title' => 'Produk Kurang Laku', 'implemented' => false],
            'inventory-value' => ['title' => 'Nilai Persediaan', 'implemented' => true],
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

        if ($slug === 'sales-by-product') {
            return redirect()->route('admin.reports.sales.products', array_filter([
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
                'outlet_id' => $request->input('outlet_id'),
                'user_id' => $request->input('user_id'),
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

        $report = $reports[$slug];
        $financeSlugs = ['balance-sheet', 'profit-loss', 'cash-bank', 'cash-bank-detail', 'ledger-detail', 'cash-flow', 'sales-summary', 'stock-movement', 'inventory-value'];
        $defaultDateFrom = in_array($slug, $financeSlugs, true) ? now()->startOfMonth()->toDateString() : now()->toDateString();
        $defaultDateTo = in_array($slug, $financeSlugs, true) ? now()->endOfMonth()->toDateString() : now()->toDateString();

        $dateFrom = $request->input('date_from', $defaultDateFrom);
        $dateTo = $request->input('date_to', $defaultDateTo);
        $outletId = $request->input('outlet_id');
        $outlets = Outlet::where('is_active', true)->orderBy('name')->get();
        $selectedProductId = $request->input('product_id');
        $products = collect();
        if ($slug === 'stock-movement') {
            $products = Product::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'sku']);
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

        if ($slug === 'stock-movement') {
            $viewType = 'stock-movement';
            $selectedProductId = $request->filled('product_id') ? (int) $request->input('product_id') : null;

            [$rows, $summary] = $this->stockMovementReport(
                dateFrom: $dateFrom,
                dateTo: $dateTo,
                outletId: !empty($outletId) ? (int) $outletId : null,
                productId: $selectedProductId,
            );

            $meta = [
                'notes' => [
                    'Nominal mutasi memakai snapshot total_cost; jika kosong akan fallback ke avg_cost outlet atau purchase_price produk.',
                    'Kolom Penjualan Keluar (Nominal) dapat dipakai untuk rekonsiliasi HPP dengan laporan Laba Rugi pada filter tanggal/outlet yang sama.',
                    'Metrik HPP eksplisit: hpp_penjualan_kotor (out sale), hpp_reversal_void (in sale_cancellation), hpp_penjualan_bersih (kotor - reversal).',
                ],
            ];
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
            $viewMode = $request->input('view_mode', 'summary'); // summary | detail
            $filterType = $request->input('filter_type', 'all'); // all | compliment | meal_karyawan

            $salesQuery = \App\Models\Sale::query()
                ->with(['items.product', 'outlet', 'user', 'customer'])
                ->where('status', 'completed')
                ->whereBetween('sale_date', [$dateFrom, $dateTo]);

            if (!empty($outletId)) {
                $salesQuery->where('outlet_id', $outletId);
            }

            // Optional: Filter by specific sales types if needed, but report typically shows all "discounted" transactions
            if ($filterType !== 'all') {
                $salesQuery->where('sales_type', $filterType);
            }

            $allSales = $salesQuery->orderByDesc('sale_date')->orderByDesc('created_at')->get();

            // Process data to calculate "Real Value" vs "Paid Value"
            $processedData = $allSales->map(function ($sale) {
                $grossValue = 0;
                $totalDiscount = 0;

                foreach ($sale->items as $item) {
                    // Estimate normal price from product master if item price is 0 (compliment)
                    // Or use item subtotal + item discount if available
                    // Use product selling price as baseline for value
                    $normalPrice = $item->product ? (float) $item->product->selling_price : (float) $item->unit_price;

                    // If unit_price is 0, we assume it's a full discount/compliment
                    // If unit_price > 0, we trust it, but check if discount_amount exists

                    // Logic: Value = Quantity * Normal Price (Highest logic)
                    // But product price might have changed.
                    // Fallback: If unit_price > 0, Value = (Unit Price * Qty). If Unit Price == 0, Value = (Product Price * Qty).

                    $itemPrice = (float) $item->unit_price;
                    $isSpecialType = in_array($sale->sales_type, ['compliment', 'meal_karyawan']);

                    // If it's a special type or price is 0, use master price to show full value
                    if (($isSpecialType || $itemPrice == 0) && $item->product) {
                        $itemGross = ($item->quantity * $item->product->selling_price);
                    } else {
                        // Regular sales: respect historical price snapshot (subtotal + discount)
                        $itemGross = ((float) $item->subtotal) + ((float) $item->discount_amount);
                    }

                    $paidAmount = (float) $item->subtotal;
                    // Safety check
                    if ($itemGross < $paidAmount) {
                        $itemGross = $paidAmount;
                    }

                    $grossValue += $itemGross;
                }

                // Add global discount from header if any (already deducted from items? No, SaleService splits it? No, SaleService discount_amount is global)
                // SaleService structure:
                // Subtotal (sum of item subtotals)
                // Discount Amount (Global)
                // Tax Base = Subtotal - Discount Amount

                // So Total Discount = (Sum of (Item Value - Item Paid)) + Global Discount Amount
                // Item Paid = Item Subtotal.

                $itemsPaid = $sale->items->sum('subtotal');
                $itemsPotentialValue = $grossValue;
                $itemLevelDiscount = $itemsPotentialValue - $itemsPaid;

                $totalDiscount = $itemLevelDiscount + (float) $sale->discount_amount;

                return [
                    'id' => $sale->id,
                    'invoice_number' => $sale->invoice_number,
                    'sale_date' => $sale->sale_date->format('Y-m-d'),
                    'outlet_name' => $sale->outlet->name ?? '-',
                    'sales_type' => $sale->sales_type ?? 'regular', // compliment, meal_karyawan, etc
                    'customer_name' => $sale->customer_name ?? '-',
                    'gross_value' => $itemsPotentialValue, // Nilai seharusnya (Normal Price)
                    'net_sales' => (float) $sale->total_amount, // Yang dibayar
                    'total_discount' => $totalDiscount, // Selisih
                    'notes' => $sale->notes,
                    'items_count' => $sale->items->count(),
                ];
            });

            // Filter only transactions with discount or specific types
            $filteredData = $processedData->filter(function ($row) use ($filterType) {
                if ($filterType !== 'all') {
                    return $row['sales_type'] === $filterType;
                }
                // Show if there is discount OR sales_type is special
                return $row['total_discount'] > 0 || in_array($row['sales_type'], ['compliment', 'meal_karyawan']);
            });

            if ($viewMode === 'summary') {
                $rows = $filteredData->groupBy('sales_type')->map(function ($group, $type) {
                    return [
                        'sales_type' => $type,
                        'transaction_count' => $group->count(),
                        'gross_value' => $group->sum('gross_value'),
                        'net_sales' => $group->sum('net_sales'),
                        'total_discount' => $group->sum('total_discount'),
                    ];
                })->values();
            } else {
                $rows = $filteredData->values();
            }

            $summary = [
                'total_transactions' => $filteredData->count(),
                'total_gross_value' => $filteredData->sum('gross_value'),
                'total_net_sales' => $filteredData->sum('net_sales'),
                'total_discount' => $filteredData->sum('total_discount'),
                'view_mode' => $viewMode,
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
            'selectedProductId' => $selectedProductId,
            'outlets' => $outlets,
            'products' => $products,
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
            ], $rowsCollection->map(fn($row) => (array) $row)->all()];
        }

        if ($viewType === 'sales-discount') {
            return [[
                ['key' => 'sales_type', 'label' => 'Tipe Penjualan', 'type' => 'text'],
                ['key' => 'invoice_number', 'label' => 'No Transaksi', 'type' => 'text'],
                ['key' => 'sale_date', 'label' => 'Tanggal', 'type' => 'text'],
                ['key' => 'outlet_name', 'label' => 'Outlet', 'type' => 'text'],
                ['key' => 'gross_value', 'label' => 'Nilai Jual (Gross)', 'type' => 'number', 'decimals' => 2],
                ['key' => 'total_discount', 'label' => 'Total Diskon', 'type' => 'number', 'decimals' => 2],
                ['key' => 'net_sales', 'label' => 'Total Bayar', 'type' => 'number', 'decimals' => 2],
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
}
