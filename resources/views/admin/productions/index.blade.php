@extends('layouts.admin')

@section('title', 'Produksi')
@section('page-title', 'Produksi')
@section('page-subtitle', 'Proses bahan baku menjadi stok setengah jadi atau produk hasil produksi')

@section('content')
<div class="w-full animate-in fade-in slide-in-from-bottom-2 duration-500">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-normal text-slate-800 tracking-tight">Transaksi Produksi</h1>
            <p class="text-xs font-normal text-slate-500 mt-0.5">Bahan keluar, hasil produksi masuk stok.</p>
        </div>
        <a href="{{ route('admin.productions.create') }}" class="ui-btn ui-btn-primary inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-normal text-white shadow-sm transition-all hover:bg-indigo-700 active:scale-95">
            <i class="fas fa-plus text-[10px]"></i>
            <span>Buat Produksi</span>
        </a>
    </div>

    <div class="ui-card bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6">
        <div class="p-4 border-b border-slate-100 bg-slate-50/50">
            <div class="flex items-center gap-2">
                <i class="fas fa-filter text-indigo-500 text-[10px]"></i>
                <h3 class="text-[10px] font-normal text-slate-400 uppercase tracking-widest leading-none">Filter Produksi</h3>
            </div>
        </div>
        <div class="p-5">
            <form method="GET" action="{{ route('admin.productions.index') }}" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Cari</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="PRD / SKU / produk" class="ui-input w-full px-3 py-1.5 text-[11.5px] bg-white border border-slate-200 rounded-lg">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Outlet</label>
                    <select name="outlet_id" class="ui-input w-full px-3 py-1.5 text-[11.5px] bg-white border border-slate-200 rounded-lg">
                        <option value="">Semua</option>
                        @foreach($outlets as $outlet)
                            <option value="{{ $outlet->id }}" @selected(request('outlet_id') == $outlet->id)>{{ $outlet->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Produk Hasil</label>
                    <select name="product_id" class="ui-input w-full px-3 py-1.5 text-[11.5px] bg-white border border-slate-200 rounded-lg">
                        <option value="">Semua</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" @selected(request('product_id') == $product->id)>{{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Dari</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}" class="ui-input w-full px-3 py-1.5 text-[11.5px] bg-white border border-slate-200 rounded-lg">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Sampai</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}" class="ui-input w-full px-3 py-1.5 text-[11.5px] bg-white border border-slate-200 rounded-lg">
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="ui-btn ui-btn-primary flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs text-white">
                        <i class="fas fa-search text-[10px]"></i>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('admin.productions.index') }}" class="ui-btn ui-btn-ghost inline-flex items-center justify-center h-[34px] w-[34px] rounded-lg bg-white border border-slate-200 text-slate-400">
                        <i class="fas fa-redo text-[10px]"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="ui-card bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-2">
            <i class="fas fa-flask text-indigo-500 text-[10px]"></i>
            <h3 class="text-[10px] font-normal text-slate-400 uppercase tracking-widest leading-none">Daftar Transaksi Produksi</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="ui-table min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50/80">
                    <tr>
                        <th class="px-5 py-3 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500">No. Produksi</th>
                        <th class="px-5 py-3 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500">Tanggal</th>
                        <th class="px-5 py-3 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500">Outlet</th>
                        <th class="px-5 py-3 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500">Produk Hasil</th>
                        <th class="px-5 py-3 text-right text-[9px] font-normal uppercase tracking-widest text-slate-500">Qty</th>
                        <th class="px-5 py-3 text-right text-[9px] font-normal uppercase tracking-widest text-slate-500">HPP/Unit</th>
                        <th class="px-5 py-3 text-right text-[9px] font-normal uppercase tracking-widest text-slate-500">Total HPP</th>
                        <th class="px-5 py-3 text-center text-[9px] font-normal uppercase tracking-widest text-slate-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($productions as $production)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-5 py-3.5">
                                <a href="{{ route('admin.productions.show', $production) }}" class="text-[11.5px] text-indigo-600 hover:text-indigo-800">{{ $production->production_number }}</a>
                            </td>
                            <td class="px-5 py-3.5 text-[11px] text-slate-700">{{ $production->production_date->format('d M Y') }}</td>
                            <td class="px-5 py-3.5 text-[11px] text-slate-600">{{ $production->outlet->name ?? '-' }}</td>
                            <td class="px-5 py-3.5">
                                <div class="text-[11.5px] text-slate-800">{{ $production->product->name ?? '-' }}</div>
                                <div class="text-[9px] text-slate-400 uppercase tracking-widest">{{ $production->product->sku ?? '' }}</div>
                            </td>
                            <td class="px-5 py-3.5 text-right text-[11px] text-slate-700">{{ rtrim(rtrim(number_format((float) $production->quantity, 4, ',', '.'), '0'), ',') }} {{ $production->product->unit ?? '' }}</td>
                            <td class="px-5 py-3.5 text-right text-[11px] text-slate-700">Rp {{ number_format((float) $production->unit_cost, 0, ',', '.') }}</td>
                            <td class="px-5 py-3.5 text-right text-[11px] font-semibold text-slate-800">Rp {{ number_format((float) $production->total_cost, 0, ',', '.') }}</td>
                            <td class="px-5 py-3.5 text-center">
                                <a href="{{ route('admin.productions.show', $production) }}" class="inline-flex items-center gap-1.5 text-[10px] text-indigo-500 hover:text-indigo-700 uppercase tracking-widest">
                                    <i class="fas fa-eye text-[9px]"></i>
                                    <span>Detail</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center justify-center opacity-40">
                                    <i class="fas fa-flask text-4xl mb-4 text-slate-300"></i>
                                    <p class="text-[11px] text-slate-500 italic uppercase tracking-widest">Belum ada transaksi produksi</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($productions->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/30">{{ $productions->links() }}</div>
        @endif
    </div>
</div>
@endsection
