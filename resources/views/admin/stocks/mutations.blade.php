@extends('layouts.admin')

@section('title', 'History Mutasi Stok')
@section('page-title', 'History Mutasi Stok')
@section('page-subtitle', 'Audit trail dan riwayat perubahan stok produk')

@section('content')
    <div class="mx-auto w-full max-w-7xl animate-in fade-in slide-in-from-bottom-2 duration-500">
        {{-- Header Section --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-normal text-slate-800 tracking-tight">History Mutasi Stok</h1>
                <p class="text-xs font-normal text-slate-500 mt-0.5">Audit trail dan riwayat perubahan stok produk</p>
            </div>
            <div class="flex items-center gap-3 no-print">
                <a href="{{ route('admin.stocks.index') }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-xs font-normal text-slate-700 border border-slate-200 shadow-sm transition-all hover:bg-slate-50 active:scale-95">
                    <i class="fas fa-arrow-left text-[10px]"></i>
                    <span>Kembali ke Stok</span>
                </a>
            </div>
        </div>

        {{-- Filter Section --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6 no-print">
            <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                <div class="flex items-center gap-2">
                    <i class="fas fa-filter text-indigo-500 text-[10px]"></i>
                    <h3 class="text-[10px] font-normal text-slate-400 uppercase tracking-widest leading-none">Filter Audit
                        Trail</h3>
                </div>
            </div>
            <div class="p-5">
                <form method="GET" action="{{ route('admin.stocks.mutations') }}" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label
                                class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Outlet</label>
                            <select name="outlet_id"
                                class="w-full px-3 py-1.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
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
                                class="w-full px-3 py-1.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
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
                                class="w-full px-3 py-1.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                <option value="">Semua</option>
                                <option value="purchase" {{ request('reference_type') == 'purchase' ? 'selected' : '' }}>
                                    Pembelian</option>
                                <option value="sale" {{ request('reference_type') == 'sale' ? 'selected' : '' }}>Penjualan
                                </option>
                                <option value="stock_opname" {{ request('reference_type') == 'stock_opname' ? 'selected' : '' }}>Opname</option>
                                <option value="stock_transfer" {{ request('reference_type') == 'stock_transfer' ? 'selected' : '' }}>Transfer</option>
                            </select>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Dari
                                Tanggal</label>
                            <input type="date" name="start_date" value="{{ request('start_date') }}"
                                class="w-full px-3 py-1.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Sampai
                                Tanggal</label>
                            <input type="date" name="end_date" value="{{ request('end_date') }}"
                                class="w-full px-3 py-1.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                        </div>

                        <div class="flex items-end gap-2">
                            <button type="submit"
                                class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-normal text-white shadow-sm transition-all hover:bg-indigo-700 active:scale-95">
                                <i class="fas fa-search text-[10px]"></i>
                                <span>Filter</span>
                            </button>
                            <a href="{{ route('admin.stocks.mutations') }}"
                                class="inline-flex items-center justify-center h-[34px] w-[34px] rounded-lg bg-white border border-slate-200 text-slate-400 hover:text-indigo-600 hover:border-indigo-100 transition-all active:scale-95">
                                <i class="fas fa-redo text-[10px]"></i>
                            </a>
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Cari
                            Produk</label>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Cari berdasarkan nama produk atau SKU..."
                            class="w-full px-4 py-2 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm">
                    </div>
                </form>
            </div>
        </div>

        {{-- Mutations Table --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-8">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/80 backdrop-blur-sm sticky top-0 z-10">
                        <tr>
                            <th class="px-5 py-3 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500">
                                Tanggal & Waktu</th>
                            <th class="px-5 py-3 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500">
                                Produk</th>
                            <th class="px-5 py-3 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500">
                                Outlet</th>
                            <th
                                class="px-5 py-3 text-center text-[9px] font-normal uppercase tracking-widest text-slate-500">
                                Tipe</th>
                            <th
                                class="px-5 py-3 text-right text-[9px] font-normal uppercase tracking-widest text-slate-500">
                                Qty Mutasi</th>
                            <th
                                class="px-5 py-3 text-right text-[9px] font-normal uppercase tracking-widest text-slate-500">
                                Stok Akhir</th>
                            <th class="px-5 py-3 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500">
                                Referensi & Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($mutations as $mutation)
                            <tr class="group hover:bg-slate-50/50 transition-colors">
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
                                <td class="px-5 py-3.5">
                                    <div class="flex flex-col gap-0.5">
                                        <div class="flex items-center gap-1.5">
                                            <span
                                                class="text-[10px] font-normal text-slate-700 uppercase tracking-wider">{{ str_replace('_', ' ', $mutation->reference_type ?? 'Manual') }}</span>
                                            @if($mutation->reference_id)
                                                <span
                                                    class="text-[9px] font-mono text-slate-400 bg-slate-50 px-1 border border-slate-100 rounded">#{{ $mutation->reference_id }}</span>
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
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center justify-center opacity-40">
                                        <i class="fas fa-history text-4xl mb-4 text-slate-300"></i>
                                        <p class="text-[11px] font-normal text-slate-500 italic uppercase tracking-widest">Tidak
                                            ada riwayat mutasi ditemukan</p>
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