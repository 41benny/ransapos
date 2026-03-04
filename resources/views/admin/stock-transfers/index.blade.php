@extends('layouts.admin')

@section('title', 'Transfer Stok Antar Outlet')
@section('page-title', 'Transfer Stok')
@section('page-subtitle', 'Monitoring dan pengelolaan pengiriman barang antar cabang/outlet')

@section('content')
<div class="w-full animate-in fade-in slide-in-from-bottom-2 duration-500">
    {{-- Header Section --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-normal text-slate-800 tracking-tight">Transfer Stok</h1>
            <p class="text-xs font-normal text-slate-500 mt-0.5">Monitoring dan pengelolaan pengiriman barang antar cabang/outlet</p>
        </div>
        <div class="flex items-center gap-3 no-print">
            <a href="{{ route('admin.stock-transfers.create') }}"
                class="ui-btn ui-btn-primary inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-normal text-white shadow-sm transition-all hover:bg-indigo-700 active:scale-95">
                <i class="fas fa-plus text-[10px]"></i>
                <span>Buat Transfer Baru</span>
            </a>
        </div>
    </div>

    {{-- Filter Section --}}
    <div class="ui-card bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6 no-print">
        <div class="p-4 border-b border-slate-100 bg-slate-50/50">
            <div class="flex items-center gap-2">
                <i class="fas fa-filter text-indigo-500 text-[10px]"></i>
                <h3 class="text-[10px] font-normal text-slate-400 uppercase tracking-widest leading-none">Filter Data Transfer</h3>
            </div>
        </div>
        <div class="p-5">
            <form method="GET" action="{{ route('admin.stock-transfers.index') }}">
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Dari Outlet</label>
                        <select name="from_outlet_id" class="ui-input w-full px-3 py-1.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                            <option value="">Semua</option>
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->id }}" {{ request('from_outlet_id') == $outlet->id ? 'selected' : '' }}>
                                    {{ $outlet->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Ke Outlet</label>
                        <select name="to_outlet_id" class="ui-input w-full px-3 py-1.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                            <option value="">Semua</option>
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->id }}" {{ request('to_outlet_id') == $outlet->id ? 'selected' : '' }}>
                                    {{ $outlet->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Status</label>
                        <select name="status" class="ui-input w-full px-3 py-1.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                            <option value="">Semua Status</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="in_transit" {{ request('status') == 'in_transit' ? 'selected' : '' }}>In Transit</option>
                            <option value="received" {{ request('status') == 'received' ? 'selected' : '' }}>Received</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Dari Tanggal</label>
                        <input type="date" name="start_date" value="{{ request('start_date') }}"
                            class="ui-input w-full px-3 py-1.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Sampai Tanggal</label>
                        <input type="date" name="end_date" value="{{ request('end_date') }}"
                            class="ui-input w-full px-3 py-1.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                    </div>

                    <div class="flex items-end gap-2">
                        <button type="submit" class="ui-btn ui-btn-primary flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-normal text-white shadow-sm transition-all hover:bg-indigo-700 active:scale-95">
                            <i class="fas fa-search text-[10px]"></i>
                            <span>Filter</span>
                        </button>
                        <a href="{{ route('admin.stock-transfers.index') }}" class="ui-btn ui-btn-ghost inline-flex items-center justify-center h-[34px] w-[34px] rounded-lg bg-white border border-slate-200 text-slate-400 hover:text-indigo-600 hover:border-indigo-100 transition-all active:scale-95">
                            <i class="fas fa-redo text-[10px]"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Transfers Table --}}
    <div class="ui-card bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-8">
        <div class="overflow-x-auto">
            <table class="ui-table min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50/80 backdrop-blur-sm sticky top-0 z-10">
                    <tr>
                        <th class="px-5 py-3 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500">No. Transfer</th>
                        <th class="px-5 py-3 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500">Tanggal</th>
                        <th class="px-5 py-3 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500">Asal (From)</th>
                        <th class="px-5 py-3 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500">Tujuan (To)</th>
                        <th class="px-5 py-3 text-center text-[9px] font-normal uppercase tracking-widest text-slate-500">Item</th>
                        <th class="px-5 py-3 text-right text-[9px] font-normal uppercase tracking-widest text-slate-500">Nilai Kirim HPP</th>
                        <th class="px-5 py-3 text-center text-[9px] font-normal uppercase tracking-widest text-slate-500">Status</th>
                        <th class="px-5 py-3 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500">Author</th>
                        <th class="px-5 py-3 text-center text-[9px] font-normal uppercase tracking-widest text-slate-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($transfers as $transfer)
                        <tr class="group hover:bg-slate-50/50 transition-colors">
                            <td class="px-5 py-3.5">
                                <a href="{{ route('admin.stock-transfers.show', $transfer->id) }}" class="text-[11.5px] font-normal text-indigo-600 hover:text-indigo-800 transition-colors tracking-tight">
                                    {{ $transfer->transfer_number }}
                                </a>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="text-[11px] font-normal text-slate-700 tracking-tight">{{ $transfer->transfer_date->format('d M Y') }}</span>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-1.5">
                                    <div class="w-1.5 h-1.5 rounded-full bg-slate-400"></div>
                                    <span class="text-[11px] font-normal text-slate-600 tracking-tight">{{ $transfer->fromOutlet->name }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-1.5">
                                    <div class="w-1.5 h-1.5 rounded-full bg-indigo-400"></div>
                                    <span class="text-[11px] font-normal text-slate-600 tracking-tight">{{ $transfer->toOutlet->name }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <span class="text-[11px] font-normal text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full">{{ $transfer->items->count() }} Item</span>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                @php
                                    $nominalHpp = $transferNominals[$transfer->id] ?? null;
                                @endphp
                                @if(!is_null($nominalHpp))
                                    <span class="text-[11px] font-semibold text-slate-800">
                                        Rp {{ number_format((float) $nominalHpp, 0, ',', '.') }}
                                    </span>
                                @else
                                    <span class="text-[10px] font-normal text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                @php
                                    $statusStyles = [
                                        'pending' => 'bg-amber-50 text-amber-600 ring-amber-200',
                                        'in_transit' => 'bg-blue-50 text-blue-600 ring-blue-200',
                                        'received' => 'bg-emerald-50 text-emerald-600 ring-emerald-200',
                                        'cancelled' => 'bg-rose-50 text-rose-600 ring-rose-200',
                                    ];
                                    $style = $statusStyles[$transfer->status] ?? 'bg-slate-50 text-slate-600 ring-slate-200';
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[9px] font-normal {{ $style }} ring-1 ring-inset uppercase tracking-widest">
                                    {{ $transfer->status }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex flex-col">
                                    <span class="text-[11px] font-normal text-slate-700 leading-tight">{{ $transfer->creator->name ?? '-' }}</span>
                                    <span class="text-[9px] font-normal text-slate-400 mt-1 uppercase tracking-widest">{{ $transfer->created_at->format('d/m H:i') }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <a href="{{ route('admin.stock-transfers.show', $transfer->id) }}"
                                    class="inline-flex items-center gap-1.5 text-[10px] font-normal text-indigo-500 hover:text-indigo-700 transition-colors uppercase tracking-widest">
                                    <i class="fas fa-eye text-[9px]"></i>
                                    <span>Detail</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center justify-center opacity-40">
                                    <i class="fas fa-exchange-alt text-4xl mb-4 text-slate-300"></i>
                                    <p class="text-[11px] font-normal text-slate-500 italic uppercase tracking-widest">Tidak ada data transfer ditemukan</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($transfers->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/30">
                {{ $transfers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
