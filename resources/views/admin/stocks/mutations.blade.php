@extends('layouts.admin')

@section('title', 'History Mutasi Stok')
@section('page-title', 'History Mutasi Stok')
@section('page-subtitle', 'Audit trail dan riwayat perubahan stok produk')

@section('content')
    <div class="w-full animate-in fade-in slide-in-from-bottom-2 duration-500">
        {{-- Header Section --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-normal text-slate-800 tracking-tight">History Mutasi Stok</h1>
                <p class="text-xs font-normal text-slate-500 mt-0.5">Audit trail dan riwayat perubahan stok produk</p>
            </div>
            <div class="flex items-center gap-3 no-print">
                <a href="{{ route('admin.stocks.index') }}"
                    class="ui-btn ui-btn-ghost inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-xs font-normal text-slate-700 border border-slate-200 shadow-sm transition-all hover:bg-slate-50 active:scale-95">
                    <i class="fas fa-arrow-left text-[10px]"></i>
                    <span>Kembali ke Stok</span>
                </a>
            </div>
        </div>

        {{-- Tabs Navigation --}}
        <div class="flex items-center gap-2 mb-6 border-b border-slate-200 no-print">
            <a href="{{ route('admin.stocks.mutations', ['tab' => 'all']) }}" 
               class="px-4 py-2.5 text-xs font-medium transition-all border-b-2 {{ $tab === 'all' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                <i class="fas fa-history mr-1.5 {{ $tab === 'all' ? 'text-indigo-500' : 'text-slate-400' }}"></i>
                Semua History Mutasi
            </a>
            <a href="{{ route('admin.stocks.mutations', ['tab' => 'usage']) }}" 
               class="px-4 py-2.5 text-xs font-medium transition-all border-b-2 {{ $tab === 'usage' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                <i class="fas fa-utensils mr-1.5 {{ $tab === 'usage' ? 'text-indigo-500' : 'text-slate-400' }}"></i>
                Pemakaian Bahan Baku
            </a>
        </div>

        {{-- Filter Section --}}
        <div class="ui-card bg-white rounded-2xl shadow-sm border border-slate-200 mb-6 no-print">
            <div class="p-4 border-b border-slate-100 bg-slate-50/50 rounded-t-2xl">
                <div class="flex items-center gap-2">
                    <i class="fas fa-filter text-indigo-500 text-[10px]"></i>
                    <h3 class="text-[10px] font-normal text-slate-400 uppercase tracking-widest leading-none">Filter Audit
                        Trail</h3>
                </div>
            </div>
            <div class="p-5">
                <form method="GET" action="{{ route('admin.stocks.mutations') }}" class="space-y-4">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    @if(request()->filled('reference_scope'))
                        <input type="hidden" name="reference_scope" value="{{ request('reference_scope') }}">
                    @endif
                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                        @if($tab === 'all')
                        <div class="flex flex-col gap-1.5">
                            <label
                                class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Outlet</label>
                            <select name="outlet_id"
                                class="ui-input w-full px-3 py-1.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                <option value="">Semua Outlet</option>
                                @foreach($outlets as $outlet)
                                    <option value="{{ $outlet->id }}" {{ request('outlet_id') == $outlet->id ? 'selected' : '' }}>
                                        {{ $outlet->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Tipe
                                Mutasi</label>
                            <select name="mutation_type"
                                class="ui-input w-full px-3 py-1.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                <option value="">Semua Tipe</option>
                                <option value="in" {{ request('mutation_type') == 'in' ? 'selected' : '' }}>Masuk (In)
                                </option>
                                <option value="out" {{ request('mutation_type') == 'out' ? 'selected' : '' }}>Keluar (Out)
                                </option>
                                <option value="adjustment" {{ request('mutation_type') == 'adjustment' ? 'selected' : '' }}>
                                    Adjustment</option>
                                <option value="transfer_in" {{ request('mutation_type') == 'transfer_in' ? 'selected' : '' }}>
                                    Transfer In</option>
                                <option value="transfer_out" {{ request('mutation_type') == 'transfer_out' ? 'selected' : '' }}>Transfer Out</option>
                            </select>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label
                                class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Referensi</label>
                            <select name="reference_type"
                                class="ui-input w-full px-3 py-1.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                <option value="">Semua</option>
                                <option value="purchase" {{ request('reference_type') == 'purchase' ? 'selected' : '' }}>
                                    Pembelian</option>
                                <option value="sale" {{ request('reference_type') == 'sale' ? 'selected' : '' }}>Penjualan
                                </option>
                                <option value="stock_opname" {{ request('reference_type') == 'stock_opname' ? 'selected' : '' }}>Opname</option>
                                <option value="stock_transfer" {{ request('reference_type') == 'stock_transfer' ? 'selected' : '' }}>Transfer</option>
                            </select>
                        </div>
                        @endif

                        @if($tab === 'usage')
                        <div class="flex flex-col gap-1.5 md:col-span-2">
                            <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Bahan Baku (Pilih Salah Satu)</label>
                            <select name="product_id" required
                                class="tom-select ui-input w-full px-3 py-1.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                <option value="">-- Pilih Bahan Baku --</option>
                                @foreach($products as $prod)
                                    @if($prod->product_type === 'raw_material' || $prod->product_type === 'finished_good')
                                        <option value="{{ $prod->id }}" {{ request('product_id') == $prod->id ? 'selected' : '' }}>
                                            {{ $prod->name }} ({{ $prod->sku ?? 'NO-SKU' }})
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        @else
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Dari Tanggal</label>
                            <input type="date" name="start_date" value="{{ request('start_date') }}" class="ui-input w-full px-3 py-1.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                        </div>
                        
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Sampai Tanggal</label>
                            <input type="date" name="end_date" value="{{ request('end_date') }}" class="ui-input w-full px-3 py-1.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                        </div>
                        
                        <div class="flex items-end gap-2">
                            <button type="submit" class="ui-btn ui-btn-primary flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-normal text-white shadow-sm transition-all hover:bg-indigo-700 active:scale-95">
                                <i class="fas fa-search text-[10px]"></i>
                                <span>Filter</span>
                            </button>
                            <a href="{{ route('admin.stocks.mutations', ['tab' => $tab]) }}" class="ui-btn ui-btn-ghost inline-flex items-center justify-center h-[34px] w-[34px] rounded-lg bg-white border border-slate-200 text-slate-400 hover:text-indigo-600 hover:border-indigo-100 transition-all active:scale-95">
                                <i class="fas fa-redo text-[10px]"></i>
                            </a>
                        </div>
                        @endif
                        
                        @if($tab === 'usage')
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Dari Tanggal</label>
                            <input type="date" name="start_date" value="{{ request('start_date') }}" class="ui-input w-full px-3 py-1.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                        </div>
                        
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Sampai Tanggal</label>
                            <input type="date" name="end_date" value="{{ request('end_date') }}" class="ui-input w-full px-3 py-1.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                        </div>
                        
                        <div class="flex items-end gap-2 md:col-span-2">
                            <button type="submit" class="ui-btn ui-btn-primary flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-normal text-white shadow-sm transition-all hover:bg-indigo-700 active:scale-95">
                                <i class="fas fa-search text-[10px]"></i>
                                <span>Tampilkan Pemakaian</span>
                            </button>
                            <a href="{{ route('admin.stocks.mutations', ['tab' => $tab]) }}" class="ui-btn ui-btn-ghost inline-flex items-center justify-center h-[34px] w-[34px] rounded-lg bg-white border border-slate-200 text-slate-400 hover:text-indigo-600 hover:border-indigo-100 transition-all active:scale-95">
                                <i class="fas fa-redo text-[10px]"></i>
                            </a>
                        </div>
                        @endif
                    </div>

                    @if($tab === 'all')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Cari
                                Produk</label>
                            <input type="text" name="search" value="{{ request('search') }}"
                                placeholder="Cari berdasarkan nama produk atau SKU..."
                                class="ui-input w-full px-4 py-2 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Reference ID</label>
                            <input type="number" name="reference_id" value="{{ request('reference_id') }}"
                                placeholder="Contoh: 12345"
                                class="ui-input w-full px-4 py-2 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm">
                        </div>
                    </div>
                    @endif
                </form>
            </div>
        </div>

        @if(request('reference_scope') === 'sales_cogs')
            <div class="mb-4 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-xs text-indigo-700">
                Mode audit HPP aktif: menampilkan mutasi referensi <code>sale</code> dan <code>sale_cancellation</code>.
            </div>
        @endif

        {{-- Mutations Table --}}
        <div class="ui-card bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-8" data-stock-mutation-table>
            <div class="overflow-x-auto">
                <table class="ui-table min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/80 backdrop-blur-sm sticky top-0 z-10">
                        <tr>
                            @if($tab === 'usage')
                            <th class="px-5 py-3 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500">No Invoice</th>
                            <th class="px-5 py-3 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500">Tanggal & Waktu</th>
                            <th class="px-5 py-3 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500">Outlet</th>
                            <th class="px-5 py-3 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500">Menu Terjual</th>
                            <th class="px-5 py-3 text-right text-[9px] font-normal uppercase tracking-widest text-slate-500">Qty Terpakai</th>
                            <th class="px-5 py-3 text-right text-[9px] font-normal uppercase tracking-widest text-slate-500">HPP/Unit</th>
                            <th class="px-5 py-3 text-right text-[9px] font-normal uppercase tracking-widest text-slate-500">Nominal HPP</th>
                            <th class="px-5 py-3 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500">Kasir</th>
                            @else
                            <th class="px-5 py-3 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500">Tanggal & Waktu</th>
                            <th class="px-5 py-3 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500">Produk</th>
                            <th class="px-5 py-3 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500">Outlet</th>
                            <th class="px-5 py-3 text-center text-[9px] font-normal uppercase tracking-widest text-slate-500">Tipe</th>
                            <th class="px-5 py-3 text-right text-[9px] font-normal uppercase tracking-widest text-slate-500">Qty Mutasi</th>
                            <th class="px-5 py-3 text-right text-[9px] font-normal uppercase tracking-widest text-slate-500">Stok Akhir</th>
                            <th class="px-5 py-3 text-right text-[9px] font-normal uppercase tracking-widest text-slate-500">HPP/Unit</th>
                            <th class="px-5 py-3 text-right text-[9px] font-normal uppercase tracking-widest text-slate-500">Nominal HPP</th>
                            <th class="px-5 py-3 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500">Referensi & Catatan</th>
                            @endif
                        </tr>
                        @if($tab === 'usage')
                            <tr class="bg-white border-b border-slate-100 no-print">
                                <td class="px-1 py-1 relative">
                                    <button type="button" id="clearMutationTableFilters" title="Reset filter tabel" class="absolute left-1 top-1 h-6 w-6 inline-flex items-center justify-center rounded bg-slate-50 text-slate-400 hover:text-rose-500 transition-all z-10">
                                        <i class="fas fa-times text-[10px]"></i>
                                    </button>
                                    <input type="text" data-name="filter_invoice" name="filter_invoice" value="{{ request('filter_invoice') }}" placeholder="Cari..." class="ui-input filter-input w-full pl-7 pr-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500">
                                </td>
                                <td class="px-1 py-1"><input type="text" data-name="filter_tanggal" name="filter_tanggal" value="{{ request('filter_tanggal') }}" placeholder="Cari..." class="ui-input filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500"></td>
                                <td class="px-1 py-1"><input type="text" data-name="filter_outlet" name="filter_outlet" value="{{ request('filter_outlet') }}" placeholder="Cari..." class="ui-input filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500"></td>
                                <td class="px-1 py-1"><input type="text" data-name="filter_menu" name="filter_menu" value="{{ request('filter_menu') }}" placeholder="Cari..." class="ui-input filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500"></td>
                                <td class="px-1 py-1"><input type="text" data-name="filter_qty" name="filter_qty" value="{{ request('filter_qty') }}" placeholder="Cari..." class="ui-input filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500 text-right"></td>
                                <td class="px-1 py-1"><input type="text" data-name="filter_hpp_unit" name="filter_hpp_unit" value="{{ request('filter_hpp_unit') }}" placeholder="Cari..." class="ui-input filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500 text-right"></td>
                                <td class="px-1 py-1"><input type="text" data-name="filter_hpp_nominal" name="filter_hpp_nominal" value="{{ request('filter_hpp_nominal') }}" placeholder="Cari..." class="ui-input filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500 text-right"></td>
                                <td class="px-1 py-1"><input type="text" data-name="filter_kasir" name="filter_kasir" value="{{ request('filter_kasir') }}" placeholder="Cari..." class="ui-input filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500"></td>
                            </tr>
                        @else
                            <tr class="bg-white border-b border-slate-100 no-print">
                                <td class="px-1 py-1 relative">
                                    <button type="button" id="clearMutationTableFilters" title="Reset filter tabel" class="absolute left-1 top-1 h-6 w-6 inline-flex items-center justify-center rounded bg-slate-50 text-slate-400 hover:text-rose-500 transition-all z-10">
                                        <i class="fas fa-times text-[10px]"></i>
                                    </button>
                                    <input type="text" data-name="filter_tanggal" name="filter_tanggal" value="{{ request('filter_tanggal') }}" placeholder="Cari..." class="ui-input filter-input w-full pl-7 pr-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500">
                                </td>
                                <td class="px-1 py-1"><input type="text" data-name="filter_produk" name="filter_produk" value="{{ request('filter_produk') }}" placeholder="Cari..." class="ui-input filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500"></td>
                                <td class="px-1 py-1"><input type="text" data-name="filter_outlet" name="filter_outlet" value="{{ request('filter_outlet') }}" placeholder="Cari..." class="ui-input filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500"></td>
                                <td class="px-1 py-1"><input type="text" data-name="filter_tipe" name="filter_tipe" value="{{ request('filter_tipe') }}" placeholder="Cari..." class="ui-input filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500 text-center"></td>
                                <td class="px-1 py-1"><input type="text" data-name="filter_qty" name="filter_qty" value="{{ request('filter_qty') }}" placeholder="Cari..." class="ui-input filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500 text-right"></td>
                                <td class="px-1 py-1"><input type="text" data-name="filter_stok_akhir" name="filter_stok_akhir" value="{{ request('filter_stok_akhir') }}" placeholder="Cari..." class="ui-input filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500 text-right"></td>
                                <td class="px-1 py-1"><input type="text" data-name="filter_hpp_unit" name="filter_hpp_unit" value="{{ request('filter_hpp_unit') }}" placeholder="Cari..." class="ui-input filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500 text-right"></td>
                                <td class="px-1 py-1"><input type="text" data-name="filter_hpp_nominal" name="filter_hpp_nominal" value="{{ request('filter_hpp_nominal') }}" placeholder="Cari..." class="ui-input filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500 text-right"></td>
                                <td class="px-1 py-1"><input type="text" data-name="filter_referensi" name="filter_referensi" value="{{ request('filter_referensi') }}" placeholder="Cari..." class="ui-input filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500"></td>
                            </tr>
                        @endif
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($mutations as $mutation)
                            <tr class="group hover:bg-slate-50/50 transition-colors">
                                @if($tab === 'usage')
                                <td class="px-5 py-3.5">
                                    <div class="flex flex-col">
                                        <a href="{{ route('admin.reports.sales.index', ['search' => $mutation->invoice_number]) }}" class="text-[11.5px] font-medium text-indigo-600 hover:text-indigo-800 hover:underline leading-tight">
                                            {{ $mutation->invoice_number ?? ('#' . $mutation->reference_id) }}
                                        </a>
                                        <span class="text-[9px] font-normal text-slate-400 mt-1 uppercase tracking-widest">
                                            {{ $mutation->reference_type === 'sale_cancellation' ? 'REFUND/BATAL' : 'SALE' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex flex-col">
                                        <span class="text-[11px] font-normal text-slate-700 leading-tight">{{ $mutation->mutation_date->format('d M Y') }}</span>
                                        <span class="text-[9px] font-normal text-slate-400 mt-1 uppercase tracking-widest">{{ $mutation->created_at->format('H:i') }} WIB</span>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="text-[11px] font-normal text-slate-600 tracking-tight">{{ $mutation->outlet->name }}</span>
                                </td>
                                <td class="px-5 py-3.5">
                                    @php
                                        // Ekstrak nama menu dari notes "Penjualan: Tori miso ramen" -> "Tori miso ramen"
                                        $menuName = $mutation->notes;
                                        if (str_starts_with($menuName, 'Penjualan: ')) {
                                            $menuName = substr($menuName, 11);
                                        } elseif (str_starts_with($menuName, 'Batal Jual (Menu: ')) {
                                            $menuName = explode(')', substr($menuName, 18))[0] ?? $menuName;
                                            $menuName = '<span class="text-rose-500 block">BATAL: ' . $menuName . '</span>';
                                        }
                                    @endphp
                                    <span class="text-[11px] font-medium text-slate-800 tracking-tight">{!! $menuName !!}</span>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <span class="text-[11.5px] font-bold {{ $mutation->quantity < 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                        {{ number_format(abs($mutation->quantity), 2, ',', '.') }}
                                    </span>
                                    <span class="text-[9px] font-normal text-slate-400 ml-0.5 uppercase tracking-widest">{{ $mutation->product->unit ?? 'pcs' }}</span>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    @php $unitCost = (float) ($mutation->unit_cost ?? 0); @endphp
                                    @if($unitCost > 0)
                                        <span class="text-[11.5px] font-normal text-slate-700">Rp {{ number_format($unitCost, 2, ',', '.') }}</span>
                                    @else
                                        <span class="text-[11px] font-normal text-slate-300">-</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    @php
                                        $nominalHpp = abs((float) ($mutation->total_cost ?? 0));
                                        $nominalClass = ((float) $mutation->quantity < 0) ? 'text-rose-600' : 'text-emerald-600';
                                        $nominalSign = ((float) $mutation->quantity < 0) ? '-' : '+';
                                    @endphp
                                    @if($nominalHpp > 0)
                                        <span class="text-[11.5px] font-normal {{ $nominalClass }}">{{ $nominalSign }}Rp {{ number_format($nominalHpp, 0, ',', '.') }}</span>
                                    @else
                                        <span class="text-[11px] font-normal text-slate-300">-</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="text-[10px] font-normal text-slate-600 uppercase tracking-widest">{{ $mutation->creator->name ?? '-' }}</span>
                                </td>
                                @else
                                <td class="px-5 py-3.5">
                                    <div class="flex flex-col">
                                        <span
                                            class="text-[11px] font-normal text-slate-700 leading-tight">{{ $mutation->mutation_date->format('d M Y') }}</span>
                                        <span
                                            class="text-[9px] font-normal text-slate-400 mt-1 uppercase tracking-widest">{{ $mutation->created_at->format('H:i') }}
                                            WIB</span>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex flex-col">
                                        <span
                                            class="text-[11.5px] font-normal text-slate-800 leading-tight">{{ $mutation->product->name }}</span>
                                        <span
                                            class="text-[9px] font-mono text-slate-400 mt-1 uppercase tracking-tighter bg-slate-100 px-1 rounded inline-block w-fit">{{ $mutation->product->sku ?? 'NO-SKU' }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span
                                        class="text-[11px] font-normal text-slate-600 tracking-tight">{{ $mutation->outlet->name }}</span>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    @php
                                        $typeStyles = [
                                            'in' => 'bg-emerald-50 text-emerald-600 ring-emerald-200',
                                            'out' => 'bg-rose-50 text-rose-600 ring-rose-200',
                                            'adjustment' => 'bg-amber-50 text-amber-600 ring-amber-200',
                                            'transfer_in' => 'bg-blue-50 text-blue-600 ring-blue-200',
                                            'transfer_out' => 'bg-purple-50 text-purple-600 ring-purple-200',
                                        ];
                                        $typeLabels = [
                                            'in' => 'MASUK',
                                            'out' => 'KELUAR',
                                            'adjustment' => 'ADJ',
                                            'transfer_in' => 'TRF IN',
                                            'transfer_out' => 'TRF OUT',
                                        ];
                                        $style = $typeStyles[$mutation->mutation_type] ?? 'bg-slate-50 text-slate-600 ring-slate-200';
                                        $label = $typeLabels[$mutation->mutation_type] ?? strtoupper($mutation->mutation_type);
                                    @endphp
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[8.5px] font-normal {{ $style }} ring-1 ring-inset">
                                        {{ $label }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <span
                                        class="text-[11.5px] font-normal {{ $mutation->quantity >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                        {{ $mutation->quantity >= 0 ? '+' : '' }}{{ number_format($mutation->quantity, 2, ',', '.') }}
                                    </span>
                                    <span
                                        class="text-[9px] font-normal text-slate-400 ml-0.5 uppercase tracking-widest">{{ $mutation->product->unit ?? 'pcs' }}</span>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <div class="flex flex-col items-end">
                                        <span
                                            class="text-[11.5px] font-normal text-slate-800">{{ number_format($mutation->stock_after, 2, ',', '.') }}</span>
                                        <span class="text-[8px] font-normal text-slate-400 italic">Prev:
                                            {{ number_format($mutation->stock_before, 2, ',', '.') }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    @php $unitCost = (float) ($mutation->unit_cost ?? 0); @endphp
                                    @if($unitCost > 0)
                                        <span class="text-[11.5px] font-normal text-slate-700">Rp {{ number_format($unitCost, 2, ',', '.') }}</span>
                                    @else
                                        <span class="text-[11px] font-normal text-slate-300">-</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    @php
                                        $nominalHpp = abs((float) ($mutation->total_cost ?? 0));
                                        $nominalClass = ((float) $mutation->quantity < 0) ? 'text-rose-600' : 'text-emerald-600';
                                        $nominalSign = ((float) $mutation->quantity < 0) ? '-' : '+';
                                    @endphp
                                    @if($nominalHpp > 0)
                                        <span class="text-[11.5px] font-normal {{ $nominalClass }}">{{ $nominalSign }}Rp {{ number_format($nominalHpp, 0, ',', '.') }}</span>
                                    @else
                                        <span class="text-[11px] font-normal text-slate-300">-</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex flex-col gap-0.5">
                                        <div class="flex items-center gap-1.5">
                                            <span
                                                class="text-[10px] font-normal text-slate-700 uppercase tracking-wider">{{ str_replace('_', ' ', $mutation->reference_type ?? 'Manual') }}</span>
                                            @if($mutation->reference_id)
                                                @php
                                                    $referenceAuditParams = array_filter([
                                                        'tab' => 'all',
                                                        'start_date' => request('start_date'),
                                                        'end_date' => request('end_date'),
                                                        'outlet_id' => request('outlet_id'),
                                                        'reference_scope' => request('reference_scope'),
                                                        'reference_id' => $mutation->reference_id,
                                                    ], fn($value) => !is_null($value) && $value !== '');
                                                @endphp
                                                <a href="{{ route('admin.stocks.mutations', $referenceAuditParams) }}"
                                                    class="text-[9px] font-mono text-indigo-500 bg-indigo-50 px-1 border border-indigo-100 rounded hover:bg-indigo-100">
                                                    #{{ $mutation->reference_id }}
                                                </a>
                                            @endif
                                        </div>
                                        @if($mutation->notes)
                                            <p class="text-[10px] font-normal text-slate-400 leading-normal">{{ $mutation->notes }}
                                            </p>
                                        @endif
                                        @if($mutation->creator)
                                            <span
                                                class="text-[8px] font-normal text-slate-400 uppercase tracking-widest mt-1 italic">BY:
                                                {{ $mutation->creator->name }}</span>
                                        @endif
                                    </div>
                                </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $tab === 'usage' ? 8 : 9 }}" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center justify-center opacity-40">
                                        <i class="fas fa-history text-4xl mb-4 text-slate-300"></i>
                                        <p class="text-[11px] font-normal text-slate-500 italic uppercase tracking-widest">Tidak
                                            ada riwayat mutasi / pemakaian ditemukan</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($mutations->hasPages())
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/30">
                    {{ $mutations->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <style>
        .ts-control {
            border-radius: 0.5rem;
            border-color: #e2e8f0;
            padding: 0.375rem 0.75rem;
            font-size: 11.5px;
            box-shadow: none;
            position: relative;
            z-index: 20; /* Lebih tinggi dari z-10 di header tabel */
        }
        .ts-control:focus {
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
            border-color: #6366f1;
        }
        .ts-dropdown {
            z-index: 30 !important; /* Memastikan melayang di atas segalanya */
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            margin-top: 4px;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (document.querySelector('.tom-select')) {
                new TomSelect('.tom-select', {
                    create: false,
                    sortField: {
                        field: "text",
                        direction: "asc"
                    },
                    placeholder: '-- Pilih Bahan Baku --'
                });
            }

            const tableFilterWrap = document.querySelector('[data-stock-mutation-table]');
            if (!tableFilterWrap) {
                return;
            }

            const filterInputs = tableFilterWrap.querySelectorAll('.filter-input');
            const clearBtn = document.getElementById('clearMutationTableFilters');

            const updateFilter = (name, value) => {
                const url = new URL(window.location.href);
                url.searchParams.delete('page');

                if (value.trim()) {
                    url.searchParams.set(name, value.trim());
                } else {
                    url.searchParams.delete(name);
                }

                window.location.href = url.toString();
            };

            let timer;
            filterInputs.forEach((input) => {
                input.addEventListener('input', (event) => {
                    clearTimeout(timer);
                    timer = setTimeout(() => updateFilter(input.dataset.name, event.target.value), 500);
                });
            });

            if (clearBtn) {
                clearBtn.addEventListener('click', () => {
                    const url = new URL(window.location.href);
                    url.searchParams.delete('page');

                    const keysToDelete = [];
                    url.searchParams.forEach((value, key) => {
                        if (key.startsWith('filter_')) {
                            keysToDelete.push(key);
                        }
                    });

                    keysToDelete.forEach((key) => url.searchParams.delete(key));
                    window.location.href = url.toString();
                });
            }
        });
    </script>
@endpush
