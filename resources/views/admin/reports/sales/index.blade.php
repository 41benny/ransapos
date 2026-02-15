@extends('layouts.admin')

@section('title', 'Laporan Penjualan')
@section('page-title', 'Laporan Penjualan')
@section('page-subtitle', 'Ringkasan dan detail transaksi penjualan')

@section('content')
<div class="mb-4 no-print flex justify-end">
    <a href="{{ route('admin.reports.index') }}"
        class="inline-flex h-10 items-center rounded-lg border border-slate-300 px-4 text-sm font-medium text-slate-700 hover:bg-slate-50">
        Kembali ke Katalog
    </a>
</div>
<div class="bg-white rounded-xl shadow-sm border border-gray-100">

    <!-- Filter Section -->
    <div class="p-6 border-b border-gray-100 no-print">
        <form method="GET" action="{{ route('admin.reports.sales.index') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4">

            <!-- Date From -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <!-- Date To -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                <input type="date" name="date_to" value="{{ $dateTo }}" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <!-- Outlet -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Outlet</label>
                <select name="outlet_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Semua Outlet</option>
                    @foreach($outlets as $outlet)
                    <option value="{{ $outlet->id }}" {{ ($filters['outlet_id'] ?? '') == $outlet->id ? 'selected' : '' }}>
                        {{ $outlet->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- Kasir -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kasir</label>
                <select name="user_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Semua Kasir</option>
                    @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ ($filters['user_id'] ?? '') == $user->id ? 'selected' : '' }}>
                        {{ $user->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- Payment Method -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Metode Pembayaran</label>
                <select name="payment_method_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Semua Metode</option>
                    @foreach($paymentMethods as $method)
                    <option value="{{ $method->id }}" {{ ($filters['payment_method_id'] ?? '') == $method->id ? 'selected' : '' }}>
                        {{ $method->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- Mode View -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tampilan</label>
                <select name="view_mode" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="ringkas" {{ ($viewMode ?? 'ringkas') === 'ringkas' ? 'selected' : '' }}>Ringkas</option>
                    <option value="detail" {{ ($viewMode ?? 'ringkas') === 'detail' ? 'selected' : '' }}>Detil</option>
                </select>
            </div>

            <!-- Actions -->
            <div class="md:col-span-6 flex space-x-2">
                <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-emerald-500 via-teal-500 to-sky-500 text-white rounded-full transition shadow-md hover:shadow-lg">
                    <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    Filter
                </button>
                <a href="{{ route('admin.reports.sales.index') }}" class="px-5 py-2.5 bg-white border border-amber-300 hover:bg-amber-50 text-amber-900 rounded-full transition">
                    Reset
                </a>
                <button type="button" onclick="window.print()" class="ml-auto px-5 py-2.5 bg-gradient-to-r from-emerald-500 via-teal-500 to-sky-500 text-white rounded-full transition shadow-md hover:shadow-lg">
                    <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Print
                </button>
            </div>
        </form>
    </div>

    <!-- Report Header (Print) -->
    <div class="p-6 border-b border-gray-100 print-only hidden">
        <div class="text-center mb-4">
            <h1 class="text-2xl font-bold text-gray-900">LAPORAN PENJUALAN</h1>
            <p class="text-gray-600 mt-1">Periode: {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</p>
            <p class="text-sm text-gray-500">Dicetak: {{ now()->format('d M Y, H:i') }}</p>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="p-6 border-b border-gray-100">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">

            <!-- Total Transaksi -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <p class="text-sm text-blue-600 font-medium mb-1">Total Transaksi</p>
                <p class="text-3xl font-bold text-blue-900">{{ $summary['total_transactions'] }}</p>
            </div>

            <!-- Total Omzet -->
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <p class="text-sm text-green-600 font-medium mb-1">Total Omzet</p>
                <p class="text-3xl font-bold text-green-900">Rp {{ number_format($summary['total_amount'], 0, ',', '.') }}</p>
            </div>

            <!-- Total Pembulatan -->
            <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                <p class="text-sm text-slate-600 font-medium mb-1">Total Pembulatan</p>
                <p class="text-2xl font-bold text-slate-900">
                    {{ $summary['total_rounding'] >= 0 ? '+' : '-' }}Rp {{ number_format(abs($summary['total_rounding']), 2, ',', '.') }}
                </p>
                <p class="text-xs text-slate-600 mt-1">
                    Sebelum bulat: Rp {{ number_format($summary['total_before_rounding'], 2, ',', '.') }}
                </p>
            </div>

            <!-- Rata-rata per Transaksi -->
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                <p class="text-sm text-purple-600 font-medium mb-1">Rata-rata Transaksi</p>
                <p class="text-3xl font-bold text-purple-900">Rp {{ number_format($summary['avg_per_transaction'], 0, ',', '.') }}</p>
            </div>

            <!-- Cash vs Non-Cash -->
            <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                <p class="text-sm text-orange-600 font-medium mb-1">Cash vs Non-Cash</p>
                <p class="text-lg font-bold text-orange-900">
                    Rp {{ number_format($summary['total_cash'], 0, ',', '.') }}
                </p>
                <p class="text-sm text-orange-700">
                    Non-Cash: Rp {{ number_format($summary['total_non_cash'], 0, ',', '.') }}
                </p>
            </div>
        </div>
    </div>

    <!-- Detail Transactions -->
    <div class="overflow-x-auto">
        <table class="imperial-table w-full">
            <thead>
                @if(($viewMode ?? 'ringkas') === 'detail')
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">No Transaksi</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Outlet</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Pelanggan</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Produk</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Qty</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Harga</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Diskon per item</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Subtotal</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Pajak</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Service Charge</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Pembulatan</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status Pembayaran</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Metode Pembayaran</th>
                    </tr>
                @else
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Tanggal & Jam</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Invoice</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Outlet</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Kasir</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Pembayaran</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Pembulatan</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Total</th>
                    </tr>
                @endif
            </thead>
            <tbody>
                @if(($viewMode ?? 'ringkas') === 'detail')
                    @forelse($detailRows as $row)
                    <tr>
                        <td class="px-6 py-3 whitespace-nowrap text-sm font-mono text-gray-900">{{ $row->transaction_number }}</td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900">{{ \Carbon\Carbon::parse($row->sale_date)->format('d M Y') }}</td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">{{ $row->outlet_name }}</td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">{{ $row->customer_name ?: '-' }}</td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900">{{ $row->product_name }}</td>
                        <td class="px-6 py-3 whitespace-nowrap text-right text-sm text-gray-900">{{ number_format($row->qty, 2, ',', '.') }}</td>
                        <td class="px-6 py-3 whitespace-nowrap text-right text-sm text-gray-900">Rp {{ number_format($row->price, 0, ',', '.') }}</td>
                        <td class="px-6 py-3 whitespace-nowrap text-right text-sm text-rose-700">Rp {{ number_format($row->item_discount, 0, ',', '.') }}</td>
                        <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-semibold text-gray-900">Rp {{ number_format($row->item_subtotal, 0, ',', '.') }}</td>
                        <td class="px-6 py-3 whitespace-nowrap text-right text-sm text-gray-900">Rp {{ number_format($row->tax_amount, 0, ',', '.') }}</td>
                        <td class="px-6 py-3 whitespace-nowrap text-right text-sm text-gray-900">Rp {{ number_format($row->service_charge_amount, 0, ',', '.') }}</td>
                        <td class="px-6 py-3 whitespace-nowrap text-right text-sm text-gray-900">
                            {{ $row->rounding_amount >= 0 ? '+' : '-' }}Rp {{ number_format(abs($row->rounding_amount), 2, ',', '.') }}
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-semibold text-gray-900">Rp {{ number_format($row->total_amount, 0, ',', '.') }}</td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm">
                            @if($row->payment_status === 'Lunas')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Lunas</span>
                            @elseif($row->payment_status === 'Parsial')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-800">Parsial</span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-700">Belum Bayar</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">{{ $row->payment_methods }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="15" class="px-6 py-12 text-center">
                            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p class="text-gray-500">Tidak ada detail penjualan pada periode ini</p>
                        </td>
                    </tr>
                    @endforelse
                @else
                    @forelse($sales as $sale)
                    <tr>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900">
                            {{ $sale->created_at->format('d M Y, H:i') }}
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm font-mono text-gray-900">
                            {{ $sale->invoice_number }}
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">
                            {{ $sale->outlet->name }}
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">
                            {{ $sale->user->name }}
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">
                            {{ $sale->payments->first()->paymentMethod->name ?? '-' }}
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap text-right text-sm text-gray-900">
                            {{ (float) $sale->rounding_amount >= 0 ? '+' : '-' }}Rp {{ number_format(abs((float) $sale->rounding_amount), 2, ',', '.') }}
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-semibold text-gray-900">
                            Rp {{ number_format($sale->total_amount, 0, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p class="text-gray-500">Tidak ada transaksi pada periode ini</p>
                        </td>
                    </tr>
                    @endforelse
                @endif
            </tbody>
            @if(($viewMode ?? 'ringkas') !== 'detail' && $sales->count() > 0)
            <tfoot>
                <tr>
                    <td colspan="6" class="px-6 py-4 text-right text-sm font-bold text-gray-900">
                        TOTAL ({{ $summary['total_transactions'] }} transaksi):
                    </td>
                    <td class="px-6 py-4 text-right text-lg font-bold text-amber-700">
                        Rp {{ number_format($summary['total_amount'], 0, ',', '.') }}
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
    .bg-gray-50 {
        background: white !important;
    }
    aside {
        display: none !important;
    }
    header {
        display: none !important;
    }
    main {
        padding: 0 !important;
    }
}
</style>
@endsection
