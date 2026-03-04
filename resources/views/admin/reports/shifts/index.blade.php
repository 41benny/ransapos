@extends('layouts.admin')

@section('title', 'Laporan Shift Kasir')
@section('page-title', 'Laporan Shift Kasir')
@section('page-subtitle', 'Ringkasan shift per kasir')

@section('content')
<div class="page-fullwidth">
<div class="mb-4 no-print flex justify-end">
    <a href="{{ route('admin.reports.index', ['tab' => request('tab', 'penjualan')]) }}"
        class="ui-btn ui-btn-ghost inline-flex h-10 items-center rounded-lg border border-slate-300 px-4 text-sm font-normal text-slate-700 hover:bg-slate-50">
        Kembali ke Katalog
    </a>
</div>
<div class="ui-card bg-white rounded-xl shadow-sm border border-gray-100 page-card-fill">

    @if(($viewMode ?? 'ringkas') === 'detail')
        <div class="p-4 border-b border-amber-100 bg-amber-50">
            <p class="text-sm text-amber-900">
                Mode detail menampilkan analisis anomali lintas tanggal, perangkat buka/tutup, dan selisih luar periode.
            </p>
        </div>
    @endif

    <div class="p-6 border-b border-gray-100 no-print">
        <form method="GET" action="{{ route('admin.reports.shifts.index') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <input type="hidden" name="tab" value="{{ request('tab', 'penjualan') }}">

            <div>
                <label class="block text-sm font-normal text-gray-700 mb-1">Dari Tanggal</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}" required
                       class="ui-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-normal text-gray-700 mb-1">Sampai Tanggal</label>
                <input type="date" name="date_to" value="{{ $dateTo }}" required
                       class="ui-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-normal text-gray-700 mb-1">Outlet</label>
                <select name="outlet_id" class="ui-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Semua Outlet</option>
                    @foreach($outlets as $outlet)
                    <option value="{{ $outlet->id }}" {{ ($filters['outlet_id'] ?? '') == $outlet->id ? 'selected' : '' }}>
                        {{ $outlet->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-normal text-gray-700 mb-1">Kasir</label>
                <select name="user_id" class="ui-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Semua Kasir</option>
                    @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ ($filters['user_id'] ?? '') == $user->id ? 'selected' : '' }}>
                        {{ $user->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-normal text-gray-700 mb-1">Status</label>
                <select name="status" class="ui-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Semua Status</option>
                    <option value="open" {{ ($filters['status'] ?? '') == 'open' ? 'selected' : '' }}>Open</option>
                    <option value="closed" {{ ($filters['status'] ?? '') == 'closed' ? 'selected' : '' }}>Closed</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-normal text-gray-700 mb-1">Mode Tampilan</label>
                <select name="view_mode" class="ui-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="ringkas" {{ ($viewMode ?? 'ringkas') === 'ringkas' ? 'selected' : '' }}>Ringkas</option>
                    <option value="detail" {{ ($viewMode ?? 'ringkas') === 'detail' ? 'selected' : '' }}>Detail</option>
                </select>
            </div>

            <div class="md:col-span-6 flex space-x-2">
                <button type="submit" class="ui-btn ui-btn-primary px-5 py-2.5 bg-gradient-to-r from-emerald-500 via-teal-500 to-sky-500 text-white rounded-full transition shadow-md hover:shadow-lg">
                    <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    Filter
                </button>
                <a href="{{ route('admin.reports.shifts.index', ['tab' => request('tab', 'penjualan')]) }}" class="ui-btn ui-btn-ghost px-5 py-2.5 bg-white border border-amber-300 hover:bg-amber-50 text-amber-900 rounded-full transition">
                    Reset
                </a>
                <a href="{{ route('admin.reports.shifts.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}" class="ui-btn ui-btn-ghost px-5 py-2.5 bg-emerald-50 border border-emerald-200 hover:bg-emerald-100 text-emerald-700 rounded-full transition">
                    Export Excel
                </a>
                <a href="{{ route('admin.reports.shifts.export', array_merge(request()->query(), ['format' => 'pdf'])) }}" class="ui-btn ui-btn-ghost px-5 py-2.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-full transition">
                    Export PDF
                </a>
                <button type="button" onclick="window.print()" class="ui-btn ui-btn-primary ml-auto px-5 py-2.5 bg-gradient-to-r from-emerald-500 via-teal-500 to-sky-500 text-white rounded-full transition shadow-md hover:shadow-lg">
                    <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Cetak
                </button>
            </div>
        </form>
    </div>

    <div class="p-6 border-b border-gray-100 print-only hidden">
        <div class="text-center mb-4">
            <h1 class="text-2xl font-normal text-gray-900">LAPORAN SHIFT KASIR</h1>
            <p class="text-gray-600 mt-1">Periode: {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</p>
            <p class="text-sm text-gray-500">Dicetak: {{ now()->format('d M Y, H:i') }}</p>
        </div>
    </div>

    @if(($viewMode ?? 'ringkas') === 'ringkas')
        <div class="p-6 border-b border-gray-100">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p class="text-sm text-blue-600 font-normal mb-1">Total Shift</p>
                    <p class="text-3xl font-normal text-blue-900">{{ $totals['total_shifts'] }}</p>
                </div>

                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <p class="text-sm text-green-600 font-normal mb-1">Total Omzet</p>
                    <p class="text-2xl font-normal text-green-900">Rp {{ number_format($totals['total_sales'], 0, ',', '.') }}</p>
                </div>

                <div class="bg-{{ $totals['total_difference'] >= 0 ? 'green' : 'red' }}-50 border border-{{ $totals['total_difference'] >= 0 ? 'green' : 'red' }}-200 rounded-lg p-4">
                    <p class="text-sm text-{{ $totals['total_difference'] >= 0 ? 'green' : 'red' }}-600 font-normal mb-1">Total Selisih</p>
                    <p class="text-2xl font-normal text-{{ $totals['total_difference'] >= 0 ? 'green' : 'red' }}-900">
                        {{ $totals['total_difference'] >= 0 ? '+' : '' }} Rp {{ number_format(abs($totals['total_difference']), 0, ',', '.') }}
                    </p>
                </div>

                <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                    <p class="text-sm text-orange-600 font-normal mb-1">Shift dengan Selisih</p>
                    <p class="text-xl font-normal text-orange-900">
                        {{ $totals['shifts_with_shortage'] }} kurang / {{ $totals['shifts_with_overage'] }} lebih
                    </p>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="ui-table imperial-table w-full">
                <thead>
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-normal text-gray-600 uppercase">Session Number</th>
                        <th class="px-6 py-3 text-left text-xs font-normal text-gray-600 uppercase">Outlet</th>
                        <th class="px-6 py-3 text-left text-xs font-normal text-gray-600 uppercase">Kasir</th>
                        <th class="px-6 py-3 text-left text-xs font-normal text-gray-600 uppercase">Dibuka</th>
                        <th class="px-6 py-3 text-left text-xs font-normal text-gray-600 uppercase">Ditutup</th>
                        <th class="px-6 py-3 text-right text-xs font-normal text-gray-600 uppercase">Total Penjualan</th>
                        <th class="px-6 py-3 text-right text-xs font-normal text-gray-600 uppercase">Selisih</th>
                        <th class="px-6 py-3 text-center text-xs font-normal text-gray-600 uppercase">Status</th>
                        <th class="px-6 py-3 text-center text-xs font-normal text-gray-600 uppercase no-print">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sessions as $session)
                    <tr>
                        <td class="px-6 py-3 whitespace-nowrap">
                            <a href="{{ route('admin.reports.shifts.show', ['cashSession' => $session, 'tab' => request('tab', 'penjualan'), 'view_mode' => ($viewMode ?? 'ringkas')]) }}"
                               class="text-sm font-mono text-amber-700 hover:text-amber-800 hover:underline">
                                {{ $session->session_number }}
                            </a>
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900">{{ $session->outlet->name }}</td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900">{{ $session->user->name }}</td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">{{ $session->opened_at->format('d M Y, H:i') }}</td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">{{ $session->closed_at ? $session->closed_at->format('d M Y, H:i') : '-' }}</td>
                        <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-normal text-gray-900">Rp {{ number_format($session->total_sales, 0, ',', '.') }}</td>
                        <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-normal">
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
                                <span class="px-3 py-1 text-xs font-normal bg-green-100 text-green-800 rounded-full">Open</span>
                            @else
                                <span class="px-3 py-1 text-xs font-normal bg-gray-100 text-gray-800 rounded-full">Closed</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap text-center no-print">
                            <a href="{{ route('admin.reports.shifts.show', ['cashSession' => $session, 'tab' => request('tab', 'penjualan'), 'view_mode' => ($viewMode ?? 'ringkas')]) }}"
                               class="text-amber-700 hover:text-amber-800 text-sm font-normal">
                                Detail ->
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center">
                            <p class="text-gray-500">Tidak ada shift pada periode ini</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        <div class="p-6 border-b border-gray-100">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p class="text-sm text-blue-600 font-normal mb-1">Total Shift</p>
                    <p class="text-3xl font-normal text-blue-900">{{ $totals['total_shifts'] }}</p>
                </div>

                <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4">
                    <p class="text-sm text-emerald-600 font-normal mb-1">Omzet Periode</p>
                    <p class="text-2xl font-normal text-emerald-900">Rp {{ number_format($totals['total_sales_period'], 0, ',', '.') }}</p>
                </div>

                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <p class="text-sm text-green-600 font-normal mb-1">Omzet Total Shift</p>
                    <p class="text-2xl font-normal text-green-900">Rp {{ number_format($totals['total_sales_shift'], 0, ',', '.') }}</p>
                </div>

                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                    <p class="text-sm text-amber-700 font-normal mb-1">Selisih Luar Periode</p>
                    <p class="text-2xl font-normal text-amber-900">Rp {{ number_format($totals['total_outside_period'], 0, ',', '.') }}</p>
                </div>

                <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                    <p class="text-sm text-orange-600 font-normal mb-1">Shift Anomali</p>
                    <p class="text-xl font-normal text-orange-900">{{ $totals['shifts_with_anomaly'] }}</p>
                    <p class="text-xs text-orange-700 mt-1">{{ $totals['shifts_with_shortage'] }} kurang / {{ $totals['shifts_with_overage'] }} lebih</p>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="ui-table imperial-table w-full">
                <thead>
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-normal text-gray-600 uppercase">Session Number</th>
                        <th class="px-6 py-3 text-left text-xs font-normal text-gray-600 uppercase">Outlet</th>
                        <th class="px-6 py-3 text-left text-xs font-normal text-gray-600 uppercase">Kasir</th>
                        <th class="px-6 py-3 text-left text-xs font-normal text-gray-600 uppercase">Dibuka</th>
                        <th class="px-6 py-3 text-left text-xs font-normal text-gray-600 uppercase">Ditutup</th>
                        <th class="px-6 py-3 text-left text-xs font-normal text-gray-600 uppercase">Rentang Tgl Sales</th>
                        <th class="px-6 py-3 text-right text-xs font-normal text-gray-600 uppercase">Omzet Periode</th>
                        <th class="px-6 py-3 text-right text-xs font-normal text-gray-600 uppercase">Omzet Shift</th>
                        <th class="px-6 py-3 text-right text-xs font-normal text-gray-600 uppercase">Luar Periode</th>
                        <th class="px-6 py-3 text-right text-xs font-normal text-gray-600 uppercase">Selisih Kas</th>
                        <th class="px-6 py-3 text-center text-xs font-normal text-gray-600 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-normal text-gray-600 uppercase">Trace / Anomali</th>
                        <th class="px-6 py-3 text-center text-xs font-normal text-gray-600 uppercase no-print">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sessions as $session)
                    <tr>
                        <td class="px-6 py-3 whitespace-nowrap">
                            <a href="{{ route('admin.reports.shifts.show', ['cashSession' => $session, 'tab' => request('tab', 'penjualan'), 'view_mode' => ($viewMode ?? 'ringkas')]) }}"
                               class="text-sm font-mono text-amber-700 hover:text-amber-800 hover:underline">
                                {{ $session->session_number }}
                            </a>
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900">{{ $session->outlet->name }}</td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900">{{ $session->user->name }}</td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">{{ $session->opened_at->format('d M Y, H:i') }}</td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">{{ $session->closed_at ? $session->closed_at->format('d M Y, H:i') : '-' }}</td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900">{{ $session->sale_date_range_label }}</td>
                        <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-normal text-emerald-700">Rp {{ number_format($session->period_sales_total, 0, ',', '.') }}</td>
                        <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-normal text-gray-900">Rp {{ number_format($session->total_sales, 0, ',', '.') }}</td>
                        <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-normal {{ $session->outside_period_sales > 0 ? 'text-amber-700' : 'text-gray-500' }}">Rp {{ number_format($session->outside_period_sales, 0, ',', '.') }}</td>
                        <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-normal">
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
                                <span class="px-3 py-1 text-xs font-normal bg-green-100 text-green-800 rounded-full">Open</span>
                            @else
                                <span class="px-3 py-1 text-xs font-normal bg-gray-100 text-gray-800 rounded-full">Closed</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-xs text-gray-700">
                            <div>Durasi: {{ $session->duration_minutes !== null ? $session->duration_minutes . ' menit' : '-' }}</div>
                            <div>Device: {{ $session->opened_device_name }} -> {{ $session->closed_device_name }}</div>
                            @if($session->anomaly_count > 0)
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @foreach($session->anomaly_notes as $note)
                                        <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-800">{{ $note }}</span>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-emerald-700 mt-1">Tidak ada indikator anomali.</div>
                            @endif
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap text-center no-print">
                            <a href="{{ route('admin.reports.shifts.show', ['cashSession' => $session, 'tab' => request('tab', 'penjualan'), 'view_mode' => ($viewMode ?? 'ringkas')]) }}"
                               class="text-amber-700 hover:text-amber-800 text-sm font-normal">
                                Detail ->
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="13" class="px-6 py-12 text-center">
                            <p class="text-gray-500">Tidak ada shift pada periode ini</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>

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
