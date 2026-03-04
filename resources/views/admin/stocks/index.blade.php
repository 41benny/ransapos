@extends('layouts.admin')

@section('title', 'Manajemen Stok')
@section('page-title', 'Manajemen Stok')
@section('page-subtitle', 'Monitoring stok produk per outlet dan mutasi barang')

@section('content')
<div class="w-full animate-in fade-in slide-in-from-bottom-2 duration-500">
    {{-- Header Section --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-normal text-slate-800 tracking-tight">Manajemen Stok</h1>
            <p class="text-xs font-normal text-slate-500 mt-0.5">Monitoring stok produk per outlet dan mutasi barang</p>
        </div>
        <div class="flex items-center gap-3 no-print">
            <a href="{{ route('admin.stocks.adjustment') }}"
                class="ui-btn ui-btn-ghost inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-xs font-normal text-slate-700 border border-slate-200 shadow-sm transition-all hover:bg-slate-50 active:scale-95">
                <i class="fas fa-adjust text-xs text-indigo-500"></i>
                <span>Stock Adjustment</span>
            </a>
            <a href="{{ route('admin.stock-transfers.create') }}"
                class="ui-btn ui-btn-primary inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-normal text-white shadow-sm transition-all hover:bg-indigo-700 active:scale-95">
                <i class="fas fa-exchange-alt text-xs"></i>
                <span>Transfer Stok</span>
            </a>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Total Produk --}}
        <div class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all hover:shadow-md">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-normal uppercase tracking-[0.2em] text-slate-500">Total Produk</span>
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-50 text-slate-600 transition-colors group-hover:bg-indigo-600 group-hover:text-white">
                    <i class="fas fa-box text-xs"></i>
                </div>
            </div>
            <div class="flex flex-col">
                <h3 class="text-xl font-normal text-slate-800">{{ number_format($stats['total_products'], 0, ',', '.') }}</h3>
                <p class="text-xs font-normal text-slate-400 mt-0.5">Item Terdaftar</p>
            </div>
        </div>

        {{-- Nilai Stok --}}
        <div class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all hover:shadow-md border-l-4 border-l-emerald-500">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-normal uppercase tracking-[0.2em] text-emerald-500">Nilai Stok (HPP)</span>
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 transition-colors group-hover:bg-emerald-600 group-hover:text-white">
                    <i class="fas fa-dollar-sign text-xs"></i>
                </div>
            </div>
            <div class="flex flex-col">
                <h3 class="text-xl font-normal text-slate-800">Rp {{ number_format($stats['total_value'], 0, ',', '.') }}</h3>
                <p class="text-xs font-normal text-slate-400 mt-0.5">Total Kapitalisir</p>
            </div>
        </div>

        {{-- Stok Minimum --}}
        <div class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all hover:shadow-md border-l-4 border-l-amber-500">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-normal uppercase tracking-[0.2em] text-amber-500">Stok Minimum</span>
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 transition-colors group-hover:bg-amber-600 group-hover:text-white">
                    <i class="fas fa-exclamation-triangle text-xs"></i>
                </div>
            </div>
            <div class="flex flex-col">
                <h3 class="text-xl font-normal text-slate-800">{{ $stats['low_stock_count'] }}</h3>
                <p class="text-xs font-normal text-slate-400 mt-0.5">Perlu Reorder</p>
            </div>
        </div>

        {{-- Stok Habis --}}
        <div class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all hover:shadow-md border-l-4 border-l-rose-500">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-normal uppercase tracking-[0.2em] text-rose-500">Stok Habis</span>
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600 transition-colors group-hover:bg-rose-600 group-hover:text-white">
                    <i class="fas fa-times-circle text-xs"></i>
                </div>
            </div>
            <div class="flex flex-col">
                <h3 class="text-xl font-normal text-slate-800">{{ $stats['out_of_stock'] }}</h3>
                <p class="text-xs font-normal text-slate-400 mt-0.5">Status Kosong</p>
            </div>
        </div>
    </div>

    {{-- Filter Section --}}
    <div class="ui-card bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6 no-print">
        <div class="p-4 border-b border-slate-100 bg-slate-50/50">
            <div class="flex items-center gap-2">
                <i class="fas fa-filter text-indigo-500 text-[10px]"></i>
                <h3 class="text-xs font-normal text-slate-400 uppercase tracking-widest leading-none">Filter Data Stok</h3>
            </div>
        </div>
        <div class="p-5">
            <form method="GET" action="{{ route('admin.stocks.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-normal text-slate-500 uppercase tracking-wider ml-1">Outlet</label>
                    <select name="outlet_id" class="ui-input w-full px-3 py-1.5 text-sm font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                        <option value="">Semua Outlet</option>
                        @foreach($outlets as $outlet)
                            <option value="{{ $outlet->id }}" {{ request('outlet_id') == $outlet->id ? 'selected' : '' }}>
                                {{ $outlet->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Kategori</label>
                    <select name="category_id" class="ui-input w-full px-3 py-1.5 text-sm font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                        <option value="">Semua Kategori</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Cari Produk</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama atau SKU..."
                        class="ui-input w-full px-3 py-1.5 text-sm font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Kondisi Stok</label>
                    <select name="low_stock" class="ui-input w-full px-3 py-1.5 text-sm font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                        <option value="">Semua Stok</option>
                        <option value="1" {{ request('low_stock') == '1' ? 'selected' : '' }}>Stok Limit / Habis</option>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="ui-btn ui-btn-primary flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-normal text-white shadow-sm transition-all hover:bg-indigo-700 active:scale-95">
                        <i class="fas fa-search text-xs"></i>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('admin.stocks.index') }}" class="ui-btn ui-btn-ghost inline-flex items-center justify-center h-[34px] w-[34px] rounded-lg bg-white border border-slate-200 text-slate-400 hover:text-indigo-600 hover:border-indigo-100 transition-all active:scale-95">
                        <i class="fas fa-redo text-xs"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Stock Table --}}
    <div class="ui-card bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="ui-table ui-table-standard min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50/80 backdrop-blur-sm sticky top-0 z-10">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-normal uppercase tracking-widest text-slate-500">Produk</th>
                        <th class="px-5 py-3 text-left text-xs font-normal uppercase tracking-widest text-slate-500">Outlet</th>
                        <th class="px-5 py-3 text-right text-xs font-normal uppercase tracking-widest text-slate-500">Stok Saat Ini</th>
                        <th class="px-5 py-3 text-right text-xs font-normal uppercase tracking-widest text-slate-500">Stok Min</th>
                        <th class="px-5 py-3 text-right text-xs font-normal uppercase tracking-widest text-slate-500">Nilai (HPP)</th>
                        <th class="px-5 py-3 text-center text-xs font-normal uppercase tracking-widest text-slate-500">Status</th>
                        <th class="px-5 py-3 text-center text-xs font-normal uppercase tracking-widest text-slate-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($stocks as $stock)
                        <tr class="group hover:bg-slate-50/50 transition-colors">
                            <td class="px-5 py-3.5">
                                <div class="flex flex-col">
                                    <span class="text-sm font-normal text-slate-800 leading-tight">{{ $stock->product->name }}</span>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-xs font-mono text-slate-400 uppercase tracking-tighter bg-slate-100 px-1 rounded">{{ $stock->product->sku ?? 'NO-SKU' }}</span>
                                        <span class="text-xs font-normal text-slate-400 uppercase tracking-widest">{{ $stock->product->category->name ?? '-' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-1.5">
                                    <div class="w-1.5 h-1.5 rounded-full bg-indigo-400"></div>
                                    <span class="text-sm font-normal text-slate-600 tracking-tight">{{ $stock->outlet->name }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex flex-col items-end">
                                    <span class="text-[11.5px] font-normal text-slate-800">{{ number_format($stock->quantity, 2, ',', '.') }}</span>
                                    <span class="text-xs font-normal text-slate-400 uppercase tracking-widest">{{ $stock->product->unit ?? 'pcs' }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <span class="text-sm font-normal text-slate-500 italic">{{ number_format($stock->product->min_stock ?? 0, 2, ',', '.') }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <span class="text-[11.5px] font-normal text-slate-800 tracking-tight">Rp {{ number_format($stock->quantity * ($stock->product->purchase_price ?? 0), 0, ',', '.') }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                @if($stock->quantity <= 0)
                                    <span class="inline-flex items-center rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-normal text-rose-600 ring-1 ring-inset ring-rose-200">
                                        HABIS
                                    </span>
                                @elseif($stock->quantity < ($stock->product->min_stock ?? 0))
                                    <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-normal text-amber-600 ring-1 ring-inset ring-amber-200">
                                        LIMIT
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-normal text-emerald-600 ring-1 ring-inset ring-emerald-200">
                                        NORMAL
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <a href="{{ route('admin.stocks.card', ['product_id' => $stock->product_id, 'outlet_id' => $stock->outlet_id]) }}"
                                    class="inline-flex items-center gap-1.5 text-xs font-normal text-indigo-500 hover:text-indigo-700 transition-colors uppercase tracking-widest">
                                    <i class="fas fa-history text-[9px]"></i>
                                    <span>Kartu Stok</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center justify-center opacity-40">
                                    <i class="fas fa-box-open text-4xl mb-4 text-slate-300"></i>
                                    <p class="text-sm font-normal text-slate-500 italic uppercase tracking-widest">Tidak ada data stok ditemukan</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($stocks->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/30">
                {{ $stocks->links() }}
            </div>
        @endif
    </div>

    {{-- Quick Links / Utility Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8 no-print">
        <a href="{{ route('admin.stocks.mutations') }}"
            class="group bg-white rounded-xl border border-slate-100 p-4 shadow-sm transition-all hover:shadow-md active:scale-[0.98]">
            <div class="flex items-center gap-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-500 group-hover:bg-blue-500 group-hover:text-white transition-colors">
                    <i class="fas fa-list-ul text-sm"></i>
                </div>
                <div>
                    <h3 class="text-xs font-normal text-slate-800 uppercase tracking-wider leading-none mb-1">History Mutasi</h3>
                    <p class="text-xs font-normal text-slate-400">Lacak setiap perubahan stok</p>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.stock-transfers.index') }}"
            class="group bg-white rounded-xl border border-slate-100 p-4 shadow-sm transition-all hover:shadow-md active:scale-[0.98]">
            <div class="flex items-center gap-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-500 group-hover:bg-emerald-500 group-hover:text-white transition-colors">
                    <i class="fas fa-truck-moving text-sm"></i>
                </div>
                <div>
                    <h3 class="text-xs font-normal text-slate-800 uppercase tracking-wider leading-none mb-1">Data Transfer</h3>
                    <p class="text-[10px] font-normal text-slate-400">Monitoring pengiriman barang</p>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.purchases.index') }}"
            class="group bg-white rounded-xl border border-slate-100 p-4 shadow-sm transition-all hover:shadow-md active:scale-[0.98]">
            <div class="flex items-center gap-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-purple-50 text-purple-500 group-hover:bg-purple-500 group-hover:text-white transition-colors">
                    <i class="fas fa-shopping-basket text-sm"></i>
                </div>
                <div>
                    <h3 class="text-xs font-normal text-slate-800 uppercase tracking-wider leading-none mb-1">Purchase Order</h3>
                    <p class="text-[10px] font-normal text-slate-400">Pengadaan barang & supplier</p>
                </div>
            </div>
        </a>
    </div>
</div>
@endsection
