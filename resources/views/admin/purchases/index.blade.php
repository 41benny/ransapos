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

    {{-- Table Section --}}
    <div class="ui-card bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-8">
        <div class="overflow-x-auto">
            <table class="ui-table min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50/80 backdrop-blur-sm sticky top-0 z-10">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-normal uppercase tracking-widest text-slate-600">No. Purchase / Tgl PO / Receive</th>
                        <th class="px-5 py-3 text-left text-xs font-normal uppercase tracking-widest text-slate-600">Outlet & Supplier</th>
                        <th class="px-5 py-3 text-right text-xs font-normal uppercase tracking-widest text-slate-600">Total Transaksi</th>
                        <th class="px-5 py-3 text-center text-xs font-normal uppercase tracking-widest text-slate-600">Status</th>
                        <th class="px-5 py-3 text-center text-xs font-normal uppercase tracking-widest text-slate-600">Aksi</th>
                    </tr>
                    <tr class="bg-white border-b border-slate-100 no-print align-top">
                        <td class="px-2 py-2 relative">
                            <button type="button" id="clearPurchaseFilters" title="Reset filter tabel"
                                class="absolute left-2 top-2 h-7 w-7 inline-flex items-center justify-center rounded-lg bg-slate-50 text-slate-400 hover:text-rose-500 hover:bg-rose-50 transition-all">
                                <i class="fas fa-times text-[10px]"></i>
                            </button>
                            <div class="space-y-2 pl-9">
                                <input type="text" data-name="keyword" value="{{ request('keyword') }}"
                                    placeholder="PO / produk / supplier / SKU..."
                                    class="ui-input purchase-table-filter w-full px-2 py-1.5 text-[10px] font-normal bg-slate-50 border border-slate-100 rounded-lg focus:ring-1 focus:ring-indigo-500 transition-all">
                                <div class="grid grid-cols-2 gap-2">
                                    <input type="date" data-name="date_from" value="{{ request('date_from') }}"
                                        title="Tanggal PO dari"
                                        class="ui-input purchase-table-filter w-full px-2 py-1.5 text-[10px] font-normal bg-slate-50 border border-slate-100 rounded-lg focus:ring-1 focus:ring-indigo-500 transition-all">
                                    <input type="date" data-name="date_to" value="{{ request('date_to') }}"
                                        title="Tanggal PO sampai"
                                        class="ui-input purchase-table-filter w-full px-2 py-1.5 text-[10px] font-normal bg-slate-50 border border-slate-100 rounded-lg focus:ring-1 focus:ring-indigo-500 transition-all">
                                </div>
                                <p class="text-[9px] font-normal uppercase tracking-widest text-slate-400">Cari + Tgl PO</p>
                            </div>
                        </td>
                        <td class="px-2 py-2">
                            <div class="space-y-2">
                                <select data-name="outlet_id"
                                    class="ui-input purchase-table-filter w-full px-2 py-1.5 text-[10px] font-normal bg-slate-50 border border-slate-100 rounded-lg focus:ring-1 focus:ring-indigo-500 transition-all">
                                    <option value="">Semua Outlet</option>
                                    @foreach($outlets as $outlet)
                                        <option value="{{ $outlet->id }}" {{ request('outlet_id') == $outlet->id ? 'selected' : '' }}>
                                            {{ $outlet->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <select data-name="supplier_id"
                                    class="ui-input purchase-table-filter w-full px-2 py-1.5 text-[10px] font-normal bg-slate-50 border border-slate-100 rounded-lg focus:ring-1 focus:ring-indigo-500 transition-all">
                                    <option value="">Semua Supplier</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-[9px] font-normal uppercase tracking-widest text-slate-400">Outlet + Supplier</p>
                            </div>
                        </td>
                        <td class="px-2 py-2">
                            <div class="space-y-2">
                                <div class="grid grid-cols-2 gap-2">
                                    <input type="date" data-name="received_from" value="{{ request('received_from') }}"
                                        title="Tanggal receive dari"
                                        class="ui-input purchase-table-filter w-full px-2 py-1.5 text-[10px] font-normal bg-emerald-50 border border-emerald-100 rounded-lg focus:ring-1 focus:ring-emerald-500 transition-all">
                                    <input type="date" data-name="received_to" value="{{ request('received_to') }}"
                                        title="Tanggal receive sampai"
                                        class="ui-input purchase-table-filter w-full px-2 py-1.5 text-[10px] font-normal bg-emerald-50 border border-emerald-100 rounded-lg focus:ring-1 focus:ring-emerald-500 transition-all">
                                </div>
                                <p class="text-[9px] font-normal uppercase tracking-widest text-emerald-500">Tanggal Receive</p>
                            </div>
                        </td>
                        <td class="px-2 py-2">
                            <div class="space-y-2">
                                <select data-name="status"
                                    class="ui-input purchase-table-filter w-full px-2 py-1.5 text-[10px] font-normal bg-slate-50 border border-slate-100 rounded-lg focus:ring-1 focus:ring-indigo-500 transition-all">
                                    <option value="">Semua Status</option>
                                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                                    <option value="received" {{ request('status') == 'received' ? 'selected' : '' }}>Received</option>
                                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                </select>
                                <p class="text-[9px] font-normal uppercase tracking-widest text-slate-400">Status Dokumen</p>
                            </div>
                        </td>
                        <td class="px-2 py-2 text-center">
                            <div class="flex h-full min-h-[74px] items-center justify-center rounded-lg border border-dashed border-slate-200 bg-slate-50 px-2">
                                <p class="text-[9px] font-normal leading-relaxed text-slate-400">
                                    Mutasi stok masuk mengikuti
                                    <span class="font-medium text-slate-600">tanggal receive</span>,
                                    bukan tanggal PO.
                                </p>
                            </div>
                        </td>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($purchases as $purchase)
                        <tr class="group hover:bg-slate-50/50 transition-colors">
                            <td class="px-5 py-3.5">
                                <div class="flex flex-col">
                                    <span class="text-sm font-mono text-indigo-600 tracking-tight leading-none mb-1">{{ $purchase->purchase_number }}</span>
                                    <span class="text-xs font-normal text-slate-400 uppercase tracking-widest">PO: {{ $purchase->purchase_date->format('d M Y') }}</span>
                                    <span class="text-[11px] font-normal text-slate-600 mt-1">
                                        Receive:
                                        @if($purchase->received_at)
                                            {{ $purchase->received_at->format('d M Y H:i') }}
                                        @else
                                            -
                                        @endif
                                    </span>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const filters = Array.from(document.querySelectorAll('.purchase-table-filter'));
    const clearButton = document.getElementById('clearPurchaseFilters');
    const filterKeys = ['keyword', 'outlet_id', 'supplier_id', 'status', 'date_from', 'date_to', 'received_from', 'received_to'];

    const updateFilter = (name, value) => {
        const url = new URL(window.location.href);

        if (value && value.trim() !== '') {
            url.searchParams.set(name, value.trim());
        } else {
            url.searchParams.delete(name);
        }

        url.searchParams.delete('page');
        window.location.href = url.toString();
    };

    let debounceTimer;

    filters.forEach((input) => {
        const name = input.dataset.name;

        if (input.tagName === 'SELECT' || input.type === 'date') {
            input.addEventListener('change', (event) => updateFilter(name, event.target.value));
            return;
        }

        input.addEventListener('input', (event) => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => updateFilter(name, event.target.value), 500);
        });
    });

    if (clearButton) {
        clearButton.addEventListener('click', () => {
            const url = new URL(window.location.href);
            filterKeys.forEach((key) => url.searchParams.delete(key));
            url.searchParams.delete('page');
            window.location.href = url.toString();
        });
    }
});
</script>
@endpush
