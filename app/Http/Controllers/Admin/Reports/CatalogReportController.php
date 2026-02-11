<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Services\BalanceSheetReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatalogReportController extends Controller
{
    public function __construct(
        private readonly BalanceSheetReportService $balanceSheetReportService
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
                    'waiter-performance',
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
            'profit-loss' => ['title' => 'Laba & Rugi', 'implemented' => false, 'existing_route' => 'admin.reports.profit-loss.index'],
            'cash-bank' => ['title' => 'Kas dan Bank', 'implemented' => true],
            'cash-bank-detail' => ['title' => 'Kas dan Bank Detil', 'implemented' => true],
            'ledger-detail' => ['title' => 'Detil Ledger', 'implemented' => true],
            'cash-flow' => ['title' => 'Arus Kas', 'implemented' => true],
            'sales-summary' => ['title' => 'Ringkasan Penjualan', 'implemented' => false],
            'sales' => ['title' => 'Penjualan', 'implemented' => false],
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

        $report = $reports[$slug];
        $dateFrom = $request->input('date_from', now()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
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
