@extends('layouts.admin')

@section('title', 'Laporan Shift Kasir')
@section('page-title', 'Laporan Shift Kasir')
@section('page-subtitle', 'Ringkasan shift per kasir dengan analisis selisih')

@section('content')
<div class="page-fullwidth">
<div class="mb-4 no-print flex justify-end">
    <a href="{{ route('admin.reports.index') }}"
        class="inline-flex h-10 items-center rounded-lg border border-slate-300 px-4 text-sm font-medium text-slate-700 hover:bg-slate-50">
        Kembali ke Katalog
    </a>
</div>
<div class="bg-white rounded-xl shadow-sm border border-gray-100 page-card-fill">

    <!-- Filter Section -->
    <div class="p-6 border-b border-gray-100 no-print">
        <form method="GET" action="{{ route('admin.reports.shifts.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">

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

            <!-- Status -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Semua Status</option>
                    <option value="open" {{ ($filters['status'] ?? '') == 'open' ? 'selected' : '' }}>Open</option>
                    <option value="closed" {{ ($filters['status'] ?? '') == 'closed' ? 'selected' : '' }}>Closed</option>
                </select>
            </div>

            <!-- Actions -->
            <div class="md:col-span-5 flex space-x-2">
                <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-emerald-500 via-teal-500 to-sky-500 text-white rounded-full transition shadow-md hover:shadow-lg">
                    <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    Filter
                </button>
                <a href="{{ route('admin.reports.shifts.index') }}" class="px-5 py-2.5 bg-white border border-amber-300 hover:bg-amber-50 text-amber-900 rounded-full transition">
                    Reset
                </a>
                <button type="button" onclick="window.print()" class="ml-auto px-5 py-2.5 bg-gradient-to-r from-emerald-500 via-teal-500 to-sky-500 text-white rounded-full transition shadow-md hover:shadow-lg">
                    <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Cetak
                </button>
            </div>
        </form>
    </div>

    <!-- Report Header (Print) -->
    <div class="p-6 border-b border-gray-100 print-only hidden">
        <div class="text-center mb-4">
            <h1 class="text-2xl font-bold text-gray-900">LAPORAN SHIFT KASIR</h1>
            <p class="text-gray-600 mt-1">Periode: {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</p>
            <p class="text-sm text-gray-500">Dicetak: {{ now()->format('d M Y, H:i') }}</p>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="p-6 border-b border-gray-100">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

            <!-- Total Shifts -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <p class="text-sm text-blue-600 font-medium mb-1">Total Shift</p>
                <p class="text-3xl font-bold text-blue-900">{{ $totals['total_shifts'] }}</p>
            </div>

            <!-- Total Sales -->
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <p class="text-sm text-green-600 font-medium mb-1">Total Omzet</p>
                <p class="text-2xl font-bold text-green-900">Rp {{ number_format($totals['total_sales'], 0, ',', '.') }}</p>
            </div>

            <!-- Total Difference -->
            <div class="bg-{{ $totals['total_difference'] >= 0 ? 'green' : 'red' }}-50 border border-{{ $totals['total_difference'] >= 0 ? 'green' : 'red' }}-200 rounded-lg p-4">
                <p class="text-sm text-{{ $totals['total_difference'] >= 0 ? 'green' : 'red' }}-600 font-medium mb-1">Total Selisih</p>
                <p class="text-2xl font-bold text-{{ $totals['total_difference'] >= 0 ? 'green' : 'red' }}-900">
                    {{ $totals['total_difference'] >= 0 ? '+' : '' }} Rp {{ number_format(abs($totals['total_difference']), 0, ',', '.') }}
                </p>
            </div>

            <!-- Issues -->
            <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                <p class="text-sm text-orange-600 font-medium mb-1">Shift dengan Selisih</p>
                <p class="text-xl font-bold text-orange-900">
                    {{ $totals['shifts_with_shortage'] }} kurang / {{ $totals['shifts_with_overage'] }} lebih
                </p>
            </div>
        </div>
    </div>

    <!-- Shifts Table -->
    <div class="overflow-x-auto">
        <table class="imperial-table w-full">
            <thead class="">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Session Number</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Outlet</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Kasir</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Dibuka</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Ditutup</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Total Penjualan</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Selisih</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Status</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase no-print">Aksi</th>
                </tr>
            </thead>
            <tbody class="">
                @forelse($sessions as $session)
                <tr>
                    <td class="px-6 py-3 whitespace-nowrap">
                        <a href="{{ route('admin.reports.shifts.show', $session) }}"
                           class="text-sm font-mono text-amber-700 hover:text-amber-800 hover:underline">
                            {{ $session->session_number }}
                        </a>
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900">{{ $session->outlet->name }}</td>
                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900">{{ $session->user->name }}</td>
                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">
                        {{ $session->opened_at->format('d M Y, H:i') }}
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">
                        {{ $session->closed_at ? $session->closed_at->format('d M Y, H:i') : '-' }}
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-semibold text-gray-900">
                        Rp {{ number_format($session->total_sales, 0, ',', '.') }}
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-semibold">
                        @if($session->status === 'closed')
                            @if($session->difference > 0)
                                <span class="text-green-600">+ Rp {{ number_format($session->difference, 0, ',', '.') }}</span>
                            @elseif($session->difference < 0)
                                <span class="text-red-600">- Rp {{ number_format(abs($session->difference), 0, ',', '.') }}</span>
                            @else
                                <span class="text-gray-600">Rp 0</span>
                            @endif
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap text-center">
                        @if($session->status === 'open')
                            <span class="px-3 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">Open</span>
                        @else
                            <span class="px-3 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">Closed</span>
                        @endif
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap text-center no-print">
                        <a href="{{ route('admin.reports.shifts.show', $session) }}"
                           class="text-amber-700 hover:text-amber-800 text-sm font-medium">
                            Detail →
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-6 py-12 text-center">
                        <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <p class="text-gray-500">Tidak ada shift pada periode ini</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
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
</div>
</div>
@endsection
