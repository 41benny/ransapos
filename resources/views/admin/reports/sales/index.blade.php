@extends('layouts.admin')

@section('title', 'Laporan Penjualan')
@section('page-title', 'Laporan Penjualan')
@section('page-subtitle', 'Ringkasan dan detail transaksi penjualan unit usaha')

@section('content')
    <div class="w-full animate-in fade-in slide-in-from-bottom-2 duration-500">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-normal text-slate-800 tracking-tight">Laporan Penjualan</h1>
                <p class="text-xs font-normal text-slate-500 mt-0.5">Ringkasan dan detail transaksi penjualan unit usaha</p>
            </div>
            <div class="flex items-center gap-3 no-print">
                <a href="{{ route('admin.reports.index', ['tab' => request('tab', 'penjualan')]) }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-xs font-normal text-slate-700 border border-slate-200 shadow-sm transition-all hover:bg-slate-50 active:scale-95">
                    <i class="fas fa-arrow-left text-[10px]"></i>
                    <span>Kembali ke Katalog</span>
                </a>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 mb-6 no-print">
            <div class="p-5 border-b border-slate-100 bg-slate-50/50 rounded-t-2xl">
                <div class="flex items-center gap-2">
                    <i class="fas fa-filter text-indigo-500 text-xs"></i>
                    <h3 class="text-[10px] font-normal text-slate-400 uppercase tracking-widest leading-none">Filter Laporan</h3>
                </div>
            </div>
            <div class="p-5">
                <form method="GET" action="{{ route('admin.reports.sales.index') }}" class="space-y-4">
                    <input type="hidden" name="tab" value="{{ request('tab', 'penjualan') }}">
                    @php
                        $selectedOutletIds = collect($filters['outlet_ids'] ?? [])->map(fn($id) => (int) $id)->all();
                    @endphp

                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                        <!-- Date From -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Dari Tanggal</label>
                            <input type="date" name="date_from" value="{{ $dateFrom }}" required
                                class="w-full px-3 py-1.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                        </div>

                        <!-- Date To -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Sampai Tanggal</label>
                            <input type="date" name="date_to" value="{{ $dateTo }}" required
                                class="w-full px-3 py-1.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                        </div>

                        <!-- Outlet -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Outlet</label>
                            <div class="relative" id="salesOutletFilterWrap">
                                <button type="button" id="salesOutletDropdownBtn"
                                    class="w-full px-3 py-1.5 text-left text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all flex items-center justify-between">
                                    <span id="salesOutletDropdownLabel">Semua Outlet</span>
                                    <i class="fas fa-chevron-down text-[10px] text-slate-400"></i>
                                </button>
                                <div id="salesOutletDropdownMenu"
                                    class="hidden absolute top-full left-0 mt-1 w-full rounded-lg border border-slate-200 bg-white shadow-lg p-2 z-20">
                                    <label class="flex items-center gap-2 text-[11.5px] text-slate-700 pb-1 mb-1 border-b border-slate-100">
                                        <input type="checkbox" id="salesOutletAllCheckbox"
                                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                            {{ count($selectedOutletIds) === 0 ? 'checked' : '' }}>
                                        <span>Semua Outlet</span>
                                    </label>
                                    <div style="max-height: 9rem; overflow-y: auto;" class="space-y-1 pr-1">
                                        @foreach($outlets as $outlet)
                                            <label class="flex items-center gap-2 text-[11.5px] text-slate-700">
                                                <input type="checkbox"
                                                    name="outlet_ids[]"
                                                    value="{{ $outlet->id }}"
                                                    {{ (count($selectedOutletIds) === 0 || in_array((int) $outlet->id, $selectedOutletIds, true)) ? 'checked' : '' }}
                                                    class="sales-outlet-checkbox rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                                <span class="truncate">{{ $outlet->name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <p class="text-[9px] text-slate-400 ml-1">Kosong = semua outlet</p>
                        </div>

                        <!-- Kasir -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Kasir</label>
                            <select name="user_id"
                                class="w-full px-3 py-1.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                <option value="">Semua Kasir</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ ($filters['user_id'] ?? '') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Payment Method -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Pembayaran</label>
                            <select name="payment_method_id"
                                class="w-full px-3 py-1.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                <option value="">Semua Metode</option>
                                @foreach($paymentMethods as $method)
                                    <option value="{{ $method->id }}" {{ ($filters['payment_method_id'] ?? '') == $method->id ? 'selected' : '' }}>
                                        {{ $method->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Mode View -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Tampilan</label>
                            <select name="view_mode"
                                class="w-full px-3 py-1.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                <option value="ringkas" {{ ($viewMode ?? 'ringkas') === 'ringkas' ? 'selected' : '' }}>Ringkas</option>
                                <option value="detail" {{ ($viewMode ?? 'ringkas') === 'detail' ? 'selected' : '' }}>Detil</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                        <div class="flex items-center gap-2">
                            <button type="submit"
                                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-normal text-white shadow-sm transition-all hover:bg-indigo-700 active:scale-95">
                                <i class="fas fa-filter text-[10px]"></i>
                                <span>Terapkan Filter</span>
                            </button>
                            <a href="{{ route('admin.reports.sales.index', ['tab' => request('tab', 'penjualan')]) }}"
                                class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-xs font-normal text-slate-600 border border-slate-200 shadow-sm transition-all hover:bg-slate-50 active:scale-95">
                                <i class="fas fa-undo text-[10px]"></i>
                                <span>Reset</span>
                            </a>
                        </div>

                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.reports.sales.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}"
                                class="inline-flex items-center gap-2 rounded-lg bg-emerald-50 px-4 py-2 text-xs font-normal text-emerald-700 border border-emerald-100 shadow-sm transition-all hover:bg-emerald-100 active:scale-95">
                                <i class="fas fa-file-excel text-[10px]"></i>
                                <span>Excel</span>
                            </a>
                            <a href="{{ route('admin.reports.sales.export', array_merge(request()->query(), ['format' => 'pdf'])) }}"
                                class="inline-flex items-center gap-2 rounded-lg bg-rose-50 px-4 py-2 text-xs font-normal text-rose-700 border border-rose-100 shadow-sm transition-all hover:bg-rose-100 active:scale-95">
                                <i class="fas fa-file-pdf text-[10px]"></i>
                                <span>PDF</span>
                            </a>
                            <button type="button" onclick="window.print()"
                                class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-xs font-normal text-white shadow-sm transition-all hover:bg-slate-800 active:scale-95">
                                <i class="fas fa-print text-[10px]"></i>
                                <span>Cetak Laporan</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Report Header (Print) -->
        <div class="p-6 border-b border-gray-100 print-only hidden">
            <div class="text-center mb-4">
                <h1 class="text-2xl font-normal text-gray-900">LAPORAN PENJUALAN</h1>
                <p class="text-gray-600 mt-1">Periode: {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} -
                    {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</p>
                <p class="text-sm text-gray-500">Dicetak: {{ now()->format('d M Y, H:i') }}</p>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
            <!-- Total Transaksi Card -->
            <div class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all hover:shadow-md">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[9px] font-normal uppercase tracking-[0.2em] text-indigo-500">Total Transaksi</span>
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 transition-colors group-hover:bg-indigo-600 group-hover:text-white">
                        <i class="fas fa-shopping-bag text-xs"></i>
                    </div>
                </div>
                <div class="flex flex-col">
                    <h3 class="text-xl font-normal text-slate-800">{{ $summary['total_transactions'] }}</h3>
                    <p class="text-[10px] font-normal text-slate-400 mt-0.5">Transaksi Terhitung</p>
                </div>
            </div>

            <!-- Total Omzet Card -->
            <div class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all hover:shadow-md border-l-4 border-l-emerald-500">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[9px] font-normal uppercase tracking-[0.2em] text-emerald-500">Total Omzet</span>
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 transition-colors group-hover:bg-emerald-600 group-hover:text-white">
                        <i class="fas fa-money-bill-wave text-xs"></i>
                    </div>
                </div>
                <div class="flex flex-col">
                    <h3 class="text-xl font-normal text-slate-800">Rp {{ number_format($summary['total_amount'], 0, ',', '.') }}</h3>
                    <p class="text-[10px] font-normal text-slate-400 mt-0.5">Penjualan Kotor</p>
                </div>
            </div>

            <!-- Total Pembulatan Card -->
            <div class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all hover:shadow-md">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[9px] font-normal uppercase tracking-[0.2em] text-slate-500">Total Pembulatan</span>
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-50 text-slate-600 transition-colors group-hover:bg-slate-600 group-hover:text-white">
                        <i class="fas fa-coins text-xs"></i>
                    </div>
                </div>
                <div class="flex flex-col">
                    <h3 class="text-xl font-normal text-slate-800">
                        {{ $summary['total_rounding'] >= 0 ? '+' : '-' }}Rp {{ number_format(abs($summary['total_rounding']), 0, ',', '.') }}
                    </h3>
                    <p class="text-[10px] font-normal text-slate-400 mt-0.5">Selisih Pembulatan</p>
                </div>
            </div>

            <!-- Average Card -->
            <div class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all hover:shadow-md">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[9px] font-normal uppercase tracking-[0.2em] text-purple-500">Rerata Per Transaksi</span>
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-50 text-purple-600 transition-colors group-hover:bg-purple-600 group-hover:text-white">
                        <i class="fas fa-percentage text-xs"></i>
                    </div>
                </div>
                <div class="flex flex-col">
                    <h3 class="text-xl font-normal text-slate-800">Rp {{ number_format($summary['avg_per_transaction'], 0, ',', '.') }}</h3>
                    <p class="text-[10px] font-normal text-slate-400 mt-0.5">Average Value</p>
                </div>
            </div>

            <!-- Cash vs Non Card -->
            <div class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all hover:shadow-md">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[9px] font-normal uppercase tracking-[0.2em] text-amber-500">Cash vs Non-Cash</span>
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 transition-colors group-hover:bg-amber-600 group-hover:text-white">
                        <i class="fas fa-credit-card text-xs"></i>
                    </div>
                </div>
                <div class="flex flex-col">
                    <h3 class="text-xl font-normal text-slate-800">Rp {{ number_format($summary['total_cash'], 0, ',', '.') }}</h3>
                    <p class="text-[10px] font-normal text-slate-400 mt-0.5 text-ellipsis overflow-hidden whitespace-nowrap">Cash: {{ number_format($summary['total_cash'], 0, ',', '.') }} | Non: {{ number_format($summary['total_non_cash'], 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/80 sticky top-0 backdrop-blur-sm z-10">
                        @if(($viewMode ?? 'ringkas') === 'detail')
                            <tr>
                                <th class="px-4 py-2.5 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500">No Transaksi</th>
                                <th class="px-4 py-2.5 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500">Tanggal</th>
                                <th class="px-4 py-2.5 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500">Outlet</th>
                                <th class="px-4 py-2.5 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500">Produk</th>
                                <th class="px-4 py-2.5 text-right text-[9px] font-normal uppercase tracking-widest text-slate-500">Qty</th>
                                <th class="px-4 py-2.5 text-right text-[9px] font-normal uppercase tracking-widest text-slate-500">Harga</th>
                                <th class="px-4 py-2.5 text-right text-[9px] font-normal uppercase tracking-widest text-slate-500">Diskon</th>
                                <th class="px-4 py-2.5 text-right text-[9px] font-normal uppercase tracking-widest text-slate-500">Subtotal</th>
                                <th class="px-4 py-2.5 text-right text-[9px] font-normal uppercase tracking-widest text-slate-500">Total</th>
                                <th class="px-4 py-2.5 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500">Status</th>
                                <th class="px-4 py-2.5 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500">Metode</th>
                            </tr>
                        @else
                            <tr>
                                <th class="px-4 py-2.5 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500">Tanggal & Jam</th>
                                <th class="px-4 py-2.5 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500">Invoice</th>
                                <th class="px-4 py-2.5 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500">Outlet</th>
                                <th class="px-4 py-2.5 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500">Kasir</th>
                                <th class="px-4 py-2.5 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500">Pembayaran</th>
                                <th class="px-4 py-2.5 text-right text-[9px] font-normal uppercase tracking-widest text-slate-500">Bulat</th>
                                <th class="px-4 py-2.5 text-right text-[9px] font-normal uppercase tracking-widest text-slate-500">Total</th>
                            </tr>
                        @endif
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @if(($viewMode ?? 'ringkas') === 'detail')
                            @forelse($detailRows as $row)
                                <tr class="group hover:bg-slate-50/80 transition-colors">
                                    <td class="px-4 py-2.5 whitespace-nowrap text-[11px] font-normal text-slate-800">{{ $row->transaction_number }}</td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-[11px] text-slate-600">{{ \Carbon\Carbon::parse($row->sale_date)->format('d M Y') }}</td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-[11px] text-slate-600">{{ $row->outlet_name }}</td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-[11px] text-slate-800 font-normal uppercase tracking-tighter">{{ $row->product_name }}</td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-right text-[11px] text-slate-800 font-normal">{{ number_format($row->qty, 0, ',', '.') }}</td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-right text-[11px] text-slate-500 italic">Rp {{ number_format($row->price, 0, ',', '.') }}</td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-right text-[11px] text-rose-500 font-normal whitespace-nowrap">Rp {{ number_format($row->item_discount, 0, ',', '.') }}</td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-right text-[11px] font-normal text-slate-800">Rp {{ number_format($row->item_subtotal, 0, ',', '.') }}</td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-right text-[11px] font-normal text-indigo-600">Rp {{ number_format($row->item_total ?? $row->item_subtotal, 0, ',', '.') }}</td>
                                    <td class="px-4 py-2.5 whitespace-nowrap">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-normal {{ $row->payment_status === 'Lunas' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600' }}">
                                            {{ $row->payment_status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-[11px] text-slate-500">{{ $row->payment_methods }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center justify-center opacity-40">
                                            <i class="fas fa-receipt text-4xl mb-4 text-slate-300"></i>
                                            <p class="text-[11px] font-normal text-slate-500 italic">Tidak ada detail penjualan pada periode ini</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        @else
                            @forelse($sales as $sale)
                                <tr class="group hover:bg-slate-50/80 transition-colors">
                                    <td class="px-4 py-2.5 whitespace-nowrap text-[11px] font-normal text-slate-600">
                                        {{ $sale->created_at->format('d M Y, H:i') }}
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-[11px] font-normal text-slate-800">
                                        {{ $sale->invoice_number }}
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-[11px] text-slate-500">
                                        {{ $sale->outlet->name }}
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-[11px] text-slate-500">
                                        {{ $sale->user->name }}
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[9px] font-normal bg-indigo-50 text-indigo-700">
                                            {{ $sale->payments->first()->paymentMethod->name ?? 'Mixed' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-right text-[11px] text-slate-400 italic">
                                        {{ (float) $sale->rounding_amount >= 0 ? '+' : '-' }}Rp {{ number_format(abs((float) $sale->rounding_amount), 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-right text-[11px] font-normal text-slate-900">
                                        Rp {{ number_format($sale->total_amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center justify-center opacity-40">
                                            <i class="fas fa-file-invoice-dollar text-4xl mb-4 text-slate-300"></i>
                                            <p class="text-[11px] font-normal text-slate-500 italic">Tidak ada transaksi pada periode ini</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        @endif
                    </tbody>
                    @if(($viewMode ?? 'ringkas') !== 'detail' && $sales->count() > 0)
                        <tfoot class="bg-indigo-50/30">
                            <tr>
                                <td colspan="6" class="px-4 py-3 text-right text-[10px] font-normal text-slate-600 uppercase tracking-wider">
                                    TOTAL ({{ $summary['total_transactions'] }} transaksi):
                                </td>
                                <td class="px-4 py-3 text-right text-sm font-normal text-indigo-700">
                                    Rp {{ number_format($summary['total_amount'], 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
            body { background: white; }
            aside, header { display: none !important; }
            main { padding: 0 !important; }
        }
        </style>
    @endpush
    @push('scripts')
        <script>
            (() => {
                const wrap = document.getElementById('salesOutletFilterWrap');
                if (!wrap) return;

                const dropdownBtn = document.getElementById('salesOutletDropdownBtn');
                const dropdownLabel = document.getElementById('salesOutletDropdownLabel');
                const dropdownMenu = document.getElementById('salesOutletDropdownMenu');
                const allCheckbox = document.getElementById('salesOutletAllCheckbox');
                const itemCheckboxes = Array.from(document.querySelectorAll('.sales-outlet-checkbox'));

                const updateLabel = () => {
                    const checkedItems = itemCheckboxes.filter((checkbox) => checkbox.checked);
                    if (allCheckbox.checked || checkedItems.length === 0) {
                        dropdownLabel.textContent = 'Semua Outlet';
                        return;
                    }

                    if (checkedItems.length === 1) {
                        dropdownLabel.textContent = checkedItems[0].parentElement?.textContent?.trim() || '1 Outlet Dipilih';
                        return;
                    }

                    dropdownLabel.textContent = `${checkedItems.length} Outlet Dipilih`;
                };

                dropdownBtn.addEventListener('click', () => {
                    dropdownMenu.classList.toggle('hidden');
                });

                document.addEventListener('click', (event) => {
                    if (wrap.contains(event.target)) return;
                    dropdownMenu.classList.add('hidden');
                });

                allCheckbox.addEventListener('change', () => {
                    itemCheckboxes.forEach((checkbox) => { 
                        checkbox.checked = allCheckbox.checked; 
                    });
                    updateLabel();
                });

                itemCheckboxes.forEach((checkbox) => {
                    checkbox.addEventListener('change', () => {
                        const allChecked = itemCheckboxes.every((item) => item.checked);
                        allCheckbox.checked = allChecked;
                        updateLabel();
                    });
                });

                updateLabel();
            })();
        </script>
    @endpush
@endsection
