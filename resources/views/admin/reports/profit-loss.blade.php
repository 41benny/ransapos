@extends('layouts.admin')

@section('title', 'Laporan Laba Rugi')
@section('page-title', 'Laporan Laba Rugi')
@section('page-subtitle', 'Ringkasan laba rugi berdasarkan periode')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Laporan Laba Rugi</h1>
            <p class="text-gray-600 mt-1">Periode: {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</p>
        </div>
        <a href="{{ route('admin.reports.index') }}"
            class="no-print inline-flex h-10 items-center rounded-lg border border-slate-300 px-4 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Kembali ke Katalog
        </a>
    </div>

    <!-- Filter -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                    <input type="date" 
                           name="date_from" 
                           value="{{ $dateFrom }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                    <input type="date" 
                           name="date_to" 
                           value="{{ $dateTo }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Outlet (Opsional)</label>
                    <select name="outlet_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="">Semua Outlet</option>
                        @foreach($outlets as $outlet)
                            <option value="{{ $outlet->id }}" {{ $outletId == $outlet->id ? 'selected' : '' }}>
                                {{ $outlet->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" 
                            class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg">
                        Tampilkan
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600 mb-1">Total Pendapatan</p>
            <p class="text-2xl font-bold text-gray-900">
                Rp {{ number_format($report['total_revenue'], 0, ',', '.') }}
            </p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600 mb-1">Laba Kotor</p>
            <p class="text-2xl font-bold text-green-600">
                Rp {{ number_format($report['gross_profit'], 0, ',', '.') }}
            </p>
            <p class="text-xs text-gray-500 mt-1">
                Margin: {{ number_format($report['gross_profit_margin'], 2) }}%
            </p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600 mb-1">Total Biaya</p>
            <p class="text-2xl font-bold text-red-600">
                Rp {{ number_format($report['total_expenses'], 0, ',', '.') }}
            </p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600 mb-1">Laba Bersih</p>
            <p class="text-2xl font-bold {{ $report['net_profit'] >= 0 ? 'text-indigo-600' : 'text-red-600' }}">
                Rp {{ number_format($report['net_profit'], 0, ',', '.') }}
            </p>
            <p class="text-xs text-gray-500 mt-1">
                Margin: {{ number_format($report['net_profit_margin'], 2) }}%
            </p>
        </div>
    </div>

    <!-- Detailed Report -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-900">Rincian Laporan</h2>
            <button onclick="window.print()" 
                    class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg flex items-center space-x-2 no-print">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                <span>Print</span>
            </button>
        </div>

        <div class="p-6">
            <!-- A. PENDAPATAN -->
            <div class="mb-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">A. PENDAPATAN</h3>
                <div class="pl-4">
                    <div class="flex justify-between py-2 border-b border-gray-200">
                        <span class="text-sm text-gray-900">Penjualan</span>
                        <span class="text-sm font-semibold text-gray-900">
                            Rp {{ number_format($report['total_revenue'], 0, ',', '.') }}
                        </span>
                    </div>
                </div>
                <div class="flex justify-between py-2 bg-gray-100 font-semibold mt-2">
                    <span>Total Pendapatan</span>
                    <span>Rp {{ number_format($report['total_revenue'], 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- B. HARGA POKOK PENJUALAN -->
            <div class="mb-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">B. HARGA POKOK PENJUALAN (HPP)</h3>
                <div class="pl-4">
                    <div class="flex justify-between py-2 border-b border-gray-200">
                        <span class="text-sm text-gray-900">HPP Penjualan</span>
                        <span class="text-sm font-semibold text-red-600">
                            Rp {{ number_format($report['total_cogs'], 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- C. LABA KOTOR -->
            <div class="mb-6 border-t-2 border-gray-400 pt-4">
                <div class="flex justify-between py-3 bg-green-50 rounded-lg px-4">
                    <div>
                        <span class="font-bold text-green-800">LABA KOTOR</span>
                        <p class="text-xs text-green-600 mt-1">
                            Margin: {{ number_format($report['gross_profit_margin'], 2) }}%
                        </p>
                    </div>
                    <span class="text-xl font-bold text-green-800">
                        Rp {{ number_format($report['gross_profit'], 0, ',', '.') }}
                    </span>
                </div>
            </div>

            <!-- D. BIAYA OPERASIONAL -->
            <div class="mb-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">C. BIAYA OPERASIONAL</h3>
                
                @if(count($report['expenses_by_group']) > 0)
                    @foreach($report['expenses_by_group'] as $group)
                        <div class="mb-4 pl-4">
                            <h4 class="text-sm font-semibold text-gray-600 mb-2">{{ $group['group_name'] }}</h4>
                            
                            @foreach($group['accounts'] as $account)
                                <div class="flex justify-between py-1 pl-4 text-sm text-gray-700">
                                    <span>{{ $account['code'] }} - {{ $account['name'] }}</span>
                                    <span>Rp {{ number_format($account['amount'], 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                            
                            <div class="flex justify-between py-2 border-b border-gray-200 font-semibold text-sm mt-1">
                                <span class="pl-4">Subtotal {{ $group['group_name'] }}</span>
                                <span>Rp {{ number_format($group['total'], 0, ',', '.') }}</span>
                            </div>
                        </div>
                    @endforeach
                    
                    <div class="flex justify-between py-2 bg-red-50 font-semibold mt-2 rounded-lg px-4">
                        <span>Total Biaya Operasional</span>
                        <span class="text-red-800">Rp {{ number_format($report['total_expenses'], 0, ',', '.') }}</span>
                    </div>
                @else
                    <div class="pl-4">
                        <p class="text-sm text-gray-500 italic">Belum ada biaya operasional tercatat</p>
                        <div class="flex justify-between py-2 bg-gray-50 font-semibold mt-2">
                            <span>Total Biaya Operasional</span>
                            <span>Rp 0</span>
                        </div>
                    </div>
                @endif
            </div>

            <!-- E. LABA BERSIH -->
            <div class="border-t-2 border-gray-400 pt-4">
                <div class="flex justify-between py-4 bg-indigo-50 rounded-lg px-4">
                    <div>
                        <span class="text-lg font-bold text-indigo-900">LABA BERSIH</span>
                        <p class="text-xs text-indigo-600 mt-1">
                            Margin Laba Bersih: {{ number_format($report['net_profit_margin'], 2) }}%
                        </p>
                    </div>
                    <span class="text-2xl font-bold {{ $report['net_profit'] >= 0 ? 'text-indigo-900' : 'text-red-600' }}">
                        Rp {{ number_format($report['net_profit'], 0, ',', '.') }}
                    </span>
                </div>
            </div>

            <!-- Summary Calculation -->
            <div class="mt-6 bg-gray-50 rounded-lg p-4 text-sm text-gray-600">
                <p class="font-semibold text-gray-700 mb-2">Ringkasan Perhitungan:</p>
                <p>Pendapatan: Rp {{ number_format($report['total_revenue'], 0, ',', '.') }}</p>
                <p>HPP: Rp {{ number_format($report['total_cogs'], 0, ',', '.') }}</p>
                <p class="border-t border-gray-300 mt-1 pt-1">Laba Kotor: Rp {{ number_format($report['gross_profit'], 0, ',', '.') }}</p>
                <p class="mt-1">Biaya Operasional: Rp {{ number_format($report['total_expenses'], 0, ',', '.') }}</p>
                <p class="border-t-2 border-gray-400 mt-1 pt-1 font-bold text-gray-900">
                    Laba Bersih: Rp {{ number_format($report['net_profit'], 0, ',', '.') }}
                </p>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .no-print {
        display: none !important;
    }
    body {
        font-size: 12px;
    }
    .container {
        max-width: 100%;
        padding: 0;
    }
}
</style>
@endsection

