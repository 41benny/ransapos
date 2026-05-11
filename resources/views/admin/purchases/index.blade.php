@extends('layouts.admin')

@section('title', 'Daftar Pembelian')
@section('page-title', 'Pembelian')
@section('page-subtitle', 'Monitoring dan kelola transaksi pengadaan barang (Purchase Order)')

@section('content')
<div class="w-full animate-in fade-in slide-in-from-bottom-2 duration-500">
    {{-- Header Section --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-normal text-slate-900 tracking-tight">Daftar Pembelian</h1>
            <p class="text-xs font-normal text-slate-700 mt-0.5">Kelola pesanan pembelian (PO) dan stok masuk dari supplier</p>
        </div>
        <div class="flex items-center gap-3 no-print">
            <a href="{{ route('admin.purchases.create') }}"
                class="ui-btn ui-btn-primary inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-normal text-white shadow-sm transition-all hover:bg-indigo-700 active:scale-95">
                <i class="fas fa-plus text-xs"></i>
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

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Total Transaksi Card -->
        <div class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all hover:shadow-md border-l-4 border-l-indigo-500">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[9px] font-normal uppercase tracking-[0.2em] text-indigo-500">Total Transaksi</span>
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 transition-colors group-hover:bg-indigo-600 group-hover:text-white">
                    <i class="fas fa-shopping-cart text-xs"></i>
                </div>
            </div>
            <div class="flex flex-col">
                <h3 class="text-xl font-normal text-slate-800">{{ $summary['total_count'] }}</h3>
                <p class="text-[10px] font-normal text-slate-400 mt-0.5">Total PO Tercatat</p>
            </div>
        </div>

        <!-- Total Nilai Card -->
        <div class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all hover:shadow-md border-l-4 border-l-emerald-500">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[9px] font-normal uppercase tracking-[0.2em] text-emerald-500">Total Nilai Pembelian</span>
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 transition-colors group-hover:bg-emerald-600 group-hover:text-white">
                    <i class="fas fa-money-bill-wave text-xs"></i>
                </div>
            </div>
            <div class="flex flex-col">
                <h3 class="text-xl font-normal text-slate-800">Rp {{ number_format($summary['total_amount'], 0, ',', '.') }}</h3>
                <p class="text-[10px] font-normal text-slate-400 mt-0.5">Akumulasi Nilai Transaksi</p>
            </div>
        </div>

        <!-- Received Count Card -->
        <div class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all hover:shadow-md border-l-4 border-l-blue-500">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[9px] font-normal uppercase tracking-[0.2em] text-blue-500">Barang Diterima</span>
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 transition-colors group-hover:bg-blue-600 group-hover:text-white">
                    <i class="fas fa-box-open text-xs"></i>
                </div>
            </div>
            <div class="flex flex-col">
                <h3 class="text-xl font-normal text-slate-800">{{ $summary['received_count'] }}</h3>
                <p class="text-[10px] font-normal text-slate-400 mt-0.5">Status Received</p>
            </div>
        </div>

        <!-- Draft Count Card -->
        <div class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all hover:shadow-md border-l-4 border-l-amber-500">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[9px] font-normal uppercase tracking-[0.2em] text-amber-500">Draft / Pending PO</span>
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 transition-colors group-hover:bg-amber-600 group-hover:text-white">
                    <i class="fas fa-clock text-xs"></i>
                </div>
            </div>
            <div class="flex flex-col">
                <h3 class="text-xl font-normal text-slate-800">{{ $summary['draft_count'] }}</h3>
                <p class="text-[10px] font-normal text-slate-400 mt-0.5">Status Draft</p>
            </div>
        </div>
    </div>

    {{-- Filter Section --}}
    <div class="ui-card bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6 no-print">
        <div class="p-4 border-b border-slate-100 bg-slate-50/50">
            <div class="flex items-center gap-2">
                <i class="fas fa-filter text-indigo-500 text-xs"></i>
                <h3 class="text-xs font-normal text-slate-400 uppercase tracking-widest leading-none">Filter Pembelian</h3>
            </div>
        </div>
        <div class="p-5">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-normal text-slate-600 uppercase tracking-wider ml-1">Outlet</label>
                    <select name="outlet_id" class="ui-input w-full px-4 py-2.5 text-sm font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                        <option value="">Semua Outlet</option>
                        @foreach($outlets as $outlet)
                            <option value="{{ $outlet->id }}" {{ request('outlet_id') == $outlet->id ? 'selected' : '' }}>
                                {{ $outlet->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-normal text-slate-600 uppercase tracking-wider ml-1">Supplier</label>
                    <select name="supplier_id" class="ui-input w-full px-4 py-2.5 text-sm font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                        <option value="">Semua Supplier</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-normal text-slate-500 uppercase tracking-wider ml-1">Status</label>
                    <select name="status" class="ui-input w-full px-4 py-2.5 text-sm font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                        <option value="">Semua Status</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft (PO)</option>
                        <option value="received" {{ request('status') == 'received' ? 'selected' : '' }}>Received (Diterima)</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-normal text-slate-600 uppercase tracking-wider ml-1">Rentang Tanggal</label>
                    <div class="relative">
                        <i class="far fa-calendar-alt absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs z-10 pointer-events-none"></i>
                        <input type="text" id="purchaseDateRangePicker" name="date_range" value="{{ request('date_range') }}"
                            placeholder="Pilih rentang tanggal..."
                            class="ui-input w-full pl-9 pr-3 py-2.5 text-sm font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all cursor-pointer"
                            readonly>
                    </div>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="ui-btn ui-btn-primary flex-1 inline-flex items-center justify-center px-4 py-2.5 rounded-lg bg-slate-900 border border-slate-900 text-white hover:bg-slate-800 transition-all active:scale-95 text-xs font-normal">
                        <i class="fas fa-search mr-2 text-xs"></i>Filter
                    </button>
                    <a href="{{ route('admin.purchases.index') }}" class="ui-btn ui-btn-ghost inline-flex items-center justify-center px-3 py-2.5 rounded-lg bg-white border border-slate-200 text-slate-400 hover:text-indigo-600 hover:border-indigo-100 transition-all active:scale-95">
                        <i class="fas fa-redo text-xs"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Table Section --}}
    <div class="ui-card bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-8">
        <div class="overflow-x-auto">
            <table class="ui-table min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50/80 backdrop-blur-sm sticky top-0 z-10">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-normal uppercase tracking-widest text-slate-600">No. Purchase / Tgl</th>
                        <th class="px-5 py-3 text-left text-xs font-normal uppercase tracking-widest text-slate-600">Outlet & Supplier</th>
                        <th class="px-5 py-3 text-right text-xs font-normal uppercase tracking-widest text-slate-600">Total Transaksi</th>
                        <th class="px-5 py-3 text-center text-xs font-normal uppercase tracking-widest text-slate-600">Status</th>
                        <th class="px-5 py-3 text-center text-xs font-normal uppercase tracking-widest text-slate-600">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($purchases as $purchase)
                        <tr class="group hover:bg-slate-50/50 transition-colors">
                            <td class="px-5 py-3.5">
                                <div class="flex flex-col">
                                    <span class="text-sm font-mono text-indigo-600 tracking-tight leading-none mb-1">{{ $purchase->purchase_number }}</span>
                                    <span class="text-xs font-normal text-slate-400 uppercase tracking-widest">{{ $purchase->purchase_date->format('d M Y') }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex flex-col gap-0.5">
                                    <div class="flex items-center gap-1.5">
                                        <i class="fas fa-store text-[8px] text-slate-400"></i>
                                        <span class="text-sm font-normal text-slate-800">{{ $purchase->outlet->name }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <i class="fas fa-truck text-[8px] text-slate-400"></i>
                                        <span class="text-xs font-normal text-slate-600 italic">Sup: {{ $purchase->supplier->name }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <span class="text-sm font-normal text-slate-800 tracking-tight tabular-nums">Rp {{ number_format($purchase->total_amount, 0, ',', '.') }}</span>
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
                                        class="ui-action-icon ui-action-view" title="Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.purchases.print', $purchase) }}" target="_blank"
                                        class="ui-action-icon ui-action-print" title="Print PO">
                                        <i class="fas fa-print"></i>
                                    </a>
                                    @if($purchase->isDraft())
                                        <a href="{{ route('admin.purchases.edit', $purchase) }}"
                                            class="ui-action-icon ui-action-edit" title="Edit">
                                            <i class="fas fa-edit"></i>
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
                                    <p class="text-xs font-normal text-slate-500 italic uppercase tracking-widest">Belum ada transaksi pembelian yang tercatat</p>
                                    <a href="{{ route('admin.purchases.create') }}" class="mt-4 text-indigo-500 hover:underline text-xs font-normal tracking-wider">BUAT PEMBELIAN PERTAMA</a>
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

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .flatpickr-calendar {
            border-radius: 1rem;
            box-shadow: 0 20px 60px -10px rgba(0,0,0,0.15);
            border: 1px solid #e2e8f0;
        }
        .flatpickr-day.selected, .flatpickr-day.startRange, .flatpickr-day.endRange {
            background: #4f46e5 !important;
            border-color: #4f46e5 !important;
        }
        .flatpickr-day.inRange {
            background: #eef2ff !important;
            border-color: #eef2ff !important;
            box-shadow: -5px 0 0 #eef2ff, 5px 0 0 #eef2ff !important;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            flatpickr("#purchaseDateRangePicker", {
                mode: "range",
                dateFormat: "Y-m-d",
                allowInput: true,
                altInput: true,
                altFormat: "d M Y",
                prevArrow: '<i class="fas fa-chevron-left"></i>',
                nextArrow: '<i class="fas fa-chevron-right"></i>',
            });
        });
    </script>
@endpush
