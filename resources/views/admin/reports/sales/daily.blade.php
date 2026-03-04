@extends('layouts.admin')

@section('title', 'Ringkasan Penjualan Harian')
@section('page-title', 'Ringkasan Penjualan Harian')
@section('page-subtitle', 'Laporan ringkasan penjualan harian secara global')

@section('content')
    <div class="w-full animate-in fade-in slide-in-from-bottom-2 duration-500">
        {{-- Elegant Header --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
            <div class="flex items-center gap-4">
                <div class="group relative">
                    <div class="absolute -inset-1 rounded-2xl bg-gradient-to-r from-indigo-600 to-purple-600 opacity-20 blur transition duration-500 group-hover:opacity-40"></div>
                    <div class="relative flex h-12 w-12 items-center justify-center rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 transition-all duration-300 group-hover:scale-105 group-hover:shadow-indigo-100">
                        <i class="fas fa-chart-line text-xl bg-gradient-to-br from-indigo-600 to-purple-600 bg-clip-text text-transparent"></i>
                    </div>
                </div>
                <div>
                    <h1 class="text-2xl font-black text-slate-800 tracking-tight">Ringkasan Penjualan Harian</h1>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Global Performance Analytics</p>
                </div>
            </div>
            <div class="flex items-center gap-3 no-print">
                <a href="{{ route('admin.reports.index', ['tab' => 'penjualan']) }}"
                    class="ui-btn ui-btn-ghost inline-flex items-center gap-2 rounded-xl bg-white px-5 py-2.5 text-xs font-black text-slate-600 border border-slate-200 shadow-sm transition-all hover:bg-slate-50 hover:border-slate-300 hover:text-slate-900 active:scale-95">
                    <i class="fas fa-arrow-left-long text-[10px]"></i>
                    <span>Katalog Laporan</span>
                </a>
            </div>
        </div>

        {{-- Sophisticated Filter Panel --}}
        <div class="ui-card bg-white rounded-[2rem] shadow-xl shadow-slate-200/50 border border-slate-200/60 mb-8 no-print relative z-30">
            <div class="bg-slate-50/50 px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                    </span>
                    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] leading-none">Konfigurasi Laporan</h3>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="location.reload()" class="flex h-9 w-9 items-center justify-center rounded-xl bg-white text-slate-400 border border-slate-200 transition-all hover:text-indigo-600 hover:border-indigo-100 hover:shadow-sm">
                        <i class="fas fa-rotate text-xs"></i>
                    </button>
                </div>
            </div>
            
            <div class="p-8">
                <form method="GET" action="{{ route('admin.reports.sales.daily') }}" class="space-y-8">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                        {{-- Date Range --}}
                        <div class="lg:col-span-12 xl:col-span-5">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3 ml-1">Rentang Tanggal</label>
                            <div class="flex items-center gap-3">
                                <div class="relative flex-1 group">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-indigo-600 transition-colors">
                                        <i class="fas fa-calendar-day text-[10px]"></i>
                                    </div>
                                    <input type="date" name="date_from" value="{{ $dateFrom }}" required
                                        class="ui-input w-full pl-9 pr-3 py-2.5 text-sm font-bold bg-slate-50 border-0 ring-1 ring-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-600 focus:bg-white transition-all">
                                </div>
                                <div class="font-black text-slate-300 text-[10px] uppercase tracking-widest">ke</div>
                                <div class="relative flex-1 group">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-indigo-600 transition-colors">
                                        <i class="fas fa-calendar-check text-[10px]"></i>
                                    </div>
                                    <input type="date" name="date_to" value="{{ $dateTo }}" required
                                        class="ui-input w-full pl-9 pr-3 py-2.5 text-sm font-bold bg-slate-50 border-0 ring-1 ring-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-600 focus:bg-white transition-all">
                                </div>
                            </div>
                        </div>

                        {{-- Outlet & Type --}}
                        <div class="lg:col-span-12 xl:col-span-7 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-3">
                                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] ml-1">Outlet</label>
                                <div class="relative group">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-indigo-600 transition-colors">
                                        <i class="fas fa-store text-[10px]"></i>
                                    </div>
                                    <select name="outlet_id"
                                        class="ui-input w-full pl-9 pr-10 py-2.5 text-sm font-bold bg-slate-50 border-0 ring-1 ring-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-600 focus:bg-white transition-all appearance-none cursor-pointer">
                                        <option value="all">Semua Outlet Aktif</option>
                                        @foreach($outlets as $outlet)
                                            <option value="{{ $outlet->id }}" {{ ($filters['outlet_id'] ?? '') == $outlet->id ? 'selected' : '' }}>
                                                {{ $outlet->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
                                        <i class="fas fa-chevron-down text-[10px]"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] ml-1">Tipe Penjualan</label>
                                <div class="relative group">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-indigo-600 transition-colors">
                                        <i class="fas fa-tag text-[10px]"></i>
                                    </div>
                                    <select name="sales_type"
                                        class="ui-input w-full pl-9 pr-10 py-2.5 text-sm font-bold bg-slate-50 border-0 ring-1 ring-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-600 focus:bg-white transition-all appearance-none cursor-pointer">
                                        <option value="">Semua Metode Penjualan</option>
                                        @foreach($salesTypes as $code => $name)
                                            <option value="{{ $code }}" {{ ($filters['sales_type'] ?? '') == $code ? 'selected' : '' }}>
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
                                        <i class="fas fa-chevron-down text-[10px]"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Actions Bar --}}
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-6 border-t border-slate-100">
                        <button type="submit"
                            class="ui-btn ui-btn-primary w-full sm:w-auto inline-flex items-center justify-center gap-3 rounded-xl bg-slate-900 px-8 py-2.5 text-[13px] font-black text-white shadow-lg shadow-slate-200 transition-all hover:bg-indigo-600 hover:-translate-y-0.5 active:translate-y-0 active:scale-95">
                            <i class="fas fa-magnifying-glass text-xs"></i>
                            <span>Terapkan Parameter</span>
                        </button>

                        <div class="flex items-center gap-3 w-full sm:w-auto">
                            <div class="relative flex-1 sm:flex-none" id="exportDropdownWrap">
                                <button type="button" id="exportDropdownBtn"
                                    class="ui-btn ui-btn-ghost w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-white px-6 py-2.5 text-[11px] font-black text-slate-700 border border-slate-200 shadow-sm transition-all hover:bg-slate-50 active:scale-95">
                                    <i class="fas fa-download text-indigo-500"></i>
                                    <span>Ekspor Laporan</span>
                                    <i class="fas fa-chevron-down text-[9px] ml-1 opacity-50"></i>
                                </button>
                                
                                <div id="exportDropdownMenu" 
                                     class="hidden absolute right-0 mt-2 w-56 rounded-2xl shadow-2xl bg-white border border-slate-200 p-2 z-[100]">
                                    <a href="{{ route('admin.reports.sales.daily.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}" 
                                       class="flex items-center gap-3 px-3 py-2.5 text-[13px] font-bold text-slate-700 hover:bg-indigo-50 hover:text-indigo-700 rounded-xl transition-all group">
                                        <div class="h-8 w-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center group-hover:bg-emerald-500 group-hover:text-white transition-colors">
                                            <i class="fas fa-file-excel"></i>
                                        </div>
                                        <span>Excel Spreadsheet</span>
                                    </a>
                                    <a href="{{ route('admin.reports.sales.daily.export', array_merge(request()->query(), ['format' => 'pdf'])) }}" 
                                       class="flex items-center gap-3 px-3 py-2.5 text-[13px] font-bold text-slate-700 hover:bg-indigo-50 hover:text-indigo-700 rounded-xl transition-all group mt-1">
                                        <div class="h-8 w-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center group-hover:bg-rose-500 group-hover:text-white transition-colors">
                                            <i class="fas fa-file-pdf"></i>
                                        </div>
                                        <span>Dokumen PDF</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Luxury Table Section --}}
        <div class="ui-card bg-white rounded-[2.5rem] shadow-xl shadow-slate-200/40 border border-slate-200/60 overflow-hidden">
            <div class="relative p-10 text-center border-b border-slate-100">
                <div class="relative">
                    <div class="inline-flex items-center gap-3 px-4 py-1.5 rounded-full bg-indigo-600 text-white text-[10px] font-black uppercase tracking-[0.3em] mb-4">
                        Official Business Report
                    </div>
                    <h2 class="text-3xl font-black text-slate-800 tracking-tight mb-2">Ringkasan Penjualan Harian</h2>
                    <div class="flex items-center justify-center gap-4">
                        <div class="h-px w-8 bg-slate-200"></div>
                        <p class="text-slate-500 font-bold text-sm">
                            {{ \Carbon\Carbon::parse($dateFrom)->translatedFormat('d F Y') }} — {{ \Carbon\Carbon::parse($dateTo)->translatedFormat('d F Y') }}
                        </p>
                        <div class="h-px w-8 bg-slate-200"></div>
                    </div>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="ui-table min-w-full border-separate border-spacing-0">
                    <thead class="bg-slate-50/50 backdrop-blur-md">
                        <tr>
                            <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Tanggal</th>
                            <th class="px-8 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Total Sales</th>
                            <th class="px-8 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Diskon</th>
                            <th class="px-8 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Service</th>
                            <th class="px-8 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Pajak</th>
                            <th class="px-8 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Bulat</th>
                            <th class="px-8 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Total Akhir</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 bg-white">
                        @forelse($dailySales as $item)
                            <tr class="hover:bg-slate-50/80 transition-all group">
                                <td class="px-8 py-5">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-xl bg-white border border-slate-100 shadow-sm flex flex-col items-center justify-center group-hover:bg-indigo-600 group-hover:border-indigo-600 transition-all">
                                            <span class="text-[8px] font-black text-slate-400 group-hover:text-indigo-200 leading-none mb-1">{{ optional($item->sale_date)->format('M') }}</span>
                                            <span class="text-xs font-black text-slate-700 group-hover:text-white leading-none">{{ optional($item->sale_date)->format('d') }}</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-[13px] font-black text-slate-800">{{ optional($item->sale_date)->translatedFormat('l') }}</span>
                                            <span class="text-[11px] font-bold text-slate-400">{{ optional($item->sale_date)->format('Y') }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-5 text-right text-[13px] font-black text-slate-700">Rp {{ number_format($item->total_sales, 0, ',', '.') }}</td>
                                <td class="px-8 py-5 text-right text-[13px] font-black text-rose-500">
                                    @if($item->total_discount > 0)
                                        -Rp {{ number_format($item->total_discount, 0, ',', '.') }}
                                    @else
                                        Rp 0
                                    @endif
                                </td>
                                <td class="px-8 py-5 text-right text-[13px] font-black text-slate-600">Rp {{ number_format($item->total_service_charge, 0, ',', '.') }}</td>
                                <td class="px-8 py-5 text-right text-[13px] font-black text-slate-600">Rp {{ number_format($item->total_tax, 0, ',', '.') }}</td>
                                <td class="px-8 py-5 text-right text-[13px] font-black text-slate-600">
                                    <span class="{{ $item->total_adjustment < 0 ? 'text-rose-500' : ($item->total_adjustment > 0 ? 'text-emerald-500' : 'text-slate-400') }}">
                                        {{ $item->total_adjustment > 0 ? '+' : '' }}Rp {{ number_format($item->total_adjustment, 0, ',', '.') }}
                                    </span>
                                </td>
                                <td class="px-8 py-5 text-right">
                                    <span class="inline-flex px-4 py-2 rounded-xl bg-indigo-50 text-[14px] font-black text-indigo-700 group-hover:bg-indigo-600 group-hover:text-white transition-all">
                                        Rp {{ number_format($item->total_grand, 0, ',', '.') }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-10 py-20 text-center">
                                    <div class="flex flex-col items-center justify-center opacity-30">
                                        <i class="fas fa-inbox text-5xl mb-3"></i>
                                        <p class="text-sm font-black text-slate-400 tracking-widest">DATA TIDAK DITEMUKAN</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($dailySales->count() > 0)
                        <tfoot>
                            <tr class="bg-indigo-900">
                                <td class="px-8 py-6 text-[10px] font-black text-indigo-300 uppercase tracking-[0.2em]">Total Akumulasi</td>
                                <td class="px-8 py-6 text-right text-base font-black text-white">Rp {{ number_format($dailySales->sum('total_sales'), 0, ',', '.') }}</td>
                                <td class="px-8 py-6 text-right text-base font-black text-rose-300">Rp {{ number_format($dailySales->sum('total_discount'), 0, ',', '.') }}</td>
                                <td class="px-8 py-6 text-right text-base font-black text-white">Rp {{ number_format($dailySales->sum('total_service_charge'), 0, ',', '.') }}</td>
                                <td class="px-8 py-6 text-right text-base font-black text-white">Rp {{ number_format($dailySales->sum('total_tax'), 0, ',', '.') }}</td>
                                <td class="px-8 py-6 text-right text-base font-black text-white">Rp {{ number_format($dailySales->sum('total_adjustment'), 0, ',', '.') }}</td>
                                <td class="px-8 py-6 text-right">
                                    <div class="flex flex-col items-end">
                                        <span class="text-[9px] font-black text-indigo-400 uppercase tracking-widest mb-1">Grand Total</span>
                                        <span class="text-lg font-black text-white">Rp {{ number_format($dailySales->sum('total_grand'), 0, ',', '.') }}</span>
                                    </div>
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; font-size: 10pt; }
            aside, header { display: none !important; }
            main { padding: 0 !important; margin: 0 !important; }
            .shadow-xl, .shadow-2xl { box-shadow: none !important; }
            .rounded-[2rem], .rounded-[2.5rem] { border-radius: 0 !important; }
            tfoot tr { background-color: #1e1b4b !important; color: white !important; }
        }
        </style>
    @endpush

    @push('scripts')
        <script>
            // Dropdown Export Logic without Alpine
            (() => {
                const wrap = document.getElementById('exportDropdownWrap');
                const btn = document.getElementById('exportDropdownBtn');
                const menu = document.getElementById('exportDropdownMenu');

                if (!btn || !menu) return;

                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    menu.classList.toggle('hidden');
                });

                document.addEventListener('click', (e) => {
                    if (!wrap.contains(e.target)) {
                        menu.classList.add('hidden');
                    }
                });
            })();
        </script>
    @endpush
@endsection
