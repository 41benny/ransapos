@extends('layouts.admin')

@section('title', 'Daftar Pembelian')
@section('page-title', 'Pembelian')
@section('page-subtitle', 'Monitoring dan kelola transaksi pengadaan barang (Purchase Order)')

@section('content')
<div class="mx-auto w-full max-w-7xl animate-in fade-in slide-in-from-bottom-2 duration-500">
    {{-- Header Section --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-normal text-slate-800 tracking-tight">Daftar Pembelian</h1>
            <p class="text-xs font-normal text-slate-500 mt-0.5">Kelola pesanan pembelian (PO) dan stok masuk dari supplier</p>
        </div>
        <div class="flex items-center gap-3 no-print">
            <a href="{{ route('admin.purchases.create') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-normal text-white shadow-sm transition-all hover:bg-indigo-700 active:scale-95">
                <i class="fas fa-plus text-[10px]"></i>
                <span>Buat Pembelian Baru</span>
            </a>
        </div>
    </div>

    {{-- Alert Success/Error --}}
    @if(session('success'))
        <div class="mb-6 rounded-xl bg-emerald-50 border border-emerald-100 p-4 flex items-center gap-3 text-emerald-600 animate-in slide-in-from-top-2">
            <i class="fas fa-check-circle"></i>
            <p class="text-xs font-normal">{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 rounded-xl bg-rose-50 border border-rose-100 p-4 flex items-center gap-3 text-rose-600 animate-in slide-in-from-top-2">
            <i class="fas fa-exclamation-circle"></i>
            <p class="text-xs font-normal">{{ session('error') }}</p>
        </div>
    @endif

    {{-- Filter Section --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6 no-print">
        <div class="p-4 border-b border-slate-100 bg-slate-50/50">
            <div class="flex items-center gap-2">
                <i class="fas fa-filter text-indigo-500 text-[10px]"></i>
                <h3 class="text-[10px] font-normal text-slate-400 uppercase tracking-widest leading-none">Filter Pembelian</h3>
            </div>
        </div>
        <div class="p-5">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Outlet</label>
                    <select name="outlet_id" class="w-full px-3 py-1.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                        <option value="">Semua Outlet</option>
                        @foreach($outlets as $outlet)
                            <option value="{{ $outlet->id }}" {{ request('outlet_id') == $outlet->id ? 'selected' : '' }}>
                                {{ $outlet->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Supplier</label>
                    <select name="supplier_id" class="w-full px-3 py-1.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                        <option value="">Semua Supplier</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Status</label>
                    <select name="status" class="w-full px-3 py-1.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                        <option value="">Semua Status</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft (PO)</option>
                        <option value="received" {{ request('status') == 'received' ? 'selected' : '' }}>Received (Diterima)</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 inline-flex items-center justify-center h-[34px] rounded-lg bg-slate-900 border border-slate-900 text-white hover:bg-slate-800 transition-all active:scale-95 text-xs font-normal">
                        <i class="fas fa-search mr-2 text-[10px]"></i>Filter
                    </button>
                    <a href="{{ route('admin.purchases.index') }}" class="inline-flex items-center justify-center h-[34px] w-[34px] rounded-lg bg-white border border-slate-200 text-slate-400 hover:text-indigo-600 hover:border-indigo-100 transition-all active:scale-95">
                        <i class="fas fa-redo text-[10px]"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Table Section --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-8">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50/80 backdrop-blur-sm sticky top-0 z-10">
                    <tr>
                        <th class="px-5 py-3 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500">No. Purchase / Tgl</th>
                        <th class="px-5 py-3 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500">Outlet & Supplier</th>
                        <th class="px-5 py-3 text-right text-[9px] font-normal uppercase tracking-widest text-slate-500">Total Transaksi</th>
                        <th class="px-5 py-3 text-center text-[9px] font-normal uppercase tracking-widest text-slate-500">Status</th>
                        <th class="px-5 py-3 text-center text-[9px] font-normal uppercase tracking-widest text-slate-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($purchases as $purchase)
                        <tr class="group hover:bg-slate-50/50 transition-colors">
                            <td class="px-5 py-3.5">
                                <div class="flex flex-col">
                                    <span class="text-[11.5px] font-mono text-indigo-600 tracking-tight leading-none mb-1">{{ $purchase->purchase_number }}</span>
                                    <span class="text-[9px] font-normal text-slate-400 uppercase tracking-widest">{{ $purchase->purchase_date->format('d M Y') }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex flex-col gap-0.5">
                                    <div class="flex items-center gap-1.5">
                                        <i class="fas fa-store text-[8px] text-slate-300"></i>
                                        <span class="text-[11.5px] font-normal text-slate-700">{{ $purchase->outlet->name }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <i class="fas fa-truck text-[8px] text-slate-300"></i>
                                        <span class="text-[10px] font-normal text-slate-400 italic">Sup: {{ $purchase->supplier->name }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <span class="text-[12px] font-normal text-slate-800 tracking-tight tabular-nums">Rp {{ number_format($purchase->total_amount, 0, ',', '.') }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                @php
                                    $statusStyles = [
                                        'draft' => 'bg-amber-50 text-amber-600 ring-amber-200',
                                        'received' => 'bg-emerald-50 text-emerald-600 ring-emerald-200',
                                        'cancelled' => 'bg-rose-50 text-rose-600 ring-rose-200',
                                    ];
                                    $style = $statusStyles[$purchase->status] ?? 'bg-slate-50 text-slate-600 ring-slate-200';
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[9px] font-normal {{ $style }} ring-1 ring-inset uppercase tracking-widest">
                                    {{ $purchase->status }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('admin.purchases.show', $purchase) }}"
                                        class="h-8 w-8 inline-flex items-center justify-center bg-white border border-slate-200 text-slate-400 hover:text-indigo-600 hover:border-indigo-100 rounded-lg transition-all shadow-sm active:scale-95" title="Detail">
                                        <i class="fas fa-eye text-[10px]"></i>
                                    </a>
                                    <a href="{{ route('admin.purchases.print', $purchase) }}" target="_blank"
                                        class="h-8 w-8 inline-flex items-center justify-center bg-white border border-slate-200 text-slate-400 hover:text-emerald-600 hover:border-emerald-100 rounded-lg transition-all shadow-sm active:scale-95" title="Print PO">
                                        <i class="fas fa-print text-[10px]"></i>
                                    </a>
                                    @if($purchase->isDraft())
                                        <a href="{{ route('admin.purchases.edit', $purchase) }}"
                                            class="h-8 w-8 inline-flex items-center justify-center bg-white border border-slate-200 text-slate-400 hover:text-amber-600 hover:border-amber-100 rounded-lg transition-all shadow-sm active:scale-95" title="Edit">
                                            <i class="fas fa-edit text-[10px]"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center justify-center opacity-30">
                                    <i class="fas fa-receipt text-5xl mb-4 text-slate-300"></i>
                                    <p class="text-[11px] font-normal text-slate-500 italic uppercase tracking-widest">Belum ada transaksi pembelian yang tercatat</p>
                                    <a href="{{ route('admin.purchases.create') }}" class="mt-4 text-indigo-500 hover:underline text-[10px] font-normal tracking-wider">BUAT PEMBELIAN PERTAMA</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($purchases->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/30">
                {{ $purchases->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
