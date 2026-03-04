@extends('layouts.admin')

@section('title', 'Detail Shift Kasir')
@section('page-title', 'Detail Shift Kasir')
@section('page-subtitle', $cashSession->session_number)

@section('content')

<!-- Back Button -->
<div class="mb-4 no-print">
    <a href="{{ route('admin.reports.shifts.index', ['tab' => request('tab', 'penjualan')]) }}"
       class="ui-btn ui-btn-ghost inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50"
       aria-label="Kembali ke Index Shift"
       title="Kembali ke Index Shift">
        <i class="fas fa-arrow-left text-sm"></i>
    </a>
</div>

<!-- Print Header -->
<div class="ui-card bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6 print-header">
    <div class="text-center print-only hidden mb-6">
        <h1 class="text-2xl font-normal text-gray-900">DETAIL SHIFT KASIR</h1>
        <p class="text-gray-600 mt-1">{{ $cashSession->session_number }}</p>
        <p class="text-sm text-gray-500">Dicetak: {{ now()->format('d M Y, H:i') }}</p>
    </div>

    <!-- Session Info -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div>
            <h3 class="text-lg font-normal text-gray-900 mb-4">Informasi Shift</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Session Number:</span>
                    <span class="text-sm font-mono font-normal text-gray-900">{{ $cashSession->session_number }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Outlet:</span>
                    <span class="text-sm font-normal text-gray-900">{{ $cashSession->outlet->name }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Kasir:</span>
                    <span class="text-sm font-normal text-gray-900">{{ $cashSession->user->name }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Dibuka:</span>
                    <span class="text-sm font-normal text-gray-900">{{ $cashSession->opened_at->format('d M Y, H:i') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Ditutup:</span>
                    <span class="text-sm font-normal text-gray-900">
                        {{ $cashSession->closed_at ? $cashSession->closed_at->format('d M Y, H:i') : 'Masih Open' }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Status:</span>
                    @if($cashSession->status === 'open')
                        <span class="px-3 py-1 text-xs font-normal bg-green-100 text-green-800 rounded-full">Open</span>
                    @else
                        <span class="px-3 py-1 text-xs font-normal bg-gray-100 text-gray-800 rounded-full">Closed</span>
                    @endif
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Perangkat Buka:</span>
                    <span class="text-sm font-normal text-gray-900">{{ $sessionDiagnostics['opened_device_name'] ?? '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Perangkat Tutup:</span>
                    <span class="text-sm font-normal text-gray-900">{{ $sessionDiagnostics['closed_device_name'] ?? '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">IP Buka/Tutup:</span>
                    <span class="text-sm font-normal text-gray-900">
                        {{ $cashSession->opened_ip ?? '-' }} / {{ $cashSession->closed_ip ?? '-' }}
                    </span>
                </div>
            </div>
        </div>

        <div>
            <h3 class="text-lg font-normal text-gray-900 mb-4">Perhitungan Kas</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Saldo Awal:</span>
                    <span class="text-sm font-normal text-gray-900">Rp {{ number_format($cashSession->opening_balance, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Total Cash:</span>
                    <span class="text-sm font-normal text-green-600">+ Rp {{ number_format($cashSession->total_cash, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Total Non-Cash:</span>
                    <span class="text-sm font-normal text-blue-600">Rp {{ number_format($cashSession->total_non_cash, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between pt-3 border-t border-gray-200">
                    <span class="text-sm font-normal text-gray-600">Kas yang Seharusnya:</span>
                    <span class="text-sm font-normal text-gray-900">Rp {{ number_format($cashSession->expected_balance, 0, ',', '.') }}</span>
                </div>
                @if($cashSession->status === 'closed')
                <div class="flex justify-between">
                    <span class="text-sm font-normal text-gray-600">Kas Fisik Aktual:</span>
                    <span class="text-sm font-normal text-gray-900">Rp {{ number_format($cashSession->actual_balance, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between pt-3 border-t-2 border-gray-300">
                    <span class="text-base font-normal text-gray-900">Selisih:</span>
                    @if($cashSession->difference > 0)
                        <span class="text-lg font-normal text-green-600">+ Rp {{ number_format($cashSession->difference, 0, ',', '.') }}</span>
                    @elseif($cashSession->difference < 0)
                        <span class="text-lg font-normal text-red-600">- Rp {{ number_format(abs($cashSession->difference), 0, ',', '.') }}</span>
                    @else
                        <span class="text-lg font-normal text-gray-600">Rp 0</span>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Summary Box -->
<div class="ui-card bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
    <h3 class="text-lg font-normal text-gray-900 mb-4">Ringkasan Penjualan</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-gray-50 rounded-lg p-4">
            <p class="text-sm text-gray-600 mb-1">Total Transaksi</p>
            <p class="text-2xl font-normal text-gray-900">{{ $cashSession->sales->count() }}</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-4">
            <p class="text-sm text-gray-600 mb-1">Total Omzet</p>
            <p class="text-2xl font-normal text-gray-900">Rp {{ number_format($cashSession->total_sales, 0, ',', '.') }}</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-4">
            <p class="text-sm text-gray-600 mb-1">Rata-rata Transaksi</p>
            <p class="text-2xl font-normal text-gray-900">
                Rp {{ $cashSession->sales->count() > 0 ? number_format($cashSession->total_sales / $cashSession->sales->count(), 0, ',', '.') : 0 }}
            </p>
        </div>
    </div>
</div>

<!-- Diagnostics Box -->
<div class="ui-card bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
    <h3 class="text-lg font-normal text-gray-900 mb-4">Diagnostics Shift</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-slate-50 rounded-lg p-4">
            <p class="text-sm text-gray-600 mb-1">Rentang Tanggal Transaksi</p>
            <p class="text-base font-normal text-gray-900">{{ $sessionDiagnostics['sale_date_range_label'] ?? '-' }}</p>
        </div>
        <div class="bg-slate-50 rounded-lg p-4">
            <p class="text-sm text-gray-600 mb-1">Durasi Shift</p>
            <p class="text-base font-normal text-gray-900">
                {{ ($sessionDiagnostics['duration_minutes'] ?? null) !== null ? $sessionDiagnostics['duration_minutes'] . ' menit' : '-' }}
            </p>
        </div>
        <div class="bg-slate-50 rounded-lg p-4">
            <p class="text-sm text-gray-600 mb-1">Delta Total Session vs Sum Sales</p>
            <p class="text-base font-normal {{ ($sessionDiagnostics['delta_total_vs_sum'] ?? 0) == 0.0 ? 'text-gray-900' : 'text-amber-700' }}">
                Rp {{ number_format($sessionDiagnostics['delta_total_vs_sum'] ?? 0, 0, ',', '.') }}
            </p>
        </div>
    </div>

    @if(!empty($sessionDiagnostics['device_mismatch']))
        <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            Buka/tutup shift terdeteksi menggunakan perangkat berbeda.
            Ini bukan error otomatis, tetapi perlu verifikasi operasional.
        </div>
    @endif
</div>

<!-- Payment Breakdown -->
@if($paymentBreakdown->count() > 0)
<div class="ui-card bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
    <h3 class="text-lg font-normal text-gray-900 mb-4">Breakdown Pembayaran</h3>
    <div class="grid grid-cols-1 md:grid-cols-{{ min($paymentBreakdown->count(), 4) }} gap-4">
        @foreach($paymentBreakdown as $breakdown)
        <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
            <p class="text-sm text-indigo-600 font-normal mb-1">{{ $breakdown->method_name }}</p>
            <p class="text-xl font-normal text-indigo-900">Rp {{ number_format($breakdown->total_amount, 0, ',', '.') }}</p>
            <p class="text-xs text-indigo-700 mt-1">{{ $breakdown->transaction_count }} transaksi</p>
        </div>
        @endforeach
    </div>
</div>
@endif

<!-- Top Products -->
@if($topProducts->count() > 0)
<div class="ui-card bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
    <h3 class="text-lg font-normal text-gray-900 mb-4">Top 10 Produk di Shift Ini</h3>
    <div class="overflow-x-auto">
        <table class="ui-table imperial-table w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-normal text-gray-600 uppercase">Produk</th>
                    <th class="px-4 py-3 text-left text-xs font-normal text-gray-600 uppercase">SKU</th>
                    <th class="px-4 py-3 text-right text-xs font-normal text-gray-600 uppercase">Total Qty</th>
                    <th class="px-4 py-3 text-right text-xs font-normal text-gray-600 uppercase">Total Omzet</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($topProducts as $product)
                <tr>
                    <td class="px-4 py-3 text-sm text-gray-900">{{ $product->product_name }}</td>
                    <td class="px-4 py-3 text-sm font-mono text-gray-600">{{ $product->product_sku }}</td>
                    <td class="px-4 py-3 text-right text-sm font-normal text-gray-900">{{ number_format($product->total_qty, 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right text-sm font-normal text-gray-900">Rp {{ number_format($product->total_amount, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Transactions List -->
<div class="ui-card bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="p-6 border-b border-gray-100">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-normal text-gray-900">Daftar Transaksi</h3>
            <div class="no-print flex items-center gap-2">
                <a href="{{ route('admin.reports.shifts.show.export', ['cashSession' => $cashSession, 'format' => 'xlsx']) }}"
                    class="ui-btn ui-btn-ghost px-4 py-2 bg-emerald-50 border border-emerald-200 hover:bg-emerald-100 text-emerald-700 rounded-lg transition">
                    Export Excel
                </a>
                <a href="{{ route('admin.reports.shifts.show.export', ['cashSession' => $cashSession, 'format' => 'pdf']) }}"
                    class="ui-btn ui-btn-ghost px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-lg transition">
                    Export PDF
                </a>
                <button type="button" onclick="window.print()" class="ui-btn ui-btn-primary px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                    <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Print
                </button>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="ui-table imperial-table w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-normal text-gray-600 uppercase">Waktu</th>
                    <th class="px-6 py-3 text-left text-xs font-normal text-gray-600 uppercase">Invoice</th>
                    <th class="px-6 py-3 text-left text-xs font-normal text-gray-600 uppercase">Pelanggan</th>
                    <th class="px-6 py-3 text-left text-xs font-normal text-gray-600 uppercase">Metode Pembayaran</th>
                    <th class="px-6 py-3 text-right text-xs font-normal text-gray-600 uppercase">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($cashSession->sales as $sale)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900">
                        {{ $sale->created_at->format('H:i:s') }}
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap text-sm font-mono text-gray-900">
                        {{ $sale->invoice_number }}
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">
                        {{ $sale->customer_name ?? 'Walk-in' }}
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">
                        {{ $sale->payments->first()->paymentMethod->name ?? '-' }}
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-normal text-gray-900">
                        Rp {{ number_format($sale->total_amount, 0, ',', '.') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                        Tidak ada transaksi di shift ini
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if($cashSession->sales->count() > 0)
            <tfoot class="bg-gray-50 border-t-2 border-gray-300">
                <tr>
                    <td colspan="4" class="px-6 py-4 text-right text-sm font-normal text-gray-900">
                        TOTAL:
                    </td>
                    <td class="px-6 py-4 text-right text-lg font-normal text-indigo-600">
                        Rp {{ number_format($cashSession->total_sales, 0, ',', '.') }}
                    </td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

<!-- Print CSS -->
<style>
@media print {
    .no-print {
        display: none !important;
    }
    .print-only {
        display: block !important;
    }
    body {
        background: white;
    }
    aside, header {
        display: none !important;
    }
    main {
        padding: 20px !important;
    }
    .rounded-xl {
        border-radius: 0 !important;
    }
    .shadow-sm {
        box-shadow: none !important;
    }
}
</style>
@endsection
