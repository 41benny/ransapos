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
                    class="ui-btn ui-btn-ghost inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-xs font-normal text-slate-700 border border-slate-200 shadow-sm transition-all hover:bg-slate-50 active:scale-95">
                    <i class="fas fa-arrow-left text-[10px]"></i>
                    <span>Kembali ke Katalog</span>
                </a>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="ui-card bg-white rounded-2xl shadow-sm border border-slate-200 mb-6 no-print">
            <div class="p-5 border-b border-slate-100 bg-slate-50/50 rounded-t-2xl">
                <div class="flex items-center gap-2">
                    <i class="fas fa-filter text-indigo-500 text-xs"></i>
                    <h3 class="text-xs font-normal text-slate-400 uppercase tracking-widest leading-none">Filter Laporan</h3>
                </div>
            </div>
            <div class="p-5">
                <form method="GET" action="{{ route('admin.reports.sales.index') }}" class="space-y-4">
                    <input type="hidden" name="tab" value="{{ request('tab', 'penjualan') }}">
                    @php
                        $selectedOutletIds = collect($filters['outlet_ids'] ?? [])->map(fn($id) => (int) $id)->all();
                    @endphp

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <!-- Date Range Group -->
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Periode Tanggal</label>
                            <div class="relative">
                                <i class="far fa-calendar-alt absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs z-10 pointer-events-none"></i>
                                <input type="text" id="salesDateRangePicker"
                                    placeholder="Pilih rentang tanggal..."
                                    class="ui-input w-full pl-9 pr-3 py-2.5 text-[13px] font-bold bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all cursor-pointer"
                                    readonly>
                                <input type="hidden" name="date_from" id="salesDateFrom" value="{{ $dateFrom }}">
                                <input type="hidden" name="date_to" id="salesDateTo" value="{{ $dateTo }}">
                            </div>
                        </div>

                        <!-- Outlet selection -->
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Filter Outlet</label>
                            <div class="relative" id="salesOutletFilterWrap">
                                <button type="button" id="salesOutletDropdownBtn"
                                    class="ui-btn ui-btn-ghost w-full px-4 py-2.5 text-left text-[13px] font-bold bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all flex items-center justify-between shadow-sm">
                                    <span id="salesOutletDropdownLabel" class="truncate">Semua Outlet</span>
                                    <i class="fas fa-chevron-down text-[10px] text-slate-400"></i>
                                </button>
                                <div id="salesOutletDropdownMenu"
                                    class="hidden absolute top-full left-0 mt-2 w-full rounded-2xl border border-slate-200 bg-white shadow-xl p-3 z-50">
                                    <label class="flex items-center gap-3 px-2 py-2 text-[13px] font-bold text-slate-700 pb-2 mb-2 border-b border-slate-100 cursor-pointer">
                                        <input type="checkbox" id="salesOutletAllCheckbox"
                                            class="rounded-lg border-slate-300 text-indigo-600 focus:ring-indigo-500 h-5 w-5"
                                            {{ count($selectedOutletIds) === 0 ? 'checked' : '' }}>
                                        <span>Pilih Semua</span>
                                    </label>
                                    <div style="max-height: 12rem; overflow-y: auto;" class="space-y-1 pr-1 custom-scrollbar">
                                        @foreach($outlets as $outlet)
                                            <label class="flex items-center gap-3 px-2 py-2 text-[13px] text-slate-600 hover:bg-slate-50 rounded-lg cursor-pointer transition-colors">
                                                <input type="checkbox"
                                                    name="outlet_ids[]"
                                                    value="{{ $outlet->id }}"
                                                    {{ (count($selectedOutletIds) === 0 || in_array((int) $outlet->id, $selectedOutletIds, true)) ? 'checked' : '' }}
                                                    class="sales-outlet-checkbox rounded-lg border-slate-300 text-indigo-600 focus:ring-indigo-500 h-5 w-5">
                                                <span class="truncate font-medium">{{ $outlet->name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Other Filters Group -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Kasir</label>
                                <select name="user_id"
                                    class="ui-input w-full px-3 py-2.5 text-[13px] font-bold bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 transition-all appearance-none">
                                    <option value="">Semua Kasir</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ ($filters['user_id'] ?? '') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Bayar</label>
                                <select name="payment_method_id"
                                    class="ui-input w-full px-3 py-2.5 text-[13px] font-bold bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 transition-all appearance-none">
                                    <option value="">Semua</option>
                                    @foreach($paymentMethods as $method)
                                        <option value="{{ $method->id }}" {{ ($filters['payment_method_id'] ?? '') == $method->id ? 'selected' : '' }}>
                                            {{ $method->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 pt-5 border-t border-slate-100">
                        <button type="submit"
                            class="ui-btn ui-btn-primary inline-flex items-center gap-2 rounded-xl bg-slate-900 px-6 py-2.5 text-xs font-bold text-white shadow-sm transition-all hover:bg-indigo-600 active:scale-95">
                            <i class="fas fa-magnifying-glass text-[10px]"></i>
                            <span>Terapkan Filter</span>
                        </button>
                        <a href="{{ route('admin.reports.sales.index', ['tab' => request('tab', 'penjualan')]) }}"
                            class="ui-btn ui-btn-ghost inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-xs font-bold text-slate-500 border border-slate-200 transition-all hover:bg-slate-50 hover:text-rose-500 active:scale-95">
                            <i class="fas fa-rotate-left text-[10px]"></i>
                            <span>Reset</span>
                        </a>
                        <select name="view_mode" onchange="this.form.submit()"
                            class="ui-input flex-none px-3 py-2.5 text-[11px] font-bold bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 transition-all"
                            style="width: auto; min-width: 128px;">
                            <option value="ringkas" {{ ($viewMode ?? 'ringkas') === 'ringkas' ? 'selected' : '' }}>Ringkas</option>
                            <option value="detail" {{ ($viewMode ?? 'ringkas') === 'detail' ? 'selected' : '' }}>Detil</option>
                        </select>
                        <a href="{{ route('admin.reports.sales.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}"
                            class="ui-btn ui-btn-ghost inline-flex items-center gap-2 rounded-xl bg-emerald-50 px-4 py-2.5 text-[11px] font-bold text-emerald-700 border border-emerald-100 transition-all hover:bg-emerald-500 hover:text-white active:scale-95">
                            <i class="fas fa-file-excel text-[10px]"></i>
                            <span>EXCEL</span>
                        </a>
                        <a href="{{ route('admin.reports.sales.export', array_merge(request()->query(), ['format' => 'pdf'])) }}"
                            class="ui-btn ui-btn-ghost inline-flex items-center gap-2 rounded-xl bg-rose-50 px-4 py-2.5 text-[11px] font-bold text-rose-700 border border-rose-100 transition-all hover:bg-rose-500 hover:text-white active:scale-95">
                            <i class="fas fa-file-pdf text-[10px]"></i>
                            <span>PDF</span>
                        </a>
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
        <div class="ui-card bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="ui-table ui-table-standard min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50/80 sticky top-0 backdrop-blur-sm z-10">
                        @if(($viewMode ?? 'ringkas') === 'detail')
                            <tr>
                                <th class="px-4 py-2.5 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500 resizable group" style="min-width: 100px; position:relative;">
                                    No Transaksi <div class="resize-handle"></div>
                                </th>
                                <th class="px-4 py-2.5 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500 resizable group" style="min-width: 110px; position:relative;">
                                    Tanggal & Jam <div class="resize-handle"></div>
                                </th>
                                <th class="px-4 py-2.5 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500 resizable group" style="min-width: 100px; position:relative;">
                                    Outlet <div class="resize-handle"></div>
                                </th>
                                <th class="px-4 py-2.5 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500 resizable group" style="min-width: 110px; position:relative;">
                                    Customer <div class="resize-handle"></div>
                                </th>
                                <th class="px-4 py-2.5 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500 resizable group" style="min-width: 120px; position:relative;">
                                    Produk <div class="resize-handle"></div>
                                </th>
                                <th class="px-4 py-2.5 text-right text-[9px] font-normal uppercase tracking-widest text-slate-500 resizable group" style="min-width: 50px; position:relative;">
                                    Qty <div class="resize-handle"></div>
                                </th>
                                <th class="px-4 py-2.5 text-right text-[9px] font-normal uppercase tracking-widest text-slate-500 resizable group" style="min-width: 80px; position:relative;">
                                    Harga <div class="resize-handle"></div>
                                </th>
                                <th class="px-4 py-2.5 text-right text-[9px] font-normal uppercase tracking-widest text-slate-500 resizable group" style="min-width: 70px; position:relative;">
                                    Diskon <div class="resize-handle"></div>
                                </th>
                                <th class="px-4 py-2.5 text-right text-[9px] font-normal uppercase tracking-widest text-slate-500 resizable group" style="min-width: 80px; position:relative;">
                                    Subtotal <div class="resize-handle"></div>
                                </th>
                                <th class="px-4 py-2.5 text-right text-[9px] font-normal uppercase tracking-widest text-slate-500 resizable group" style="min-width: 80px; position:relative;">
                                    Total <div class="resize-handle"></div>
                                </th>
                                <th class="px-4 py-2.5 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500 resizable group" style="min-width: 70px; position:relative;">
                                    Status <div class="resize-handle"></div>
                                </th>
                                <th class="px-4 py-2.5 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500 resizable group" style="min-width: 100px; position:relative;">
                                    Metode Bayar <div class="resize-handle"></div>
                                </th>
                                <th class="px-4 py-2.5 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500 resizable group" style="min-width: 90px; position:relative;">
                                    Tipe Order <div class="resize-handle"></div>
                                </th>
                            </tr>
                            {{-- Filter Row Detail --}}
                            <tr class="bg-white border-b border-slate-100 no-print">
                                <td class="px-1 py-1 relative">
                                    <button type="button" id="clearFilters" title="Reset filter tabel" class="absolute left-1 top-1 h-6 w-6 inline-flex items-center justify-center rounded bg-slate-50 text-slate-400 hover:text-rose-500 transition-all z-10"><i class="fas fa-times text-[10px]"></i></button>
                                    <input type="text" data-name="filter_transaksi" placeholder="Cari..." class="filter-input w-full pl-7 pr-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500">
                                </td>
                                <td class="px-1 py-1"><input type="text" data-name="filter_tanggal" placeholder="Cari..." class="ui-input filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500"></td>
                                <td class="px-1 py-1"><input type="text" data-name="filter_outlet" placeholder="Cari..." class="ui-input filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500"></td>
                                <td class="px-1 py-1"><input type="text" data-name="filter_customer" placeholder="Cari..." class="ui-input filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500"></td>
                                <td class="px-1 py-1"><input type="text" data-name="filter_produk" placeholder="Cari..." class="ui-input filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500"></td>
                                <td class="px-1 py-1"><input type="text" data-name="filter_qty" placeholder="Cari..." class="ui-input filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500 text-right"></td>
                                <td class="px-1 py-1"><input type="text" data-name="filter_harga" placeholder="Cari..." class="ui-input filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500 text-right"></td>
                                <td class="px-1 py-1"><input type="text" data-name="filter_diskon" placeholder="Cari..." class="ui-input filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500 text-right"></td>
                                <td class="px-1 py-1"><input type="text" data-name="filter_subtotal" placeholder="Cari..." class="ui-input filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500 text-right"></td>
                                <td class="px-1 py-1"><input type="text" data-name="filter_total" placeholder="Cari..." class="ui-input filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500 text-right"></td>
                                <td class="px-1 py-1"><input type="text" data-name="filter_status" placeholder="Cari..." class="ui-input filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500"></td>
                                <td class="px-1 py-1"><input type="text" data-name="filter_metode_bayar" placeholder="Cari..." class="ui-input filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500"></td>
                                <td class="px-1 py-1"><input type="text" data-name="filter_metode_jual" placeholder="Cari..." class="ui-input filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500"></td>
                            </tr>
                        @else
                            <tr>
                                <th class="px-4 py-2.5 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500 resizable group" style="min-width: 120px; position:relative;">
                                    Tanggal & Jam <div class="resize-handle"></div>
                                </th>
                                <th class="px-4 py-2.5 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500 resizable group" style="min-width: 100px; position:relative;">
                                    Invoice <div class="resize-handle"></div>
                                </th>
                                <th class="px-4 py-2.5 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500 resizable group" style="min-width: 100px; position:relative;">
                                    Outlet <div class="resize-handle"></div>
                                </th>
                                <th class="px-4 py-2.5 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500 resizable group" style="min-width: 100px; position:relative;">
                                    Customer <div class="resize-handle"></div>
                                </th>
                                <th class="px-4 py-2.5 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500 resizable group" style="min-width: 100px; position:relative;">
                                    Kasir <div class="resize-handle"></div>
                                </th>
                                <th class="px-4 py-2.5 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500 resizable group" style="min-width: 100px; position:relative;">
                                    Pembayaran <div class="resize-handle"></div>
                                </th>
                                <th class="px-4 py-2.5 text-right text-[9px] font-normal uppercase tracking-widest text-slate-500 resizable group" style="min-width: 80px; position:relative;">
                                    Bulat <div class="resize-handle"></div>
                                </th>
                                <th class="px-4 py-2.5 text-right text-[9px] font-normal uppercase tracking-widest text-slate-500 resizable group" style="min-width: 100px; position:relative;">
                                    Total <div class="resize-handle"></div>
                                </th>
                            </tr>
                            {{-- Filter Row Ringkas --}}
                            <tr class="bg-white border-b border-slate-100 no-print">
                                <td class="px-1 py-1 relative">
                                    <button type="button" id="clearFilters" title="Reset filter tabel" class="absolute left-1 top-1 h-6 w-6 inline-flex items-center justify-center rounded bg-slate-50 text-slate-400 hover:text-rose-500 transition-all z-10"><i class="fas fa-times text-[10px]"></i></button>
                                    <input type="text" data-name="filter_tanggal" placeholder="Cari..." class="filter-input w-full pl-7 pr-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500">
                                </td>
                                <td class="px-1 py-1"><input type="text" data-name="filter_invoice" placeholder="Cari..." class="ui-input filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500"></td>
                                <td class="px-1 py-1"><input type="text" data-name="filter_outlet" placeholder="Cari..." class="ui-input filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500"></td>
                                <td class="px-1 py-1"><input type="text" data-name="filter_customer" placeholder="Cari..." class="ui-input filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500"></td>
                                <td class="px-1 py-1"><input type="text" data-name="filter_kasir" placeholder="Cari..." class="ui-input filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500"></td>
                                <td class="px-1 py-1"><input type="text" data-name="filter_pembayaran" placeholder="Cari..." class="ui-input filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500"></td>
                                <td class="px-1 py-1"><input type="text" data-name="filter_bulat" placeholder="Cari..." class="ui-input filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500 text-right"></td>
                                <td class="px-1 py-1"><input type="text" data-name="filter_total" placeholder="Cari..." class="ui-input filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500 text-right"></td>
                            </tr>
                        @endif
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @if(($viewMode ?? 'ringkas') === 'detail')
                            @forelse($detailRows as $row)
                                <tr class="group hover:bg-slate-50/80 transition-colors">
                                    <td class="px-4 py-2.5 whitespace-nowrap text-[11px] font-normal text-slate-800">{{ $row->transaction_number }}</td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-[11px] text-slate-600">{{ \Carbon\Carbon::parse($row->sale_datetime ?? $row->sale_date)->format('d M Y H:i') }}</td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-[11px] text-slate-600">{{ $row->outlet_name }}</td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-[11px] text-slate-600">{{ $row->customer_name ?? 'Walk-in' }}</td>
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
                                    <td class="px-4 py-2.5 whitespace-nowrap text-[11px] text-slate-500 capitalize">{{ str_replace('_', ' ', $row->metode_penjualan) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="13" class="px-6 py-16 text-center">
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
                                    <td class="px-4 py-2.5 whitespace-nowrap text-[11px] font-normal text-indigo-600">
                                        <a href="#" class="hover:text-indigo-800 hover:underline transition-colors cursor-pointer" data-sale-detail-id="{{ $sale->id }}">{{ $sale->invoice_number }}</a>
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-[11px] text-slate-500">
                                        {{ $sale->outlet->name }}
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-[11px] text-slate-500">
                                        {{ $sale->resolved_customer_name }}
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
                                    <td colspan="8" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center justify-center opacity-40">
                                            <i class="fas fa-file-invoice-dollar text-4xl mb-4 text-slate-300"></i>
                                            <p class="text-[11px] font-normal text-slate-500 italic">Tidak ada transaksi pada periode ini</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        @endif
                    </tbody>
                    @if(($viewMode ?? 'ringkas') !== 'detail' && ($summary['total_transactions'] ?? 0) > 0)
                        <tfoot class="bg-indigo-50/30">
                            <tr>
                                <td colspan="7" class="px-4 py-3 text-right text-[10px] font-normal text-slate-600 uppercase tracking-wider">
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
            @php
                $activeRows = ($viewMode ?? 'ringkas') === 'detail' ? $detailRows : $sales;
            @endphp
            @if(method_exists($activeRows, 'links'))
                <div class="border-t border-slate-100 p-4">
                    <div class="mb-3 text-xs text-slate-500">
                        Data transaksi dipaginasi otomatis agar halaman tetap ringan saat filter besar.
                    </div>
                    {{ $activeRows->onEachSide(1)->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Modal Detail Penjualan --}}
    <div id="saleDetailModal" class="fixed inset-0 z-[9999] hidden">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" id="saleDetailOverlay"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="relative w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-2xl border border-slate-200 animate-in fade-in zoom-in-95 duration-300" id="saleDetailContent">
                {{-- Loading state --}}
                <div id="saleDetailLoading" class="flex flex-col items-center justify-center py-20">
                    <div class="h-10 w-10 animate-spin rounded-full border-4 border-slate-200 border-t-indigo-600"></div>
                    <span class="mt-4 text-sm text-slate-500">Memuat detail transaksi...</span>
                </div>
                {{-- Content (populated by JS) --}}
                <div id="saleDetailBody" class="hidden">
                    {{-- Header --}}
                    <div class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-200 bg-white px-6 py-4 rounded-t-2xl">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-800" id="sdm-invoice">-</h3>
                            <div class="flex items-center gap-3 mt-1">
                                <span class="text-xs text-slate-400" id="sdm-date">-</span>
                                <span class="rounded bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-600" id="sdm-salestype">-</span>
                                <span class="rounded px-2 py-0.5 text-[10px] font-medium" id="sdm-status">-</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" id="saleDetailPrint" class="flex h-8 items-center gap-2 px-3 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition-all text-[11px] font-medium shadow-sm hover:shadow-indigo-100">
                                <i class="fas fa-print"></i>
                                <span>Print Bill</span>
                            </button>
                            <button type="button" id="saleDetailClose" class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-400 hover:bg-slate-50 hover:text-slate-600 transition-colors">
                                <i class="fas fa-times text-sm"></i>
                            </button>
                        </div>
                    </div>
                    {{-- Info Cards --}}
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 px-6 py-4">
                        <div class="rounded-xl bg-slate-50 border border-slate-100 p-3">
                            <div class="text-[10px] font-medium uppercase tracking-widest text-slate-400">Outlet</div>
                            <div class="mt-1 text-sm font-medium text-slate-800" id="sdm-outlet">-</div>
                        </div>
                        <div class="rounded-xl bg-slate-50 border border-slate-100 p-3">
                            <div class="text-[10px] font-medium uppercase tracking-widest text-slate-400">Kasir</div>
                            <div class="mt-1 text-sm font-medium text-slate-800" id="sdm-cashier">-</div>
                        </div>
                        <div class="rounded-xl bg-slate-50 border border-slate-100 p-3">
                            <div class="text-[10px] font-medium uppercase tracking-widest text-slate-400">Pelanggan</div>
                            <div class="mt-1 text-sm font-medium text-slate-800" id="sdm-customer">-</div>
                        </div>
                        <div class="rounded-xl bg-slate-50 border border-slate-100 p-3">
                            <div class="text-[10px] font-medium uppercase tracking-widest text-slate-400">Promo / Voucher</div>
                            <div class="mt-1 text-sm font-medium text-slate-800" id="sdm-promo">-</div>
                        </div>
                    </div>
                    {{-- Items Table --}}
                    <div class="px-6 pb-3">
                        <h4 class="text-xs font-medium uppercase tracking-widest text-slate-400 mb-3">Detail Item</h4>
                        <div class="overflow-x-auto rounded-xl border border-slate-200">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50 text-left text-[11px] font-medium uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th class="px-4 py-2.5">Produk</th>
                                        <th class="px-4 py-2.5 text-right">Qty</th>
                                        <th class="px-4 py-2.5 text-right">Harga</th>
                                        <th class="px-4 py-2.5 text-right">Diskon</th>
                                        <th class="px-4 py-2.5 text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100" id="sdm-items"></tbody>
                            </table>
                        </div>
                    </div>
                    {{-- Summary + Payments --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 px-6 pb-4">
                        <div>
                            <h4 class="text-xs font-medium uppercase tracking-widest text-slate-400 mb-3">Pembayaran</h4>
                            <div class="rounded-xl border border-slate-200 divide-y divide-slate-100" id="sdm-payments"></div>
                        </div>
                        <div>
                            <h4 class="text-xs font-medium uppercase tracking-widest text-slate-400 mb-3">Ringkasan</h4>
                            <div class="rounded-xl bg-slate-50 border border-slate-100 p-4 space-y-2">
                                <div class="flex justify-between text-sm"><span class="text-slate-500">Nilai Jual (Gross)</span><span class="font-medium text-slate-800" id="sdm-gross">-</span></div>
                                <div class="flex justify-between text-sm"><span class="text-slate-500">Diskon Item</span><span class="font-medium text-rose-500" id="sdm-item-disc">-</span></div>
                                <div class="flex justify-between text-sm"><span class="text-slate-500">Diskon Header</span><span class="font-medium text-rose-500" id="sdm-header-disc">-</span></div>
                                <div class="flex justify-between text-sm border-t border-slate-200 pt-2"><span class="text-slate-500">Subtotal</span><span class="font-medium text-slate-800" id="sdm-subtotal">-</span></div>
                                <div class="flex justify-between text-sm"><span class="text-slate-500">Pajak</span><span class="font-medium text-slate-800" id="sdm-tax">-</span></div>
                                <div class="flex justify-between text-sm"><span class="text-slate-500">Service Charge</span><span class="font-medium text-slate-800" id="sdm-sc">-</span></div>
                                <div class="flex justify-between text-sm"><span class="text-slate-500">Pembulatan</span><span class="font-medium text-slate-800" id="sdm-rounding">-</span></div>
                                <div class="flex justify-between border-t border-slate-200 pt-2"><span class="text-sm font-semibold uppercase tracking-wider text-slate-800">Total</span><span class="text-lg font-bold text-indigo-600" id="sdm-total">-</span></div>
                            </div>
                        </div>
                    </div>
                    {{-- Notes --}}
                    <div id="sdm-notes-section" class="hidden px-6 pb-6">
                        <div class="rounded-xl bg-amber-50 border border-amber-200 p-3">
                            <div class="text-[10px] font-medium uppercase tracking-widest text-amber-600 mb-1">Catatan</div>
                            <div class="text-sm text-amber-800" id="sdm-notes">-</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
        <style>
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
            body { background: white; }
            aside, header { display: none !important; }
            main { padding: 0 !important; }
        }
        /* Flatpickr custom style */
        .flatpickr-calendar {
            border-radius: 1rem;
            box-shadow: 0 20px 60px -10px rgba(0,0,0,0.15);
            border: 1px solid #e2e8f0;
            font-family: inherit;
        }
        .flatpickr-day.selected, .flatpickr-day.startRange, .flatpickr-day.endRange,
        .flatpickr-day.selected:hover, .flatpickr-day.startRange:hover, .flatpickr-day.endRange:hover {
            background: #4f46e5;
            border-color: #4f46e5;
        }
        .flatpickr-day.inRange {
            background: #eef2ff;
            border-color: #eef2ff;
            box-shadow: -5px 0 0 #eef2ff, 5px 0 0 #eef2ff;
        }
        .flatpickr-day.today {
            border-color: #4f46e5;
        }
        .flatpickr-months .flatpickr-prev-month:hover svg,
        .flatpickr-months .flatpickr-next-month:hover svg {
            fill: #4f46e5;
        }
        </style>
    @endpush
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
        <style>
            .resize-handle {
                position: absolute;
                top: 0;
                right: 0;
                width: 4px;
                height: 100%;
                cursor: col-resize;
                z-index: 10;
            }

            .resize-handle:hover {
                background: rgba(99, 102, 241, 0.5);
            }

            .resizing {
                cursor: col-resize;
                user-select: none;
            }
        </style>
        <script>
            // Column resizing logic
            const table = document.querySelector('table');
            if (table) {
                const headers = table.querySelectorAll('th.resizable');
                headers.forEach(th => {
                    const handle = th.querySelector('.resize-handle');
                    if (handle) {
                        let startX, startWidth;
                        handle.addEventListener('mousedown', (e) => {
                            startX = e.pageX; startWidth = th.offsetWidth;
                            document.body.classList.add('resizing');
                            const move = (e) => th.style.width = Math.max(50, startWidth + (e.pageX - startX)) + 'px';
                            const up = () => {
                                document.body.classList.remove('resizing');
                                document.removeEventListener('mousemove', move);
                                document.removeEventListener('mouseup', up);
                            };
                            document.addEventListener('mousemove', move);
                            document.addEventListener('mouseup', up);
                        });
                    }
                });
            }

            // Filtering logic via Input
            const filterInputs = document.querySelectorAll('.filter-input');
            const clearBtn = document.getElementById('clearFilters');

            function updateFilter(name, value) {
                const url = new URL(window.location.href);
                if (value.trim()) url.searchParams.set(name, value.trim());
                else url.searchParams.delete(name);
                window.location.href = url.toString();
            }

            let timer;
            filterInputs.forEach(input => {
                const name = input.dataset.name;
                input.addEventListener('input', (e) => {
                    clearTimeout(timer);
                    timer = setTimeout(() => updateFilter(name, e.target.value), 600);
                });
            });

            if (clearBtn) {
                clearBtn.addEventListener('click', () => {
                    const url = new URL(window.location.href);
                    const keysToDelete = [];
                    url.searchParams.forEach((val, key) => {
                        if(key.startsWith('filter_')) keysToDelete.push(key);
                    });
                    keysToDelete.forEach(key => url.searchParams.delete(key));
                    window.location.href = url.toString();
                });
            }

            // Preserve URL params in inputs on load
            const params = new URLSearchParams(window.location.search);
            filterInputs.forEach(input => {
                if (params.has(input.dataset.name)) input.value = params.get(input.dataset.name);
            });

            (() => {
                const wrap = document.getElementById('salesOutletFilterWrap');
                const btn = document.getElementById('salesOutletDropdownBtn');
                const label = document.getElementById('salesOutletDropdownLabel');
                const menu = document.getElementById('salesOutletDropdownMenu');
                const allCheckbox = document.getElementById('salesOutletAllCheckbox');
                const itemCheckboxes = Array.from(document.querySelectorAll('.sales-outlet-checkbox'));

                if (!wrap) return;

                const updateLabel = () => {
                    const checkedItems = itemCheckboxes.filter(c => c.checked);
                    if (checkedItems.length === itemCheckboxes.length) {
                        allCheckbox.checked = true;
                        label.textContent = 'Semua Outlet';
                    } else if (checkedItems.length === 0) {
                        allCheckbox.checked = false;
                        label.textContent = 'Semua Outlet';
                    } else if (checkedItems.length === 1) {
                        allCheckbox.checked = false;
                        label.textContent = checkedItems[0].parentElement.textContent.trim();
                    } else {
                        allCheckbox.checked = false;
                        label.textContent = `${checkedItems.length} Outlet`;
                    }
                };

                btn.addEventListener('click', () => menu.classList.toggle('hidden'));
                document.addEventListener('click', e => {
                    if (!wrap.contains(e.target)) menu.classList.add('hidden');
                });

                allCheckbox.addEventListener('change', () => {
                    itemCheckboxes.forEach(c => c.checked = allCheckbox.checked);
                    updateLabel();
                });

                itemCheckboxes.forEach(c => {
                    c.addEventListener('change', updateLabel);
                });

                updateLabel();
            })();

            // Sale Detail Modal
            (function() {
                const modal = document.getElementById('saleDetailModal');
                if (!modal) return;
                const overlay = document.getElementById('saleDetailOverlay');
                const closeBtn = document.getElementById('saleDetailClose');
                const printBtn = document.getElementById('saleDetailPrint');
                const loadingEl = document.getElementById('saleDetailLoading');
                const bodyEl = document.getElementById('saleDetailBody');

                let currentSaleId = null;

                function fmt(n) {
                    return 'Rp ' + Number(n || 0).toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 0});
                }

                function openModal(saleId) {
                    currentSaleId = saleId;
                    modal.classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                    loadingEl.classList.remove('hidden');
                    loadingEl.innerHTML = '<div class="h-10 w-10 animate-spin rounded-full border-4 border-slate-200 border-t-indigo-600"></div><span class="mt-4 text-sm text-slate-500">Memuat detail transaksi...</span>';
                    bodyEl.classList.add('hidden');

                    const url = '{{ url("/admin/reports/catalog/sale") }}/' + saleId;
                    fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(r => { if (!r.ok) throw new Error('Network error'); return r.json(); })
                        .then(data => {
                            document.getElementById('sdm-invoice').textContent = data.invoice_number;
                            document.getElementById('sdm-date').textContent = data.sale_date;
                            document.getElementById('sdm-salestype').textContent = data.sales_type_label;
                            const statusEl = document.getElementById('sdm-status');
                            statusEl.textContent = data.status === 'completed' ? 'Selesai' : data.status;
                            statusEl.className = 'rounded px-2 py-0.5 text-[10px] font-medium ' + (data.status === 'completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700');
                            document.getElementById('sdm-outlet').textContent = data.outlet_name;
                            document.getElementById('sdm-cashier').textContent = data.cashier_name;
                            document.getElementById('sdm-customer').textContent = data.customer_name;
                            let promoText = '-';
                            if (data.promotion_name) promoText = data.promotion_name;
                            else if (data.voucher_name) promoText = data.voucher_name;
                            else if (data.voucher_code) promoText = data.voucher_code;
                            document.getElementById('sdm-promo').textContent = promoText;
                            const itemsBody = document.getElementById('sdm-items');
                            itemsBody.innerHTML = '';
                            (data.items || []).forEach(function(item) {
                                const tr = document.createElement('tr');
                                tr.innerHTML = '<td class="px-4 py-2.5"><div class="font-medium text-slate-800">' + (item.product_name || '-') + '</div>' + (item.product_sku ? '<div class="text-[10px] text-slate-400">' + item.product_sku + '</div>' : '') + (item.notes ? '<div class="text-[10px] text-amber-600 mt-0.5"><i class="fas fa-sticky-note mr-1"></i>' + item.notes + '</div>' : '') + '</td><td class="px-4 py-2.5 text-right text-slate-700">' + Number(item.quantity).toLocaleString('id-ID') + '</td><td class="px-4 py-2.5 text-right text-slate-700">' + fmt(item.original_price) + '</td><td class="px-4 py-2.5 text-right text-rose-500">' + (item.discount_amount > 0 ? fmt(item.discount_amount) : '-') + '</td><td class="px-4 py-2.5 text-right font-medium text-slate-800">' + fmt(item.subtotal) + '</td>';
                                itemsBody.appendChild(tr);
                            });
                            const paymentsEl = document.getElementById('sdm-payments');
                            paymentsEl.innerHTML = '';
                            (data.payments || []).forEach(function(p) {
                                const div = document.createElement('div');
                                div.className = 'flex items-center justify-between px-4 py-3';
                                div.innerHTML = '<div><div class="text-sm font-medium text-slate-800">' + p.method + '</div>' + (p.reference_number ? '<div class="text-[10px] text-slate-400">' + p.reference_number + '</div>' : '') + '</div><span class="text-sm font-medium text-slate-800">' + fmt(p.amount) + '</span>';
                                paymentsEl.appendChild(div);
                            });
                            if (!data.payments || data.payments.length === 0) {
                                paymentsEl.innerHTML = '<div class="px-4 py-3 text-center text-sm text-slate-400 italic">Tidak ada data pembayaran</div>';
                            }
                            const setSummaryRow = (id, val) => {
                                const el = document.getElementById(id);
                                if (!el) return;
                                el.textContent = fmt(val);
                                // Sembunyikan baris jika nilai 0, kecuali Total & Subtotal (untuk anchor visual)
                                if (['sdm-total', 'sdm-subtotal', 'sdm-gross'].includes(id)) {
                                    el.parentElement.classList.remove('hidden');
                                } else {
                                    el.parentElement.classList.toggle('hidden', Number(val) === 0);
                                }
                            };

                            setSummaryRow('sdm-gross', data.gross_value);
                            setSummaryRow('sdm-item-disc', data.item_level_discount);
                            setSummaryRow('sdm-header-disc', data.header_discount);
                            setSummaryRow('sdm-subtotal', data.subtotal);
                            setSummaryRow('sdm-tax', data.tax_amount);
                            setSummaryRow('sdm-sc', data.service_charge_amount);
                            setSummaryRow('sdm-rounding', data.rounding_amount);
                            setSummaryRow('sdm-total', data.total_amount);
                            const notesSection = document.getElementById('sdm-notes-section');
                            if (data.notes && data.notes.trim() !== '') {
                                document.getElementById('sdm-notes').textContent = data.notes;
                                notesSection.classList.remove('hidden');
                            } else { notesSection.classList.add('hidden'); }
                            loadingEl.classList.add('hidden');
                            bodyEl.classList.remove('hidden');
                        })
                        .catch(function() {
                            loadingEl.innerHTML = '<div class="text-center py-20"><i class="fas fa-exclamation-circle text-3xl text-rose-400 mb-3"></i><div class="text-sm text-slate-500">Gagal memuat data transaksi</div></div>';
                        });
                }

                function closeModal() {
                    modal.classList.add('hidden');
                    document.body.style.overflow = '';
                    currentSaleId = null;
                }

                if (printBtn) {
                    printBtn.addEventListener('click', function() {
                        if (!currentSaleId) return;
                        const url = '{{ url("/admin/reports/catalog/sale") }}/' + currentSaleId + '/print?autoprint=1';
                        window.open(url, 'ReceiptPrint', 'width=400,height=600,scrollbars=yes');
                    });
                }

                document.addEventListener('click', function(e) {
                    const link = e.target.closest('[data-sale-detail-id]');
                    if (link) { e.preventDefault(); openModal(link.dataset.saleDetailId); }
                });
                if (overlay) overlay.addEventListener('click', closeModal);
                if (closeBtn) closeBtn.addEventListener('click', closeModal);
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
                });
            })();

            // ── Flatpickr Date Range Picker ──
            (function() {
                const pickerEl = document.getElementById('salesDateRangePicker');
                const fromInput = document.getElementById('salesDateFrom');
                const toInput   = document.getElementById('salesDateTo');
                if (!pickerEl) return;

                // Format tanggal untuk display (dd/mm/yyyy)
                function fmtDisplay(dateStr) {
                    if (!dateStr) return '';
                    const [y, m, d] = dateStr.split('-');
                    return d + '/' + m + '/' + y;
                }

                // Set initial display value
                const fromVal = fromInput.value;
                const toVal   = toInput.value;
                if (fromVal && toVal) {
                    pickerEl.value = fmtDisplay(fromVal) + ' — ' + fmtDisplay(toVal);
                } else if (fromVal) {
                    pickerEl.value = fmtDisplay(fromVal);
                }

                flatpickr(pickerEl, {
                    mode: 'range',
                    dateFormat: 'Y-m-d',
                    locale: {
                        rangeSeparator: ' — ',
                        firstDayOfWeek: 1,
                        weekdays: {
                            shorthand: ['Min','Sen','Sel','Rab','Kam','Jum','Sab'],
                            longhand: ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu']
                        },
                        months: {
                            shorthand: ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'],
                            longhand: ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember']
                        }
                    },
                    defaultDate: (fromVal && toVal) ? [fromVal, toVal] : (fromVal ? [fromVal] : null),
                    showMonths: 2,
                    onReady: function(selectedDates, dateStr, instance) {
                        // Override displayed text with dd/mm/yyyy format
                        if (selectedDates.length === 2) {
                            instance.element.value = fmtDisplay(instance.formatDate(selectedDates[0], 'Y-m-d')) + ' — ' + fmtDisplay(instance.formatDate(selectedDates[1], 'Y-m-d'));
                        }
                    },
                    onChange: function(selectedDates, dateStr, instance) {
                        if (selectedDates.length === 2) {
                            const d0 = instance.formatDate(selectedDates[0], 'Y-m-d');
                            const d1 = instance.formatDate(selectedDates[1], 'Y-m-d');
                            fromInput.value = d0;
                            toInput.value   = d1;
                            instance.element.value = fmtDisplay(d0) + ' — ' + fmtDisplay(d1);
                        } else if (selectedDates.length === 1) {
                            const d0 = instance.formatDate(selectedDates[0], 'Y-m-d');
                            fromInput.value = d0;
                            toInput.value   = '';
                        }
                    }
                });
            })();
        </script>
    @endpush
@endsection
