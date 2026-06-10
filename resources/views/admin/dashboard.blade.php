@extends('layouts.admin')

@section('title', 'Dashboard')

@section('page-title', 'Dashboard Penjualan')

@section('content')
    <div class="space-y-6 mb-8">
        {{-- Modern Dashboard Header --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="text-[10px] font-bold uppercase tracking-widest text-orange-500 mb-1">Live Statistics</div>
                <h2 class="text-3xl font-black text-slate-800">Ringkasan Penjualan</h2>
            </div>

            <div class="flex flex-wrap items-center gap-3 bg-white p-2 rounded-2xl border border-slate-200 shadow-sm">
                <div class="relative flex flex-col px-3" id="outletFilterWrap">
                    <span class="text-[9px] font-black uppercase tracking-widest text-slate-400">Outlet Selection</span>
                    <button id="outletDropdownBtn" type="button"
                        class="flex items-center gap-2 text-xs font-bold text-slate-700 bg-transparent outline-none cursor-pointer">
                        <span id="outletDropdownLabel">Semua Outlet</span>
                        <i class="fas fa-chevron-down text-[10px] text-slate-400"></i>
                    </button>

                    <div id="outletDropdownMenu"
                        class="hidden absolute top-full left-0 mt-2 w-72 rounded-xl border border-slate-200 bg-white shadow-lg p-3 z-50">
                        <label
                            class="flex items-center gap-2 text-xs font-semibold text-slate-700 pb-2 mb-2 border-b border-slate-100 cursor-pointer">
                            <input type="checkbox" id="outletAllCheckbox"
                                class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" checked>
                            Semua Outlet
                        </label>
                        <div class="max-h-56 overflow-y-auto pr-1 space-y-1">
                            @foreach ($outlets as $outlet)
                                <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer">
                                    <input type="checkbox"
                                        class="outlet-checkbox rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                        value="{{ $outlet->id }}" checked>
                                    {{ $outlet->name }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="h-8 w-px bg-slate-100"></div>
                <div class="flex flex-col gap-2 px-3">
                    <span class="text-[9px] font-black uppercase tracking-widest text-slate-400">Date Range</span>
                    <div class="flex flex-wrap items-center gap-2">
                        <input id="dateFrom" type="date" value="{{ $defaultDate }}"
                            class="text-xs font-bold text-slate-700 bg-transparent outline-none cursor-pointer" />
                        <span class="text-[10px] font-black text-slate-300">to</span>
                        <input id="dateTo" type="date" value="{{ $defaultDate }}"
                            class="text-xs font-bold text-slate-700 bg-transparent outline-none cursor-pointer" />
                    </div>
                    <div class="flex flex-wrap items-center gap-1.5">
                        <button type="button" data-range-preset="today"
                            class="date-preset-btn rounded-lg border border-slate-200 px-2.5 py-1 text-[10px] font-black uppercase tracking-widest text-slate-500 transition hover:border-indigo-200 hover:text-indigo-600">Hari Ini</button>
                        <button type="button" data-range-preset="yesterday"
                            class="date-preset-btn rounded-lg border border-slate-200 px-2.5 py-1 text-[10px] font-black uppercase tracking-widest text-slate-500 transition hover:border-indigo-200 hover:text-indigo-600">Kemarin</button>
                        <button type="button" data-range-preset="week"
                            class="date-preset-btn rounded-lg border border-slate-200 px-2.5 py-1 text-[10px] font-black uppercase tracking-widest text-slate-500 transition hover:border-indigo-200 hover:text-indigo-600">Minggu Ini</button>
                        <button type="button" data-range-preset="month"
                            class="date-preset-btn rounded-lg border border-slate-200 px-2.5 py-1 text-[10px] font-black uppercase tracking-widest text-slate-500 transition hover:border-indigo-200 hover:text-indigo-600">Bulan Ini</button>
                    </div>
                </div>
                <button id="refreshBtn" type="button"
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all">
                    <i class="fas fa-sync-alt text-xs"></i>
                </button>
            </div>
        </div>

        <div class="flex items-center justify-between px-1">
            <div class="flex items-center gap-2">
                <div class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></div>
                <div id="statusText" class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Data
                    Synchronized</div>
            </div>
            <div class="text-[10px] font-bold uppercase tracking-widest text-slate-400">
                Last updated: <span id="lastUpdated" class="text-slate-600">-</span>
            </div>
        </div>
    </div>

    {{-- Row 1: KPI Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        {{-- Omzet KPI --}}
        <div
            class="group relative rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition-all hover:shadow-xl hover:shadow-slate-200/50">
            <div class="flex items-center justify-between mb-4">
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 transition-colors group-hover:bg-indigo-600 group-hover:text-white">
                    <i class="fas fa-coins text-lg"></i>
                </div>
                <div class="text-right">
                    <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400">Total Revenue</span>
                    <span id="trendSalesPct" class="text-xs font-black text-emerald-500">-</span>
                </div>
            </div>
            <div class="space-y-1">
                <h3 id="kpiTotalSales" class="text-3xl font-black text-slate-800 tracking-tight">-</h3>
                <div class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                    <span>vs Prev Period:</span>
                    <span id="trendSales" class="text-slate-600">-</span>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 h-1 w-0 bg-indigo-600 transition-all duration-500 group-hover:w-full">
            </div>
        </div>

        {{-- Avg Transaction KPI --}}
        <div
            class="group relative rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition-all hover:shadow-xl hover:shadow-slate-200/50">
            <div class="flex items-center justify-between mb-4">
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-amber-600 transition-colors group-hover:bg-amber-600 group-hover:text-white">
                    <i class="fas fa-chart-simple text-lg"></i>
                </div>
                <div class="text-right">
                    <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400">Performance
                        Index</span>
                    <span class="text-xs font-black text-slate-400">AVG</span>
                </div>
            </div>
            <div class="space-y-1">
                <h3 id="kpiAvgTransaction" class="text-3xl font-black text-slate-800 tracking-tight">-</h3>
                <div class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                    <span>Bill Per Invoice</span>
                </div>
                <div class="flex items-center gap-2 text-[11px] font-bold text-slate-500">
                    <i class="fas fa-receipt text-amber-500"></i>
                    <span id="kpiAvgInvoiceCount">0 invoice</span>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 h-1 w-0 bg-amber-500 transition-all duration-500 group-hover:w-full"></div>
        </div>

        {{-- Laba KPI --}}
        <div
            class="group relative rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition-all hover:shadow-xl hover:shadow-slate-200/50">
            <div class="flex items-center justify-between mb-4">
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 transition-colors group-hover:bg-emerald-600 group-hover:text-white">
                    <i class="fas fa-sack-dollar text-lg"></i>
                </div>
                <div class="text-right">
                    <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400">Laba Kotor</span>
                    <span id="kpiProfitPct" class="text-xs font-black text-emerald-500">-</span>
                </div>
            </div>
            <div class="space-y-1">
                <h3 id="kpiGrossProfit" class="text-3xl font-black text-slate-800 tracking-tight">-</h3>
                <div class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                    <span>Omzet &minus; HPP</span>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 h-1 w-0 bg-emerald-600 transition-all duration-500 group-hover:w-full">
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="ui-card dash-panel bg-white rounded-2xl shadow-sm border border-gray-100 p-6 lg:col-span-2 flex flex-col"
            style="--dash-accent:#2563eb;--dash-accent-2:#3b82f6;--dash-accent-soft:rgba(37,99,235,0.16);">
            <div class="dash-card-head flex items-center justify-between mb-4 shrink-0">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Omzet per Jam</h3>
                    <p class="text-xs text-slate-500">Berdasarkan jam transaksi (created_at)</p>
                </div>
                <div class="flex items-center gap-3">
                    <button id="hourlyStackToggle" type="button"
                        class="hidden outlet-chart-toggle inline-flex items-center gap-3 rounded-full border border-slate-200 bg-white/90 px-3 py-2 text-[11px] font-black uppercase tracking-[0.18em] text-slate-500 shadow-sm transition-all hover:border-blue-200 hover:text-blue-600"
                        aria-pressed="false"
                        title="Per Outlet: tinggi garis = omzet asli tiap outlet. Bertumpuk: garis ditumpuk, puncak garis = total omzet semua outlet.">
                        <span id="hourlyStackToggleText">Per Outlet</span>
                        <span class="outlet-chart-toggle__track">
                            <span class="outlet-chart-toggle__thumb"></span>
                        </span>
                    </button>
                    <div class="text-xs text-slate-500">00:00 - 23:00 • hover untuk detail</div>
                </div>
            </div>

            <div class="flex-1 w-full min-h-[400px]" id="hourlyBars"></div>
            <div id="hourlyEmpty" class="hidden mt-2 text-xs font-semibold text-orange-700 text-center">Data per jam belum
                tersedia untuk filter ini.</div>
        </div>

        {{-- Column 1: Per Kategori --}}
        <div class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition-all hover:shadow-lg">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <span
                        class="block text-[10px] font-black uppercase tracking-widest text-orange-500 mb-1">Breakdown</span>
                    <h3 class="text-lg font-black text-slate-800">Per Kategori</h3>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 text-slate-400">
                    <i class="fas fa-tags text-sm"></i>
                </div>
            </div>

            <div id="categoryList" class="space-y-5"></div>
            <div id="categoryEmpty"
                class="hidden text-center text-[10px] font-bold uppercase tracking-widest text-slate-400 py-12">No data
                recorded</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Column 1: Metode Pembayaran --}}
        <div class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition-all hover:shadow-lg">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <span
                        class="block text-[10px] font-black uppercase tracking-widest text-emerald-500 mb-1">Collection</span>
                    <h3 class="text-lg font-black text-slate-800">Metode Pembayaran</h3>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 text-slate-400">
                    <i class="fas fa-credit-card text-sm"></i>
                </div>
            </div>

            <div id="paymentList" class="space-y-3 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar italic"></div>
            <div id="paymentEmpty"
                class="hidden text-center text-[10px] font-bold uppercase tracking-widest text-slate-400 py-12">No data
                recorded</div>
        </div>

        {{-- Column 2: Top Products --}}
        <div class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition-all hover:shadow-lg">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <span class="block text-[10px] font-black uppercase tracking-widest text-indigo-500 mb-1">Best
                        Sellers</span>
                    <h3 class="text-lg font-black text-slate-800">Top Produk</h3>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 text-slate-400">
                    <i class="fas fa-crown text-sm"></i>
                </div>
            </div>

            <div class="overflow-x-hidden max-h-[400px] overflow-y-auto custom-scrollbar pr-1">
                <table class="w-full text-sm italic">
                    <thead class="bg-slate-50/50 sticky top-0 z-10">
                        <tr>
                            <th
                                class="text-left px-2 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest not-italic">
                                Pos</th>
                            <th
                                class="text-left px-2 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest not-italic">
                                Produk</th>
                            <th
                                class="text-right px-2 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest not-italic">
                                Qty</th>
                            <th
                                class="text-right px-2 py-3 text-[10px] font-black text-slate-500 uppercase tracking-widest not-italic">
                                Omzet</th>
                        </tr>
                    </thead>
                    <tbody id="productRows" class="divide-y divide-slate-100"></tbody>
                </table>
            </div>
            <div id="productEmpty"
                class="hidden text-center text-[10px] font-bold uppercase tracking-widest text-slate-400 py-12">No data
                recorded</div>
        </div>
    </div>

    <div id="outletPanel" class="ui-card dash-panel bg-white rounded-2xl shadow-sm border border-gray-100 p-6"
        style="--dash-accent:#2563eb;--dash-accent-2:#60a5fa;--dash-accent-soft:rgba(37,99,235,0.16);">
        <div class="dash-card-head flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-bold text-slate-900">Omzet per Outlet</h3>
                <p class="text-xs text-slate-500">Muncul saat memilih "Semua Outlet"</p>
            </div>
            <button id="outletChartModeToggle" type="button"
                class="outlet-chart-toggle inline-flex items-center gap-3 rounded-full border border-slate-200 bg-white/90 px-3 py-2 text-[11px] font-black uppercase tracking-[0.18em] text-slate-500 shadow-sm transition-all hover:border-blue-200 hover:text-blue-600"
                aria-pressed="false">
                <span id="outletChartModeText">Mode omzet</span>
                <span class="outlet-chart-toggle__track">
                    <span class="outlet-chart-toggle__thumb"></span>
                </span>
            </button>
        </div>

        <div id="outletBars" class="w-full h-[400px]"></div>

        <div id="outletEmpty" class="hidden text-center text-sm text-slate-500 py-6">Belum ada data.</div>

        <div id="outletMarginContainer" class="hidden mt-6 pt-6 border-t border-slate-100">
            <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Penjualan vs HPP</h4>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4" id="outletMarginList"></div>
        </div>
    </div>

    <noscript>
        <div class="mt-6 bg-amber-50 border border-amber-200 text-amber-900 rounded-xl p-4">
            Dashboard ini butuh JavaScript untuk update data otomatis.
        </div>
    </noscript>

@endsection

@push('styles')
    <style>
        .dashboard-hero {
            background-image:
                radial-gradient(circle at top right, rgba(59, 130, 246, 0.16), transparent 42%),
                radial-gradient(circle at bottom left, rgba(96, 165, 250, 0.1), transparent 38%);
        }

        .dash-panel {
            position: relative;
            overflow: hidden;
            transition: transform 180ms ease, box-shadow 180ms ease, border-color 180ms ease;
        }

        .dash-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, var(--dash-accent, #2563eb), var(--dash-accent-2, #60a5fa));
        }

        .dash-panel:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
            border-color: rgba(148, 163, 184, 0.35);
        }

        .dash-card-head {
            margin: -0.5rem -0.5rem 1rem;
            padding: 0.75rem 0.75rem;
            border-radius: 0.9rem;
            background: linear-gradient(90deg, var(--dash-accent-soft, rgba(37, 99, 235, 0.14)), rgba(255, 255, 255, 0));
        }

        .table-head-accent {
            background: linear-gradient(90deg, var(--dash-accent-soft, rgba(37, 99, 235, 0.14)), rgba(248, 250, 252, 0.85));
        }

        .dark .dash-panel {
            border-color: #334155;
            background-color: #111827;
        }

        .dark .dash-panel:hover {
            box-shadow: 0 14px 32px -18px rgba(2, 6, 23, 0.7);
            border-color: #475569;
        }

        .dark .dash-card-head {
            background: linear-gradient(90deg, rgba(37, 99, 235, 0.16), rgba(15, 23, 42, 0));
        }

        .outlet-chart-toggle__track {
            position: relative;
            display: inline-flex;
            align-items: center;
            width: 2.9rem;
            height: 1.5rem;
            border-radius: 9999px;
            background: linear-gradient(90deg, #cbd5e1, #e2e8f0);
            transition: background 180ms ease;
            flex: none;
        }

        .outlet-chart-toggle__thumb {
            position: absolute;
            left: 0.2rem;
            width: 1.1rem;
            height: 1.1rem;
            border-radius: 9999px;
            background: #fff;
            box-shadow: 0 3px 10px rgba(15, 23, 42, 0.18);
            transition: transform 180ms ease;
        }

        .outlet-chart-toggle.is-active {
            border-color: rgba(37, 99, 235, 0.28);
            color: #2563eb;
            background: rgba(255, 255, 255, 0.96);
        }

        .outlet-chart-toggle.is-active .outlet-chart-toggle__track {
            background: linear-gradient(90deg, #2563eb, #60a5fa);
        }

        .outlet-chart-toggle.is-active .outlet-chart-toggle__thumb {
            transform: translateX(1.35rem);
        }

        .dark #hourlyBars,
        .dark #outletBars {
            background: transparent;
        }

        .dark .outlet-chart-toggle {
            border-color: #334155;
            background: rgba(15, 23, 42, 0.92);
            color: #94a3b8;
        }

        .dark .outlet-chart-toggle.is-active {
            border-color: rgba(96, 165, 250, 0.42);
            color: #bfdbfe;
            background: rgba(15, 23, 42, 0.98);
        }

        .dark .apexcharts-tooltip {
            background: #0f172a !important;
            border-color: #334155 !important;
            color: #e2e8f0 !important;
        }

        .hourly-chart-container {
            min-height: 250px;
        }

        @media (prefers-reduced-motion: reduce) {
            .dash-panel {
                transition: none;
            }

            .dash-panel:hover {
                transform: none;
            }
        }

        @keyframes topRankUpFlash {
            0% {
                background-color: rgba(16, 185, 129, 0.2);
            }

            100% {
                background-color: transparent;
            }
        }

        @keyframes topRankDownFlash {
            0% {
                background-color: rgba(244, 63, 94, 0.18);
            }

            100% {
                background-color: transparent;
            }
        }

        @keyframes topRankNewFlash {
            0% {
                background-color: rgba(249, 115, 22, 0.2);
            }

            100% {
                background-color: transparent;
            }
        }

        .top-rank-up {
            animation: topRankUpFlash 900ms ease-out;
        }

        .top-rank-down {
            animation: topRankDownFlash 900ms ease-out;
        }

        .top-rank-new {
            animation: topRankNewFlash 900ms ease-out;
        }

        .custom-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: rgba(0, 0, 0, 0.05) transparent;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background-color: rgba(0, 0, 0, 0.05);
            border-radius: 20px;
            border: 1px solid transparent;
        }

        .custom-scrollbar:hover {
            scrollbar-color: #cbd5e1 transparent;
        }

        .custom-scrollbar:hover::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background-color: #94a3b8;
        }

        .date-preset-btn.is-active {
            border-color: rgba(79, 70, 229, 0.28);
            background: rgba(79, 70, 229, 0.08);
            color: #4f46e5;
        }
    </style>
@endpush


@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <script>
        (() => {
            let hourlyChart = null;
            let hourlyChartIsStacked = null;
            let outletChart = null;
            const endpoint = @json(route('admin.dashboard.summary'));
            const salesVsHppEndpoint = @json(route('admin.reports.catalog.show', ['slug' => 'sales-vs-hpp']));
            const canOpenSalesVsHppReport = @json((bool) (auth()->user()?->hasPermission('reports.sales.view') ?? false));
            const outletFilterWrapEl = document.getElementById('outletFilterWrap');
            const outletDropdownBtnEl = document.getElementById('outletDropdownBtn');
            const outletDropdownLabelEl = document.getElementById('outletDropdownLabel');
            const outletDropdownMenuEl = document.getElementById('outletDropdownMenu');
            const outletAllCheckboxEl = document.getElementById('outletAllCheckbox');
            const outletCheckboxEls = Array.from(document.querySelectorAll('.outlet-checkbox'));
            const dateFromEl = document.getElementById('dateFrom');
            const dateToEl = document.getElementById('dateTo');
            const datePresetBtns = Array.from(document.querySelectorAll('.date-preset-btn'));
            const refreshBtn = document.getElementById('refreshBtn');
            const statusTextEl = document.getElementById('statusText');
            const lastUpdatedEl = document.getElementById('lastUpdated');

            const kpiTotalSalesEl = document.getElementById('kpiTotalSales');
            const kpiAvgTransactionEl = document.getElementById('kpiAvgTransaction');
            const kpiAvgInvoiceCountEl = document.getElementById('kpiAvgInvoiceCount');
            const trendSalesEl = document.getElementById('trendSales');
            const trendSalesPctEl = document.getElementById('trendSalesPct');

            const kpiGrossProfitEl = document.getElementById('kpiGrossProfit');
            const kpiProfitPctEl = document.getElementById('kpiProfitPct');

            const hourlyBarsEl = document.getElementById('hourlyBars');
            const hourlyEmptyEl = document.getElementById('hourlyEmpty');
            const categoryListEl = document.getElementById('categoryList');
            const categoryEmptyEl = document.getElementById('categoryEmpty');
            const paymentListEl = document.getElementById('paymentList');
            const paymentEmptyEl = document.getElementById('paymentEmpty');
            const productRowsEl = document.getElementById('productRows');
            const productEmptyEl = document.getElementById('productEmpty');
            const outletPanelEl = document.getElementById('outletPanel');
            const outletBarsEl = document.getElementById('outletBars');
            const outletEmptyEl = document.getElementById('outletEmpty');
            const outletChartModeToggleEl = document.getElementById('outletChartModeToggle');
            const outletChartModeTextEl = document.getElementById('outletChartModeText');
            const hourlyStackToggleEl = document.getElementById('hourlyStackToggle');
            const hourlyStackToggleTextEl = document.getElementById('hourlyStackToggleText');
            const idr = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0, });
            const hourlyPalette = ['#4F46E5', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4', '#F97316'];
            const hourlyOthersColor = '#94A3B8';
            const chartBlue = '#3b82f6';
            const chartRose = '#f43f5e';
            const isDarkTheme = () => document.documentElement.classList.contains('dark');
            const getChartTheme = () => {
                const dark = isDarkTheme();
                return {
                    dark,
                    axisText: dark ? '#94a3b8' : '#94a3b8',
                    grid: dark ? 'rgba(148, 163, 184, 0.2)' : '#f1f5f9',
                    tooltipTheme: dark ? 'dark' : 'light',
                };
            };
            let timer = null; let isLoading = false;
            let outletChartMode = 'area';
            let hourlyStackMode = 'overlap'; // 'overlap' (per outlet, default) | 'stacked' (bertumpuk)
            let latestHourlySeries = null;
            let latestHourlyLabels = null;
            let latestOutletRows = [];
            let latestOutletIsAllOutlets = false;
            function setStatus(text, type = 'info') {
                statusTextEl.textContent = text; statusTextEl.className = 'text-xs';
                if (type === 'error') { statusTextEl.classList.add('text-red-600'); } else if (type === 'success') { statusTextEl.classList.add('text-green-600'); } else { statusTextEl.classList.add('text-slate-500'); }
            }
            function setLoadingState(loading) { isLoading = loading; refreshBtn.disabled = loading; refreshBtn.classList.toggle('opacity-60', loading); refreshBtn.classList.toggle('cursor-not-allowed', loading); }
            function getSelectedOutletIds() {
                return outletCheckboxEls
                    .filter((checkbox) => checkbox.checked)
                    .map((checkbox) => Number(checkbox.value))
                    .filter((id) => Number.isInteger(id) && id > 0);
            }
            function simplifyOutletName(name) {
                if (!name) return '';
                let clean = name.replace(/Ransa/gi, '').trim();
                if (!clean) clean = String(name).trim();
                if (clean.toLowerCase() === 'ciplaz') return 'Cplz';
                if (clean.length > 10 && clean.includes(' ')) {
                    return clean.split(/\s+/).map(w => w[0].toUpperCase()).join('');
                }
                return clean;
            }
            function buildCurrencyTooltipHtml(title, items, emptyMessage = 'Tidak ada data untuk titik ini.') {
                const dark = isDarkTheme();
                const borderColor = dark ? '#334155' : '#dbeafe';
                const panelColor = dark ? '#0f172a' : '#ffffff';
                const titleColor = dark ? '#94a3b8' : '#64748b';
                const textColor = dark ? '#e2e8f0' : '#0f172a';
                const mutedColor = dark ? '#94a3b8' : '#64748b';
                const dividerColor = dark ? '#1e293b' : '#e2e8f0';
                const shadowColor = dark ? 'rgba(15, 23, 42, 0.55)' : 'rgba(15, 23, 42, 0.12)';

                const rowsHtml = items.length > 0
                    ? items.map((item) => `
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;font-size:12px;line-height:1.45;">
                            <div style="display:flex;align-items:center;gap:8px;min-width:0;">
                                <span style="display:inline-block;width:10px;height:10px;border-radius:9999px;background:${escapeHtml(item.color || chartBlue)};flex:none;"></span>
                                <span style="font-weight:600;color:${textColor};white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escapeHtml(item.label)}</span>
                            </div>
                            <span style="font-weight:700;color:${textColor};white-space:nowrap;">${escapeHtml(idr.format(Number(item.value || 0)))}</span>
                        </div>
                    `).join('')
                    : `<div style="font-size:12px;color:${mutedColor};">${escapeHtml(emptyMessage)}</div>`;

                return `
                    <div style="min-width:220px;max-width:320px;border:1px solid ${borderColor};border-radius:14px;background:${panelColor};box-shadow:0 14px 32px ${shadowColor};overflow:hidden;">
                        <div style="padding:10px 12px;border-bottom:1px solid ${dividerColor};font-size:11px;font-weight:800;letter-spacing:0.08em;text-transform:uppercase;color:${titleColor};">
                            ${escapeHtml(title)}
                        </div>
                        <div style="padding:10px 12px;display:flex;flex-direction:column;gap:8px;">
                            ${rowsHtml}
                        </div>
                    </div>
                `;
            }
            function createCurrencyTooltip(labels, options = {}) {
                const {
                    filterZero = false,
                    sortByValue = false,
                    titlePrefix = '',
                } = options;

                return {
                    shared: true,
                    intersect: false,
                    custom: ({ series, dataPointIndex, w }) => {
                        const label = Array.isArray(labels) ? labels[dataPointIndex] : '';
                        const title = titlePrefix && label ? `${titlePrefix}: ${label}` : (label || titlePrefix || 'Detail');
                        const seriesNames = Array.isArray(w?.globals?.seriesNames) ? w.globals.seriesNames : [];
                        const colors = Array.isArray(w?.globals?.colors) ? w.globals.colors : [];
                        const items = seriesNames
                            .map((seriesName, index) => ({
                                label: seriesName || `Seri ${index + 1}`,
                                value: Number(Array.isArray(series[index]) ? (series[index][dataPointIndex] ?? 0) : 0),
                                color: colors[index] || chartBlue,
                            }))
                            .filter((item) => !filterZero || item.value > 0);

                        if (sortByValue) {
                            items.sort((a, b) => b.value - a.value);
                        }

                        return buildCurrencyTooltipHtml(title, items);
                    }
                };
            }
            function syncOutletChartModeToggle() {
                if (!outletChartModeToggleEl || !outletChartModeTextEl) return;
                const isCompareMode = outletChartMode === 'bar';
                outletChartModeToggleEl.classList.toggle('is-active', isCompareMode);
                outletChartModeToggleEl.setAttribute('aria-pressed', isCompareMode ? 'true' : 'false');
                outletChartModeTextEl.textContent = isCompareMode ? 'Mode omzet vs hpp' : 'Mode omzet';
            }
            function syncHourlyStackToggle(isMulti) {
                if (!hourlyStackToggleEl || !hourlyStackToggleTextEl) return;
                // Toggle hanya relevan saat ada >1 outlet (mode breakdown)
                hourlyStackToggleEl.classList.toggle('hidden', !isMulti);
                const isStacked = hourlyStackMode === 'stacked';
                hourlyStackToggleEl.classList.toggle('is-active', isStacked);
                hourlyStackToggleEl.setAttribute('aria-pressed', isStacked ? 'true' : 'false');
                hourlyStackToggleTextEl.textContent = isStacked ? 'Bertumpuk' : 'Per Outlet';
            }
            function getCompactCurrencyLabel(val) {
                if (val >= 1000000) return (val / 1000000).toFixed(1) + 'M';
                if (val >= 1000) return (val / 1000).toFixed(0) + 'k';
                return val;
            }
            function buildOutletChartOptions(rows) {
                const chartTheme = getChartTheme();
                const labels = rows.map(x => simplifyOutletName(x.outlet_name));
                const fullLabels = rows.map(x => x.outlet_name || 'Outlet');
                const amounts = rows.map(x => Number(x.amount || 0));
                const cogsAmounts = rows.map(x => Number(x.cogs || 0));
                const compareMode = outletChartMode === 'bar';

                const options = {
                    series: compareMode
                        ? [
                            { name: 'Omzet', data: amounts },
                            { name: 'HPP', data: cogsAmounts },
                        ]
                        : [
                            { name: 'Omzet', data: amounts },
                        ],
                    chart: {
                        type: compareMode ? 'bar' : 'area',
                        height: 400,
                        toolbar: { show: false },
                        zoom: { enabled: false },
                        fontFamily: 'Inter, sans-serif',
                        background: 'transparent',
                        stacked: false,
                        animations: {
                            enabled: true,
                            easing: 'easeinout',
                            speed: 800,
                        }
                    },
                    theme: { mode: chartTheme.dark ? 'dark' : 'light' },
                    dataLabels: { enabled: false },
                    legend: compareMode ? {
                        show: true,
                        position: 'top',
                        horizontalAlign: 'right',
                        labels: { colors: chartTheme.axisText },
                        markers: { radius: 12 },
                    } : {
                        show: false,
                    },
                    xaxis: {
                        categories: labels,
                        labels: {
                            style: { colors: chartTheme.axisText, fontSize: compareMode ? '10px' : '11px' },
                            rotate: -45,
                            rotateAlways: false,
                            hideOverlappingLabels: true,
                            trim: true,
                        },
                        axisBorder: { show: false },
                        axisTicks: { show: false }
                    },
                    yaxis: {
                        labels: {
                            formatter: (val) => getCompactCurrencyLabel(val),
                            style: { colors: chartTheme.axisText, fontSize: '10px' }
                        },
                        tickAmount: 4,
                    },
                    grid: {
                        borderColor: chartTheme.grid,
                        strokeDashArray: compareMode ? 0 : 4,
                        padding: { left: 10, right: 10 }
                    },
                    tooltip: {
                        theme: chartTheme.tooltipTheme,
                        ...createCurrencyTooltip(fullLabels, {
                            filterZero: compareMode,
                            sortByValue: compareMode,
                            titlePrefix: 'Outlet',
                        }),
                    },
                    colors: compareMode ? [chartBlue, chartRose] : [chartBlue],
                };

                if (compareMode) {
                    options.plotOptions = {
                        bar: {
                            horizontal: false,
                            columnWidth: '52%',
                            borderRadius: 6,
                            borderRadiusApplication: 'end',
                            dataLabels: { position: 'top' },
                        }
                    };
                    options.stroke = {
                        show: true,
                        width: 1,
                        colors: ['transparent']
                    };
                    options.fill = {
                        opacity: 0.92,
                    };
                } else {
                    options.stroke = {
                        curve: 'smooth',
                        width: 3,
                        colors: [chartBlue]
                    };
                    options.fill = {
                        type: 'gradient',
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.45,
                            opacityTo: 0.05,
                            stops: [20, 100],
                        }
                    };
                    options.markers = {
                        size: 0,
                        colors: [chartBlue],
                        strokeColors: '#fff',
                        strokeWidth: 2,
                        hover: { size: 6 }
                    };
                }

                return options;
            }
            function updateOutletLabel() {
                const selectedNames = outletCheckboxEls
                    .filter((checkbox) => checkbox.checked)
                    .map((checkbox) => simplifyOutletName(checkbox.parentElement?.textContent?.trim()));

                if (outletAllCheckboxEl.checked || selectedNames.length === 0) {
                    outletDropdownLabelEl.textContent = 'Semua Outlet';
                    return;
                }

                if (selectedNames.length === 1) {
                    outletDropdownLabelEl.textContent = selectedNames[0];
                    return;
                }

                outletDropdownLabelEl.textContent = `${selectedNames.length} Outlet Dipilih`;
            }
            function normalizeOutletSelection(source) {
                if (source === 'all') {
                    outletCheckboxEls.forEach((checkbox) => {
                        checkbox.checked = outletAllCheckboxEl.checked;
                    });
                } else {
                    const allChecked = outletCheckboxEls.every((item) => item.checked);
                    outletAllCheckboxEl.checked = allChecked;
                }

                updateOutletLabel();
            }
            function getDateRange() {
                const fallbackDate = @json($defaultDate);
                const dateFrom = dateFromEl.value || fallbackDate;
                const dateTo = dateToEl.value || dateFrom;

                if (dateFrom > dateTo) {
                    return { dateFrom: dateTo, dateTo: dateFrom };
                }

                return { dateFrom, dateTo };
            }
            function toDateInputValue(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }
            function getPresetRange(preset) {
                const today = new Date(@json($defaultDate) + 'T00:00:00');
                const start = new Date(today);
                const end = new Date(today);

                if (preset === 'yesterday') {
                    start.setDate(start.getDate() - 1);
                    end.setDate(end.getDate() - 1);
                } else if (preset === 'week') {
                    const day = start.getDay() || 7;
                    start.setDate(start.getDate() - day + 1);
                } else if (preset === 'month') {
                    start.setDate(1);
                }

                return {
                    dateFrom: toDateInputValue(start),
                    dateTo: toDateInputValue(end),
                };
            }
            function syncDatePresetState() {
                const current = getDateRange();
                datePresetBtns.forEach((button) => {
                    const range = getPresetRange(button.dataset.rangePreset);
                    button.classList.toggle('is-active', range.dateFrom === current.dateFrom && range.dateTo === current.dateTo);
                });
            }
            function buildUrl() {
                const { dateFrom, dateTo } = getDateRange();
                const url = new URL(endpoint, window.location.origin);
                url.searchParams.set('date_from', dateFrom);
                url.searchParams.set('date_to', dateTo);

                const selectedOutletIds = getSelectedOutletIds();
                if (outletAllCheckboxEl.checked || selectedOutletIds.length === 0) {
                    url.searchParams.set('outlet_id', 'all');
                    return url.toString();
                }

                selectedOutletIds.forEach((outletId) => {
                    url.searchParams.append('outlet_ids[]', String(outletId));
                });

                return url.toString();
            }
            function buildSalesVsHppUrl(outletId = null) {
                const { dateFrom, dateTo } = getDateRange();
                const url = new URL(salesVsHppEndpoint, window.location.origin);
                url.searchParams.set('date_from', dateFrom);
                url.searchParams.set('date_to', dateTo);

                const normalizedOutletId = Number(outletId);
                if (Number.isInteger(normalizedOutletId) && normalizedOutletId > 0) {
                    url.searchParams.set('outlet_id', String(normalizedOutletId));
                }

                return url.toString();
            }
            function renderVelocityChart({ series, labels }) {
                latestHourlySeries = series;
                latestHourlyLabels = labels;

                const isMulti = series.length > 1;
                const isStacked = isMulti && hourlyStackMode === 'stacked';
                const hasData = series.some(s => s.data.some(v => v > 0));
                const chartTheme = getChartTheme();
                const tooltipLabels = labels;

                syncHourlyStackToggle(isMulti);
                hourlyEmptyEl.classList.toggle('hidden', hasData);

                // ApexCharts tidak selalu me-relayout saat 'stacked' diubah lewat updateOptions,
                // jadi buat ulang chart bila status tumpukannya berganti.
                if (hourlyChart && hourlyChartIsStacked !== isStacked) {
                    hourlyChart.destroy();
                    hourlyChart = null;
                }

                if (!hourlyChart) {
                    hourlyChartIsStacked = isStacked;
                    const options = {
                        series: series,
                        chart: {
                            type: 'area',
                            height: '100%',
                            stacked: isStacked,
                            toolbar: { show: false },
                            zoom: { enabled: false },
                            fontFamily: 'Inter, sans-serif',
                            background: 'transparent',
                            animations: {
                                enabled: true,
                                easing: 'easeinout',
                                speed: 800,
                            }
                        },
                        theme: { mode: chartTheme.dark ? 'dark' : 'light' },
                        dataLabels: { enabled: false },
                        stroke: {
                            curve: 'smooth',
                            width: 2,
                        },
                        fill: {
                            type: 'gradient',
                            gradient: {
                                shadeIntensity: 1,
                                opacityFrom: 0.45,
                                opacityTo: 0.05,
                                stops: [20, 100],
                            }
                        },
                        markers: {
                            size: 0,
                            strokeWidth: 2,
                            hover: { size: 6 }
                        },
                        xaxis: {
                            categories: labels,
                            labels: {
                                style: { colors: chartTheme.axisText, fontSize: '10px' },
                                rotate: 0,
                                hideOverlappingLabels: true,
                            },
                            axisBorder: { show: false },
                            axisTicks: { show: false }
                        },
                        yaxis: {
                            labels: {
                                formatter: (val) => {
                                    if (val >= 1000000) return (val / 1000000).toFixed(1) + 'M';
                                    if (val >= 1000) return (val / 1000).toFixed(0) + 'k';
                                    return val;
                                },
                                style: { colors: chartTheme.axisText, fontSize: '10px' }
                            },
                            tickAmount: 4,
                        },
                        grid: {
                            borderColor: chartTheme.grid,
                            strokeDashArray: 4,
                            padding: { left: 10, right: 10 }
                        },
                        tooltip: {
                            theme: chartTheme.tooltipTheme,
                            ...createCurrencyTooltip(tooltipLabels, {
                                filterZero: isMulti,
                                sortByValue: isMulti,
                                titlePrefix: 'Jam',
                            }),
                        },
                        colors: isMulti ? hourlyPalette : [chartBlue]
                    };

                    hourlyChart = new ApexCharts(document.querySelector("#hourlyBars"), options);
                    hourlyChart.render();
                } else {
                    const chartTheme = getChartTheme();
                    hourlyChart.updateOptions({
                        chart: { stacked: isStacked },
                        xaxis: {
                            categories: labels,
                            labels: {
                                style: { colors: chartTheme.axisText, fontSize: '10px' },
                                rotate: 0,
                                hideOverlappingLabels: true,
                            }
                        },
                        yaxis: {
                            labels: {
                                formatter: (val) => {
                                    if (val >= 1000000) return (val / 1000000).toFixed(1) + 'M';
                                    if (val >= 1000) return (val / 1000).toFixed(0) + 'k';
                                    return val;
                                },
                                style: { colors: chartTheme.axisText, fontSize: '10px' }
                            }
                        },
                        grid: { borderColor: chartTheme.grid },
                        tooltip: {
                            theme: chartTheme.tooltipTheme,
                            ...createCurrencyTooltip(tooltipLabels, {
                                filterZero: isMulti,
                                sortByValue: isMulti,
                                titlePrefix: 'Jam',
                            }),
                        },
                        theme: { mode: chartTheme.dark ? 'dark' : 'light' },
                        colors: isMulti ? hourlyPalette : [chartBlue]
                    });
                    hourlyChart.updateSeries(series);
                }
            }

            function renderCategoryRows(rows) {
                categoryListEl.innerHTML = '';
                if (!rows || rows.length === 0) {
                    categoryEmptyEl.classList.remove('hidden');
                    return;
                }

                categoryEmptyEl.classList.add('hidden');

                // Cari nilai tertinggi untuk skala progress bar
                // Agar bar terpanjang = 100% (atau relative terhadap total, tapi biasanya relative terhadap max item lebih bagus visualnya)
                const amounts = rows.map(r => Number(r.amount || 0));
                const maxVal = Math.max(...amounts, 0);

                // Warna progress bar (gradient-like or solid)
                // Kita pakai solid orange sesuai design user

                for (const row of rows) {
                    const amount = Number(row.amount || 0);
                    const pct = maxVal > 0 ? (amount / maxVal) * 100 : 0;

                    const item = document.createElement('div');
                    item.className = 'group';
                    item.innerHTML = `
                                                                                                                                                                    <div class="flex items-end justify-between mb-1.5">
                                                                                                                                                                        <span class="text-xs font-semibold text-slate-600 uppercase tracking-wide">${escapeHtml(row.category)}</span>
                                                                                                                                                                        <span class="text-sm font-bold text-slate-900">${idr.format(amount)}</span>
                                                                                                                                                                    </div>
                                                                                                                                                                    <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                                                                                                                                                                        <div class="bg-blue-500 h-2.5 rounded-full transition-all duration-700 ease-out group-hover:bg-blue-600 relative" style="width: ${pct}%">
                                                                                                                                                                        </div>
                                                                                                                                                                    </div>
                                                                                                                                                            `;
                    categoryListEl.appendChild(item);
                }
            }

            function getPaymentIcon(name) {
                const n = (name || '').toLowerCase();
                if (n.includes('cash') || n.includes('tunai')) return { icon: 'fa-money-bill-wave', bg: 'bg-emerald-50', text: 'text-emerald-500' };
                if (n.includes('qris')) return { icon: 'fa-qrcode', bg: 'bg-slate-100', text: 'text-slate-600' };
                if (n.includes('debit') || n.includes('credit') || n.includes('card') || n.includes('kartu')) return { icon: 'fa-credit-card', bg: 'bg-blue-50', text: 'text-blue-500' };
                if (n.includes('transfer')) return { icon: 'fa-money-bill-transfer', bg: 'bg-indigo-50', text: 'text-indigo-500' };
                if (n.includes('shopee')) return { icon: 'fa-wallet', bg: 'bg-orange-50', text: 'text-orange-500' };
                if (n.includes('gopay')) return { icon: 'fa-wallet', bg: 'bg-sky-50', text: 'text-sky-500' };
                if (n.includes('ovo')) return { icon: 'fa-wallet', bg: 'bg-violet-50', text: 'text-violet-500' };
                if (n.includes('dana')) return { icon: 'fa-wallet', bg: 'bg-blue-50', text: 'text-blue-500' };
                return { icon: 'fa-wallet', bg: 'bg-slate-50', text: 'text-slate-500' };
            }

            function renderPaymentRows(rows) {
                paymentListEl.innerHTML = '';
                if (!rows || rows.length === 0) {
                    paymentEmptyEl.classList.remove('hidden');
                    return;
                }

                paymentEmptyEl.classList.add('hidden');
                for (const row of rows) {
                    const style = getPaymentIcon(row.payment_method_name);

                    const item = document.createElement('div');
                    item.className = 'flex items-center justify-between p-3 rounded-xl border border-slate-100 hover:border-slate-200 hover:bg-slate-50 transition-colors group mr-1';
                    item.innerHTML = `
                                                                                                                                    <div class="flex items-center gap-3">
                                                                                                                                        <div class="flex items-center justify-center w-10 h-10 rounded-lg ${style.bg} transition-transform group-hover:scale-110">
                                                                                                                                            <i class="fas ${style.icon} text-lg ${style.text}"></i>
                                                                                                                                        </div>
                                                                                                                                        <span class="font-semibold text-slate-700">${escapeHtml(row.payment_method_name)}</span>
                                                                                                                                    </div>
                                                                                                                                    <span class="font-bold text-slate-900">${idr.format(Number(row.amount || 0))}</span>
                                                                                                                                `;
                    paymentListEl.appendChild(item);
                }
            }

            function renderTopProducts(rows) {
                productRowsEl.innerHTML = '';
                if (!rows || rows.length === 0) {
                    productEmptyEl.classList.remove('hidden');
                    return;
                }

                productEmptyEl.classList.add('hidden');
                for (const row of rows) {
                    const rank = productRowsEl.children.length + 1;
                    let trendBadge = '';
                    let movementClass = '';

                    const movement = row?.movement && typeof row.movement === 'object' ? row.movement : null;
                    const movementDir = movement?.direction === 'up' || movement?.direction === 'down' ? movement.direction : null;
                    const movementDelta = Number(movement?.delta || 0);

                    if (movementDir === 'up' && movementDelta > 0) {
                        trendBadge = `<span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700"><i class="fas fa-arrow-up"></i> +${movementDelta}</span>`;
                        movementClass = 'top-rank-up';
                    } else if (movementDir === 'down' && movementDelta > 0) {
                        trendBadge = `<span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-semibold text-rose-700"><i class="fas fa-arrow-down"></i> -${movementDelta}</span>`;
                        movementClass = 'top-rank-down';
                    }

                    const tr = document.createElement('tr');
                    if (movementClass) {
                        tr.classList.add(movementClass);
                    }

                    let imageHtml = '';
                    if (row.image_url) {
                        imageHtml = `<img src="${row.image_url}" class="w-10 h-10 rounded-lg object-cover border border-slate-100 bg-white shrink-0" alt="img" onerror="this.src='https://via.placeholder.com/48?text=IMG'"/>`;
                    } else {
                        imageHtml = `<div class="w-10 h-10 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center text-sm text-slate-400 shrink-0"><i class="fas fa-image"></i></div>`;
                    }

                    tr.innerHTML = `
                                <td class="px-2 py-3 text-slate-500 font-semibold text-center whitespace-nowrap text-[11px]">${rank}</td>
                                <td class="px-2 py-3 text-slate-700">
                                    <div class="flex items-center gap-3">
                                        ${imageHtml}
                                        <div class="min-w-0">
                                            <div class="flex flex-col">
                                                <div class="flex flex-wrap items-center gap-1.5">
                                                    <span class="font-bold text-slate-800 text-[12px] leading-tight break-words" title="${escapeHtml(row.product_name)}">${escapeHtml(row.product_name)}</span>
                                                    ${trendBadge}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-2 py-3 text-right text-slate-600 whitespace-nowrap text-[11px]">${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Number(row.qty || 0))}</td>
                                <td class="px-2 py-3 text-right font-black text-slate-900 pr-2 whitespace-nowrap text-[12px]">${idr.format(Number(row.amount || 0))}</td>
                            `;
                    productRowsEl.appendChild(tr);
                }
            }

            function renderOutletRows(rows, isAllOutlets) {
                latestOutletRows = Array.isArray(rows) ? rows : [];
                latestOutletIsAllOutlets = Boolean(isAllOutlets);
                outletPanelEl.classList.toggle('hidden', !isAllOutlets);
                if (!isAllOutlets) return;
                syncOutletChartModeToggle();

                const amounts = rows.map(x => Number(x.amount || 0));
                const hasData = amounts.some(a => a > 0);

                outletEmptyEl.classList.toggle('hidden', hasData);

                if (outletChart) {
                    outletChart.destroy();
                    outletChart = null;
                }

                if (hasData) {
                    outletChart = new ApexCharts(document.querySelector("#outletBars"), buildOutletChartOptions(rows));
                    outletChart.render();
                }

                const marginContainer = document.getElementById('outletMarginContainer');
                const marginList = document.getElementById('outletMarginList');
                if (marginContainer && marginList) {
                    if (hasData) {
                        marginContainer.classList.remove('hidden');
                        marginList.innerHTML = rows.map(r => {
                            const rev = Number(r.amount || 0);
                            const cogs = Number(r.cogs || 0);
                            const cogsPct = rev > 0 ? (cogs / rev * 100).toFixed(1) : 0;
                            const reportUrl = buildSalesVsHppUrl(r.outlet_id);
                            const cardTag = canOpenSalesVsHppReport ? 'a' : 'div';
                            const cardAttrs = canOpenSalesVsHppReport
                                ? `href="${escapeHtml(reportUrl)}" title="Lihat laporan Sales vs HPP"`
                                : '';
                            const cardClass = canOpenSalesVsHppReport
                                ? 'group relative block cursor-pointer overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition-all hover:shadow-xl hover:shadow-slate-200/50'
                                : 'group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition-all hover:shadow-xl hover:shadow-slate-200/50';
                            return `
                                <${cardTag} ${cardAttrs} class="${cardClass}">
                                    <div class="text-[11px] font-black tracking-widest uppercase text-slate-600 transition-colors duration-300 group-hover:text-blue-700 mb-2 truncate" title="${escapeHtml(r.outlet_name)}">${escapeHtml(r.outlet_name)}</div>
                                    <div class="flex flex-col gap-1 text-[10px] mb-1.5 font-bold">
                                        <div class="flex justify-between items-center text-blue-600 transition-colors duration-300 group-hover:text-blue-700"><span class="uppercase tracking-wide opacity-80">Penjualan</span><span>100%</span></div>
                                        <div class="flex justify-between items-center text-rose-600 transition-colors duration-300 group-hover:text-rose-700"><span class="uppercase tracking-wide opacity-80">HPP</span><span>${cogsPct}%</span></div>
                                    </div>
                                    <div class="w-full rounded-full bg-blue-500 h-1.5 overflow-hidden opacity-80 transition-all duration-300 group-hover:opacity-100">
                                        <div class="bg-rose-500 h-1.5 rounded-full transition-all duration-500" style="width: ${cogsPct}%"></div>
                                    </div>
                                    <div class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-transparent via-amber-400 to-transparent opacity-0 transition-opacity duration-500 group-hover:opacity-100"></div>
                                </${cardTag}>
                            `;
                        }).join('');
                    } else {
                        marginContainer.classList.add('hidden');
                    }
                }
            }

            function formatSignedPct(value) {
                if (value === null || value === undefined || Number.isNaN(Number(value))) return '-';
                const raw = Number(value);
                const n = Math.abs(raw) < 0.05 ? 0 : raw;
                const sign = n > 0 ? '+' : '';
                return `${sign}${n.toFixed(1)}%`;
            }

            // Removed renderOutletBreakdown

            function escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function render(data) {
                kpiTotalSalesEl.textContent = idr.format(Number(data?.kpis?.total_sales || 0));
                kpiAvgTransactionEl.textContent = idr.format(Number(data?.kpis?.avg_transaction || 0));
                const invoiceCount = Number(data?.kpis?.total_transactions || 0);
                kpiAvgInvoiceCountEl.textContent = `${new Intl.NumberFormat('id-ID').format(invoiceCount)} invoice`;

                const trendPct = data?.trend_vs_prev_day?.delta_total_sales_pct ?? null;
                const trendSalesAbs = data?.trend_vs_prev_day?.delta_total_sales ?? null;
                const trendPrev = data?.trend_vs_prev_day?.prev_total_sales ?? null;

                trendSalesEl.textContent = trendPrev === null ? '-' : `${idr.format(Number(trendSalesAbs || 0))} (${formatSignedPct(trendPct)})`;
                trendSalesPctEl.textContent = formatSignedPct(trendPct);

                const grossProfit = Number(data?.kpis?.gross_profit || 0);
                const grossMarginPct = data?.kpis?.gross_margin_pct ?? null;
                kpiGrossProfitEl.textContent = idr.format(grossProfit);
                kpiProfitPctEl.textContent = grossMarginPct === null ? '-' : `${Number(grossMarginPct).toFixed(1)}%`;
                kpiProfitPctEl.classList.toggle('text-emerald-500', grossProfit >= 0);
                kpiProfitPctEl.classList.toggle('text-rose-500', grossProfit < 0);

                const showBreakdown = Boolean(data?.show_breakdown);

                if (showBreakdown && Array.isArray(data?.hourly_stacked) && data.hourly_stacked.length > 0) {
                    const allOutletNames = new Set();
                    let hasOthers = false;

                    data.hourly_stacked.forEach(h => {
                        if (Array.isArray(h.segments)) {
                            h.segments.forEach(s => allOutletNames.add(s.outlet_name));
                        }
                        if (h.others && h.others.amount > 0) hasOthers = true;
                    });

                    const series = Array.from(allOutletNames).map(name => ({
                        name: name,
                        data: data.hourly_stacked.map(h => {
                            const seg = h.segments ? h.segments.find(s => s.outlet_name === name) : null;
                            return seg ? Number(seg.amount || 0) : 0;
                        })
                    }));

                    if (hasOthers) {
                        series.push({
                            name: 'Outlet Lainnya',
                            color: hourlyOthersColor,
                            data: data.hourly_stacked.map(h => Number(h.others?.amount || 0))
                        });
                    }

                    const labels = data.hourly_stacked.map(h => String(h.hour).padStart(2, '0') + ':00');
                    renderVelocityChart({ series, labels });
                } else {
                    const sales = Array.isArray(data?.sales_per_hour) ? data.sales_per_hour : [];
                    const series = [{
                        name: 'Omzet',
                        data: sales.map(s => Number(s.amount || s.total || 0))
                    }];
                    const labels = sales.map(s => String(s.hour).padStart(2, '0') + ':00');
                    renderVelocityChart({ series, labels });
                }
                renderCategoryRows(Array.isArray(data?.category_sales) ? data.category_sales : []);
                renderPaymentRows(Array.isArray(data?.payment_mix) ? data.payment_mix : []);
                renderTopProducts(Array.isArray(data?.top_products) ? data.top_products : []);
                renderOutletRows(Array.isArray(data?.outlet_sales) ? data.outlet_sales : [], showBreakdown);

                const generatedAt = data?.generated_at ? new Date(data.generated_at) : null;
                lastUpdatedEl.textContent = generatedAt ? generatedAt.toLocaleTimeString('id-ID') : '-';
            }

            async function fetchSummary(showSuccess = false) {
                if (isLoading) return;

                setLoadingState(true);
                setStatus('Memuat data...', 'info');

                try {
                    const res = await fetch(buildUrl(), {
                        headers: {
                            'Accept': 'application/json',
                        }
                    });

                    if (!res.ok) {
                        let msg = `Gagal memuat data (HTTP ${res.status}).`;
                        try {
                            const body = await res.json();
                            if (body?.message) msg = body.message;
                        } catch (e) { }
                        throw new Error(msg);
                    }

                    const data = await res.json();
                    render(data);
                    setStatus(showSuccess ? 'Berhasil diperbarui.' : 'Aktif (auto refresh).', showSuccess ? 'success' : 'info');
                } catch (err) {
                    setStatus(err?.message || 'Terjadi error saat memuat data.', 'error');
                } finally {
                    setLoadingState(false);
                }
            }

            function startAutoRefresh() {
                if (timer) clearInterval(timer);
                timer = setInterval(() => fetchSummary(false), 15000);
            }

            refreshBtn.addEventListener('click', () => fetchSummary(true));
            outletDropdownBtnEl.addEventListener('click', () => {
                outletDropdownMenuEl.classList.toggle('hidden');
            });
            outletChartModeToggleEl?.addEventListener('click', () => {
                outletChartMode = outletChartMode === 'area' ? 'bar' : 'area';
                syncOutletChartModeToggle();
                if (latestOutletIsAllOutlets) {
                    renderOutletRows(latestOutletRows, latestOutletIsAllOutlets);
                }
            });
            hourlyStackToggleEl?.addEventListener('click', () => {
                hourlyStackMode = hourlyStackMode === 'stacked' ? 'overlap' : 'stacked';
                if (latestHourlySeries && latestHourlyLabels) {
                    renderVelocityChart({ series: latestHourlySeries, labels: latestHourlyLabels });
                }
            });
            document.addEventListener('click', (event) => {
                if (outletFilterWrapEl.contains(event.target)) {
                    return;
                }

                outletDropdownMenuEl.classList.add('hidden');
            });
            outletAllCheckboxEl.addEventListener('change', () => {
                normalizeOutletSelection('all');
                fetchSummary(true);
            });
            outletCheckboxEls.forEach((checkbox) => {
                checkbox.addEventListener('change', () => {
                    normalizeOutletSelection('item');
                    fetchSummary(true);
                });
            });
            dateFromEl.addEventListener('change', () => {
                syncDatePresetState();
                fetchSummary(true);
            });
            dateToEl.addEventListener('change', () => {
                syncDatePresetState();
                fetchSummary(true);
            });
            datePresetBtns.forEach((button) => {
                button.addEventListener('click', () => {
                    const range = getPresetRange(button.dataset.rangePreset);
                    dateFromEl.value = range.dateFrom;
                    dateToEl.value = range.dateTo;
                    syncDatePresetState();
                    fetchSummary(true);
                });
            });
            window.addEventListener('theme:changed', () => fetchSummary(false));

            updateOutletLabel();
            syncDatePresetState();
            syncOutletChartModeToggle();
            fetchSummary(false);
            startAutoRefresh();
        })();
    </script>
@endpush

