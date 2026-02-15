@extends('layouts.admin')

@section('title', 'Laporan Penjualan per Produk')
@section('page-title', 'Laporan Penjualan per Produk')
@section('page-subtitle', 'Analisis penjualan berdasarkan produk')

@section('content')
<div class="mb-4 no-print flex justify-end">
    <a href="{{ route('admin.reports.index', ['tab' => request('tab', 'penjualan')]) }}"
        class="inline-flex h-10 items-center rounded-lg border border-slate-300 px-4 text-sm font-medium text-slate-700 hover:bg-slate-50">
        Kembali ke Katalog
    </a>
</div>
<div class="bg-white rounded-xl shadow-sm border border-gray-100">

    <!-- Filter Section -->
    <div class="p-6 border-b border-gray-100 no-print">
        <form method="GET" action="{{ route('admin.reports.sales.products') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="hidden" name="tab" value="{{ request('tab', 'penjualan') }}">

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

            <!-- Actions -->
            <div class="md:col-span-4 flex space-x-2">
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition">
                    <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    Filter
                </button>
                <a href="{{ route('admin.reports.sales.products', ['tab' => request('tab', 'penjualan')]) }}" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition">
                    Reset
                </a>
                <button type="button" onclick="window.print()" class="ml-auto px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                    <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Print
                </button>
            </div>
        </form>
    </div>

    <!-- Report Header (Print) -->
    <div class="p-6 border-b border-gray-100 print-only hidden">
        <div class="text-center mb-4">
            <h1 class="text-2xl font-bold text-gray-900">LAPORAN PENJUALAN PER PRODUK</h1>
            <p class="text-gray-600 mt-1">Periode: {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</p>
            <p class="text-sm text-gray-500">Dicetak: {{ now()->format('d M Y, H:i') }}</p>
        </div>
    </div>

    <!-- Summary Info -->
    <div class="p-6 border-b border-gray-100">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
                <p class="text-sm text-indigo-600 font-medium mb-1">Total Item Terjual</p>
                <p class="text-3xl font-bold text-indigo-900">{{ number_format($grandTotal['total_qty'], 0, ',', '.') }}</p>
            </div>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <p class="text-sm text-green-600 font-medium mb-1">Total Omzet</p>
                <p class="text-3xl font-bold text-green-900">Rp {{ number_format($grandTotal['total_amount'], 0, ',', '.') }}</p>
            </div>
        </div>
    </div>

    <!-- Products Table -->
    <div class="overflow-x-auto">
        <table class="imperial-table w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">No</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Produk</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">SKU</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Total Qty</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Total Omzet</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Rata-rata Harga</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($products as $index => $product)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500">{{ $index + 1 }}</td>
                    <td class="px-6 py-3 text-sm font-medium text-gray-900">{{ $product->product_name }}</td>
                    <td class="px-6 py-3 whitespace-nowrap text-sm font-mono text-gray-600">{{ $product->sku }}</td>
                    <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-semibold text-gray-900">
                        {{ number_format($product->total_qty, 0, ',', '.') }}
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-semibold text-gray-900">
                        Rp {{ number_format($product->total_amount, 0, ',', '.') }}
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap text-right text-sm text-gray-600">
                        Rp {{ number_format($product->total_amount / $product->total_qty, 0, ',', '.') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center">
                        <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <p class="text-gray-500">Tidak ada data penjualan produk</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if($products->count() > 0)
            <tfoot class="bg-gray-50 border-t-2 border-gray-300">
                <tr>
                    <td colspan="3" class="px-6 py-4 text-right text-sm font-bold text-gray-900">
                        GRAND TOTAL:
                    </td>
                    <td class="px-6 py-4 text-right text-lg font-bold text-indigo-600">
                        {{ number_format($grandTotal['total_qty'], 0, ',', '.') }}
                    </td>
                    <td colspan="2" class="px-6 py-4 text-right text-lg font-bold text-indigo-600">
                        Rp {{ number_format($grandTotal['total_amount'], 0, ',', '.') }}
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
        padding: 0 !important;
    }
}
</style>
@endsection
