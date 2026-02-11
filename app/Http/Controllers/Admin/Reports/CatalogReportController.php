<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatalogReportController extends Controller
{
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
                    'sales-summary',
                    'sales-daily-summary',
                    'daily-outlet-summary',
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

        // Implementasi awal: Penjualan per Metode Pembayaran
        if ($slug === 'sales-by-payment-method') {
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
        ]);
    }
}
