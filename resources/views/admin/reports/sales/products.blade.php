@extends('layouts.admin')

@section('title', 'Laporan Penjualan per Produk')
@section('page-title', 'Laporan Penjualan per Produk')
@section('page-subtitle', 'Analisis penjualan berdasarkan produk')

@section('content')
    <div class="mx-auto w-full max-w-7xl animate-in fade-in slide-in-from-bottom-2 duration-500">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-normal text-slate-800 tracking-tight">Penjualan per Produk</h1>
                <p class="text-xs font-normal text-slate-500 mt-0.5">Analisis performa penjualan setiap item menu</p>
            </div>
            <div class="flex items-center gap-3 no-print">
                <a href="{{ route('admin.reports.index', ['tab' => request('tab', 'penjualan')]) }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-xs font-normal text-slate-700 border border-slate-200 shadow-sm transition-all hover:bg-slate-50 active:scale-95">
                    <i class="fas fa-arrow-left text-[10px]"></i>
                    <span>Kembali ke Katalog</span>
                </a>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6 no-print">
            <div class="p-5 border-b border-slate-100 bg-slate-50/50">
                <div class="flex items-center gap-2">
                    <i class="fas fa-filter text-indigo-500 text-xs"></i>
                    <h3 class="text-[10px] font-normal text-slate-400 uppercase tracking-widest leading-none">Filter Laporan
                    </h3>
                </div>
            </div>
            <div class="p-5">
                <form method="GET" action="{{ route('admin.reports.sales.products') }}" class="space-y-4">
                    <input type="hidden" name="tab" value="{{ request('tab', 'penjualan') }}">

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Dari
                                Tanggal</label>
                            <input type="date" name="date_from" value="{{ $dateFrom }}" required
                                class="w-full px-3 py-1.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Sampai
                                Tanggal</label>
                            <input type="date" name="date_to" value="{{ $dateTo }}" required
                                class="w-full px-3 py-1.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Outlet</label>
                            <select name="outlet_id"
                                class="w-full px-3 py-1.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                <option value="">Semua Outlet</option>
                                @foreach($outlets as $outlet)
                                    <option value="{{ $outlet->id }}" {{ ($filters['outlet_id'] ?? '') == $outlet->id ? 'selected' : '' }}>
                                        {{ $outlet->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Kasir</label>
                            <select name="user_id"
                                class="w-full px-3 py-1.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                <option value="">Semua Kasir</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ ($filters['user_id'] ?? '') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                        <div class="flex items-center gap-2">
                            <button type="submit"
                                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-normal text-white shadow-sm transition-all hover:bg-indigo-700 active:scale-95">
                                <i class="fas fa-search text-[10px]"></i>
                                <span>Terapkan Filter</span>
                            </button>
                            <a href="{{ route('admin.reports.sales.products', ['tab' => request('tab', 'penjualan')]) }}"
                                class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-xs font-normal text-slate-600 border border-slate-200 shadow-sm transition-all hover:bg-slate-50 active:scale-95">
                                <i class="fas fa-undo text-[10px]"></i>
                                <span>Reset</span>
                            </a>
                        </div>

                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.reports.sales.products.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}"
                                class="inline-flex items-center gap-2 rounded-lg bg-emerald-50 px-4 py-2 text-xs font-normal text-emerald-700 border border-emerald-100 shadow-sm transition-all hover:bg-emerald-100 active:scale-95">
                                <i class="fas fa-file-excel text-[10px]"></i>
                                <span>Excel</span>
                            </a>
                            <a href="{{ route('admin.reports.sales.products.export', array_merge(request()->query(), ['format' => 'pdf'])) }}"
                                class="inline-flex items-center gap-2 rounded-lg bg-rose-50 px-4 py-2 text-xs font-normal text-rose-700 border border-rose-100 shadow-sm transition-all hover:bg-rose-100 active:scale-95">
                                <i class="fas fa-file-pdf text-[10px]"></i>
                                <span>PDF</span>
                            </a>
                            <button type="button" onclick="window.print()"
                                class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-xs font-normal text-white shadow-sm transition-all hover:bg-slate-800 active:scale-95">
                                <i class="fas fa-print text-[10px]"></i>
                                <span>Cetak</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div
                class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-all hover:shadow-md">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[9px] font-normal uppercase tracking-[0.2em] text-indigo-500">Total Item Terjual</span>
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 transition-colors group-hover:bg-indigo-600 group-hover:text-white">
                        <i class="fas fa-boxes text-sm"></i>
                    </div>
                </div>
                <div class="flex flex-col">
                    <h3 class="text-2xl font-normal text-slate-800 tracking-tight">
                        {{ number_format($grandTotal['total_qty'], 0, ',', '.') }}</h3>
                    <p class="text-[10px] font-normal text-slate-400 mt-0.5">Produk Berhasil Terjual</p>
                </div>
            </div>

            <div
                class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-all hover:shadow-md border-l-4 border-l-emerald-500">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[9px] font-normal uppercase tracking-[0.2em] text-emerald-500">Total Omzet
                        (Gross)</span>
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 transition-colors group-hover:bg-emerald-600 group-hover:text-white">
                        <i class="fas fa-money-bill-wave text-sm"></i>
                    </div>
                </div>
                <div class="flex flex-col">
                    <h3 class="text-2xl font-normal text-slate-800 tracking-tight">Rp
                        {{ number_format($grandTotal['total_amount'], 0, ',', '.') }}</h3>
                    <p class="text-[10px] font-normal text-slate-400 mt-0.5">Nilai Sebelum Diskon & Pajak Transaksi</p>
                </div>
            </div>
        </div>

        <!-- Products Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/80 sticky top-0 backdrop-blur-sm z-10">
                        <tr>
                            <th
                                class="px-4 py-3 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500 w-16">
                                No</th>
                            <th class="px-4 py-3 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500">
                                Produk</th>
                            <th class="px-4 py-3 text-left text-[9px] font-normal uppercase tracking-widest text-slate-500">
                                SKU</th>
                            <th class="px-4 py-3 text-right text-[9px] font-normal uppercase tracking-widest text-slate-500">
                                Total Qty</th>
                            <th class="px-4 py-3 text-right text-[9px] font-normal uppercase tracking-widest text-slate-500">
                                Total Omzet</th>
                            <th class="px-4 py-3 text-right text-[9px] font-normal uppercase tracking-widest text-slate-500">
                                Avg Price</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($products as $index => $product)
                            <tr class="group hover:bg-slate-50/80 transition-colors">
                                <td class="px-4 py-2.5 whitespace-nowrap text-[11px] font-normal text-slate-400">
                                    #{{ $index + 1 }}</td>
                                <td class="px-4 py-2.5 text-[11px] font-normal text-slate-800">{{ $product->product_name }}</td>
                                <td class="px-4 py-2.5 whitespace-nowrap text-[11px] font-mono text-slate-500">
                                    {{ $product->sku }}</td>
                                <td class="px-4 py-2.5 whitespace-nowrap text-right text-[11px] font-normal text-slate-800">
                                    {{ number_format($product->total_qty, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-2.5 whitespace-nowrap text-right text-[11px] font-normal text-indigo-600">
                                    Rp {{ number_format($product->total_amount, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-2.5 whitespace-nowrap text-right text-[11px] text-slate-500 italic">
                                    Rp {{ number_format($product->total_amount / max(1, $product->total_qty), 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center justify-center opacity-40">
                                        <i class="fas fa-box-open text-4xl mb-4"></i>
                                        <p class="text-[11px] font-normal text-slate-500 italic">Tidak ada data penjualan produk
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($products->count() > 0)
                        <tfoot class="bg-indigo-50/30">
                            <tr>
                                <td colspan="3"
                                    class="px-4 py-3 text-right text-[10px] font-normal text-slate-500 uppercase tracking-wider">
                                    GRAND TOTAL:
                                </td>
                                <td class="px-4 py-3 text-right text-sm font-normal text-slate-900 border-l border-indigo-100/50">
                                    {{ number_format($grandTotal['total_qty'], 0, ',', '.') }}
                                </td>
                                <td colspan="2" class="px-4 py-3 text-right text-sm font-normal text-indigo-700 bg-indigo-100/30">
                                    Rp {{ number_format($grandTotal['total_amount'], 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    <!-- Print CSS -->
    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: white;
            }

            aside,
            header {
                display: none !important;
            }

            main {
                padding: 0 !important;
            }
        }
    </style>
@endsection
