@extends('layouts.admin')

@section('title', 'Kartu Stok - ' . $product->name)
@section('page-title', 'Kartu Stok')
@section('page-subtitle', 'Laporan mutasi barang detail per item dan outlet')

@section('content')
    <div class="w-full animate-in fade-in slide-in-from-bottom-2 duration-500">
        {{-- Header Section --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-normal text-slate-800 tracking-tight">Kartu Stok</h1>
                <p class="text-xs font-normal text-slate-500 mt-0.5">Audit log mutasi barang detail per item dan outlet</p>
            </div>
            <div class="flex items-center gap-3 no-print">
                <a href="{{ route('admin.stocks.index') }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-xs font-normal text-slate-700 border border-slate-200 shadow-sm transition-all hover:bg-slate-50 active:scale-95">
                    <i class="fas fa-arrow-left text-[10px]"></i>
                    <span>Kembali ke Stok</span>
                </a>
            </div>
        </div>

        {{-- Product Meta & Summary Card --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6">
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <div class="flex flex-col gap-1">
                        <span class="text-[9px] font-normal uppercase tracking-widest text-slate-400">Informasi
                            Produk</span>
                        <h3 class="text-[13px] font-normal text-slate-800 leading-tight">{{ $product->name }}</h3>
                        <div class="flex items-center gap-1.5 mt-1">
                            <span
                                class="text-[9px] font-mono text-slate-400 bg-slate-50 px-1 border border-slate-100 rounded">SKU:
                                {{ $product->sku ?? 'NO-SKU' }}</span>
                            <span
                                class="text-[9px] font-normal text-slate-400 uppercase tracking-widest">{{ $product->category->name ?? '-' }}</span>
                        </div>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-[9px] font-normal uppercase tracking-widest text-slate-400">Lokasi / Outlet</span>
                        <div class="flex items-center gap-2">
                            <div class="h-2 w-2 rounded-full bg-indigo-500"></div>
                            <span class="text-[13px] font-normal text-slate-800">{{ $outlet->name }}</span>
                        </div>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-[9px] font-normal uppercase tracking-widest text-slate-400">Stok Akhir Saat
                            Ini</span>
                        <div class="flex items-baseline gap-1.5">
                            <span
                                class="text-xl font-normal text-indigo-600 tracking-tight">{{ number_format($currentStock->quantity ?? 0, 2, ',', '.') }}</span>
                            <span
                                class="text-[10px] font-normal text-slate-400 uppercase tracking-widest">{{ $product->unit ?? 'pcs' }}</span>
                        </div>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-[9px] font-normal uppercase tracking-widest text-slate-400">Nilai Persediaan
                            (HPP)</span>
                        <span class="text-lg font-normal text-slate-800 tracking-tight">Rp
                            {{ number_format(($currentStock->quantity ?? 0) * ($product->purchase_price ?? 0), 0, ',', '.') }}</span>
                        <span class="text-[9px] font-normal text-slate-400 italic">Est.
                            {{ number_format($product->purchase_price ?? 0, 0, ',', '.') }}/unit</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filter Period --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6 no-print">
            <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                <div class="flex items-center gap-2">
                    <i class="fas fa-calendar-alt text-indigo-500 text-[10px]"></i>
                    <h3 class="text-[10px] font-normal text-slate-400 uppercase tracking-widest leading-none">Filter Periode
                        Histori</h3>
                </div>
            </div>
            <div class="p-5">
                <form method="GET" action="{{ route('admin.stocks.card') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <input type="hidden" name="outlet_id" value="{{ $outlet->id }}">

                    <div class="flex flex-col gap-1.5">
                        <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Dari
                            Tanggal</label>
                        <input type="date" name="start_date" value="{{ request('start_date') }}"
                            class="w-full px-4 py-2 text-[11.5px] font-normal bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Sampai
                            Tanggal</label>
                        <input type="date" name="end_date" value="{{ request('end_date') }}"
                            class="w-full px-4 py-2 text-[11.5px] font-normal bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm">
                    </div>
                    <div class="flex items-end gap-2 md:col-span-2">
                        <button type="submit"
                            class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-6 py-2.5 text-xs font-normal text-white shadow-sm transition-all hover:bg-indigo-700 active:scale-95">
                            <i class="fas fa-filter text-[10px]"></i>
                            <span>Saring Periode</span>
                        </button>
                        <a href="{{ route('admin.stocks.card', ['product_id' => $product->id, 'outlet_id' => $outlet->id]) }}"
                            class="inline-flex items-center justify-center h-[40px] w-[40px] rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-indigo-600 hover:border-indigo-100 transition-all active:scale-95">
                            <i class="fas fa-redo text-[10px]"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Stock Card Table --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-8">
            <div class="p-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <i class="fas fa-history text-indigo-500 text-[10px]"></i>
                    <h3 class="text-[10px] font-normal text-slate-400 uppercase tracking-widest leading-none">Log Mutasi
                        Produk</h3>
                </div>
                <p class="text-[9px] font-normal text-slate-400 italic">Menampilkan {{ $mutations->count() }} transaksi</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-[9px] font-normal uppercase tracking-widest text-slate-400">
                                Tanggal & Waktu</th>
                            <th
                                class="px-5 py-3 text-center text-[9px] font-normal uppercase tracking-widest text-slate-400">
                                Tipe</th>
                            <th
                                class="px-5 py-3 text-right text-[9px] font-normal uppercase tracking-widest text-slate-400 text-emerald-500">
                                Masuk (+)</th>
                            <th
                                class="px-5 py-3 text-right text-[9px] font-normal uppercase tracking-widest text-slate-400 text-rose-500">
                                Keluar (-)</th>
                            <th
                                class="px-5 py-3 text-right text-[9px] font-normal uppercase tracking-widest text-slate-400">
                                Saldo Akhir</th>
                            <th class="px-5 py-3 text-left text-[9px] font-normal uppercase tracking-widest text-slate-400">
                                Referensi & Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($mutations as $mutation)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-5 py-3.5">
                                    <div class="flex flex-col">
                                        <span
                                            class="text-[11px] font-normal text-slate-700 leading-tight">{{ $mutation->mutation_date->format('d M Y') }}</span>
                                        <span
                                            class="text-[9px] font-normal text-slate-400 mt-1 uppercase tracking-widest">{{ $mutation->created_at->format('H:i') }}
                                            WIB</span>
                                    </div>
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
                                        $style = $typeStyles[$mutation->mutation_type] ?? 'bg-slate-50 text-slate-600 ring-slate-200';
                                    @endphp
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[8.5px] font-normal {{ $style }} ring-1 ring-inset uppercase tracking-widest">
                                        {{ str_replace('_', ' ', $mutation->mutation_type) }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    @if($mutation->quantity > 0)
                                        <span
                                            class="text-[11.5px] font-normal text-emerald-600 tabular-nums">+{{ number_format($mutation->quantity, 2, ',', '.') }}</span>
                                    @else
                                        <span class="text-[11px] font-normal text-slate-200">-</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    @if($mutation->quantity < 0)
                                        <span
                                            class="text-[11.5px] font-normal text-rose-500 tabular-nums">{{ number_format($mutation->quantity, 2, ',', '.') }}</span>
                                    @else
                                        <span class="text-[11px] font-normal text-slate-200">-</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <span
                                        class="text-[11.5px] font-normal text-slate-800 tracking-tight tabular-nums">{{ number_format($mutation->stock_after, 2, ',', '.') }}</span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex flex-col gap-0.5">
                                        <span
                                            class="text-[10px] font-normal text-slate-700 uppercase tracking-wider">{{ str_replace('_', ' ', $mutation->reference_type ?? 'MANUAL') }}
                                            @if($mutation->reference_id)<span
                                            class="text-slate-300 font-mono">#{{ $mutation->reference_id }}</span>@endif</span>
                                        @if($mutation->notes)
                                            <p class="text-[10px] font-normal text-slate-400 italic leading-snug">
                                                {{ $mutation->notes }}</p>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center justify-center opacity-40">
                                        <i class="fas fa-history text-4xl mb-4 text-slate-300"></i>
                                        <p class="text-[11px] font-normal text-slate-500 italic uppercase tracking-widest">Tidak
                                            ada record mutasi untuk periode ini</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Footer Summary Cards --}}
        @if($mutations->count() > 0)
            @php
                $totalIn = $mutations->where('quantity', '>', 0)->sum('quantity');
                $totalOut = abs($mutations->where('quantity', '<', 0)->sum('quantity'));
                $netChange = $totalIn - $totalOut;
            @endphp
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                <div class="bg-white rounded-2xl border border-emerald-100 p-5 shadow-sm">
                    <p class="text-[10px] font-normal uppercase tracking-[0.2em] text-emerald-500 mb-2">Total Stok Masuk (+)</p>
                    <div class="flex items-baseline gap-1">
                        <span
                            class="text-2xl font-normal text-emerald-600 tracking-tight">{{ number_format($totalIn, 2, ',', '.') }}</span>
                        <span
                            class="text-[10px] font-normal text-emerald-400 uppercase tracking-widest">{{ $product->unit ?? 'pcs' }}</span>
                    </div>
                </div>
                <div class="bg-white rounded-2xl border border-rose-100 p-5 shadow-sm">
                    <p class="text-[10px] font-normal uppercase tracking-[0.2em] text-rose-500 mb-2">Total Stok Keluar (-)</p>
                    <div class="flex items-baseline gap-1">
                        <span
                            class="text-2xl font-normal text-rose-600 tracking-tight">{{ number_format($totalOut, 2, ',', '.') }}</span>
                        <span
                            class="text-[10px] font-normal text-rose-400 uppercase tracking-widest">{{ $product->unit ?? 'pcs' }}</span>
                    </div>
                </div>
                <div class="bg-white rounded-2xl border border-indigo-100 p-5 shadow-sm">
                    <p class="text-[10px] font-normal uppercase tracking-[0.2em] text-indigo-500 mb-2">Perubahan Neto</p>
                    <div class="flex items-baseline gap-1">
                        <span
                            class="text-2xl font-normal tracking-tight {{ $netChange >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $netChange >= 0 ? '+' : '' }}{{ number_format($netChange, 2, ',', '.') }}
                        </span>
                        <span
                            class="text-[10px] font-normal text-slate-400 uppercase tracking-widest">{{ $product->unit ?? 'pcs' }}</span>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection