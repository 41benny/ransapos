@extends('layouts.admin')

@section('title', 'Detail Produksi')
@section('page-title', 'Detail Produksi')
@section('page-subtitle', $production->production_number)

@section('content')
<div class="w-full max-w-6xl animate-in fade-in slide-in-from-bottom-2 duration-500">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-normal text-slate-800 tracking-tight">{{ $production->production_number }}</h1>
            <p class="text-xs font-normal text-slate-500 mt-0.5">{{ $production->product->name ?? '-' }} - {{ $production->outlet->name ?? '-' }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.productions.index') }}" class="ui-btn ui-btn-secondary inline-flex items-center gap-2 rounded-lg bg-white border border-slate-200 px-4 py-2 text-xs text-slate-600">
                <i class="fas fa-arrow-left text-[10px]"></i>
                <span>Kembali</span>
            </a>
            <a href="{{ route('admin.productions.create') }}" class="ui-btn ui-btn-primary inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs text-white">
                <i class="fas fa-plus text-[10px]"></i>
                <span>Buat Produksi</span>
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="ui-card bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="text-[10px] uppercase tracking-widest text-slate-400 mb-1">Produk Hasil</div>
            <div class="text-lg text-slate-900">{{ $production->product->name ?? '-' }}</div>
            <div class="text-xs text-slate-500 mt-1">{{ $production->product->sku ?? '-' }}</div>
        </div>
        <div class="ui-card bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="text-[10px] uppercase tracking-widest text-slate-400 mb-1">Jumlah Hasil</div>
            <div class="text-lg text-slate-900">{{ rtrim(rtrim(number_format((float) $production->quantity, 4, ',', '.'), '0'), ',') }} {{ $production->product->unit ?? '' }}</div>
            <div class="text-xs text-slate-500 mt-1">{{ $production->production_date->format('d M Y') }}</div>
        </div>
        <div class="ui-card bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="text-[10px] uppercase tracking-widest text-slate-400 mb-1">HPP Produksi</div>
            <div class="text-lg text-slate-900">Rp {{ number_format((float) $production->total_cost, 0, ',', '.') }}</div>
            <div class="text-xs text-slate-500 mt-1">Rp {{ number_format((float) $production->unit_cost, 0, ',', '.') }} / unit</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 ui-card bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-2">
                <i class="fas fa-list-check text-indigo-500 text-[10px]"></i>
                <h3 class="text-[10px] font-normal text-slate-400 uppercase tracking-widest leading-none">Bahan Terpakai</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="ui-table min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th class="px-5 py-3 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500">Bahan</th>
                            <th class="px-5 py-3 text-right text-[9px] font-normal uppercase tracking-widest text-slate-500">Qty Pakai</th>
                            <th class="px-5 py-3 text-right text-[9px] font-normal uppercase tracking-widest text-slate-500">HPP/Unit</th>
                            <th class="px-5 py-3 text-right text-[9px] font-normal uppercase tracking-widest text-slate-500">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach($production->materials as $material)
                            <tr>
                                <td class="px-5 py-3.5">
                                    <div class="text-[11.5px] text-slate-800">{{ $material->product->name ?? '-' }}</div>
                                    <div class="text-[9px] text-slate-400 uppercase tracking-widest">{{ $material->product->sku ?? '' }}</div>
                                </td>
                                <td class="px-5 py-3.5 text-right text-[11px] text-slate-700">{{ rtrim(rtrim(number_format((float) $material->quantity, 4, ',', '.'), '0'), ',') }} {{ $material->uom }}</td>
                                <td class="px-5 py-3.5 text-right text-[11px] text-slate-700">Rp {{ number_format((float) $material->unit_cost, 0, ',', '.') }}</td>
                                <td class="px-5 py-3.5 text-right text-[11px] font-semibold text-slate-800">Rp {{ number_format((float) $material->total_cost, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="ui-card bg-white rounded-2xl shadow-sm border border-slate-200 p-5 h-fit">
            <div class="text-[10px] uppercase tracking-widest text-slate-400 mb-4">Informasi</div>
            <div class="space-y-4 text-sm">
                <div>
                    <div class="text-[10px] uppercase tracking-widest text-slate-400">BOM</div>
                    <div class="text-slate-800">{{ $production->bom->name ?: 'Resep Produksi' }}</div>
                </div>
                <div>
                    <div class="text-[10px] uppercase tracking-widest text-slate-400">Dibuat Oleh</div>
                    <div class="text-slate-800">{{ $production->creator->name ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-[10px] uppercase tracking-widest text-slate-400">Status</div>
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[9px] font-normal bg-emerald-50 text-emerald-600 ring-1 ring-emerald-200 uppercase tracking-widest">{{ $production->status }}</span>
                </div>
                <div>
                    <div class="text-[10px] uppercase tracking-widest text-slate-400">Catatan</div>
                    <div class="text-slate-800 whitespace-pre-line">{{ $production->notes ?: '-' }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
