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
                <div class="flex flex-col px-3">
                    <span class="text-[9px] font-black uppercase tracking-widest text-slate-400">Date Range</span>
                    <input id="date" type="date" value="{{ $defaultDate }}"
                        class="text-xs font-bold text-slate-700 bg-transparent outline-none cursor-pointer" />
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
                    <span>vs Yesterday:</span>
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
            </div>
            <div class="absolute bottom-0 left-0 h-1 w-0 bg-amber-500 transition-all duration-500 group-hover:w-full"></div>
        </div>

        {{-- Growth KPI --}}
        <div
            class="group relative rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition-all hover:shadow-xl hover:shadow-slate-200/50">
            <div class="flex items-center justify-between mb-4">
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-xl bg-rose-50 text-rose-600 transition-colors group-hover:bg-rose-600 group-hover:text-white">
                    <i class="fas fa-arrow-trend-up text-lg"></i>
                </div>
                <div class="text-right">
                    <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400">Daily
                        Progress</span>
                    <span class="text-xs font-black text-slate-400">TREND</span>
                </div>
            </div>
            <div class="space-y-1">
                <div id="targetWrap" class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span id="targetPct" class="text-3xl font-black text-slate-800 tracking-tight">0%</span>
                        <span id="targetValue"
                            class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Target: -</span>
                    </div>
                    <div class="h-2 w-full rounded-full bg-slate-100 overflow-hidden">
                        <div id="targetBar" class="h-full bg-rose-500 rounded-full transition-all duration-1000"
                            style="width: 0%"></div>
                    </div>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 h-1 w-0 bg-rose-600 transition-all duration-500 group-hover:w-full"></div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="dash-panel bg-white rounded-2xl shadow-sm border border-gray-100 p-6 lg:col-span-2"
            style="--dash-accent:#ea580c;--dash-accent-2:#f59e0b;--dash-accent-soft:rgba(234,88,12,0.13);">
            <div class="dash-card-head flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Omzet per Jam</h3>
                    <p class="text-xs text-slate-500">Berdasarkan jam transaksi (created_at)</p>
                </div>
                <div class="text-xs text-slate-500">00:00 - 23:00 • hover untuk detail</div>
            </div>

            <div class="h-64 w-full" id="hourlyBars"></div>
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

    <div id="outletPanel" class="dash-panel bg-white rounded-2xl shadow-sm border border-gray-100 p-6"
        style="--dash-accent:#ea580c;--dash-accent-2:#fb923c;--dash-accent-soft:rgba(234,88,12,0.13);">
        <div class="dash-card-head flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-bold text-slate-900">Omzet per Outlet</h3>
                <p class="text-xs text-slate-500">Muncul saat memilih "Semua Outlet"</p>
            </div>
        </div>

        <div id="outletBars" class="h-64 w-full"></div>

        <div id="outletEmpty" class="hidden text-center text-sm text-slate-500 py-6">Belum ada data.</div>
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
                radial-gradient(circle at top right, rgba(249, 115, 22, 0.16), transparent 42%),
                radial-gradient(circle at bottom left, rgba(251, 191, 36, 0.1), transparent 38%);
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
            background: linear-gradient(90deg, var(--dash-accent, #ec4913), var(--dash-accent-2, #fb923c));
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
            background: linear-gradient(90deg, var(--dash-accent-soft, rgba(236, 73, 19, 0.12)), rgba(255, 255, 255, 0));
        }

        .table-head-accent {
            background: linear-gradient(90deg, var(--dash-accent-soft, rgba(236, 73, 19, 0.12)), rgba(248, 250, 252, 0.85));
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
    </style>
@endpush


@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <script>
        (() => {
            let hourlyChart = null;
            let outletChart = null;
            const endpoint = @json(route('admin.dashboard.summary'));
            const outletFilterWrapEl = document.getElementById('outletFilterWrap');
            const outletDropdownBtnEl = document.getElementById('outletDropdownBtn');
            const outletDropdownLabelEl = document.getElementById('outletDropdownLabel');
            const outletDropdownMenuEl = document.getElementById('outletDropdownMenu');
            const outletAllCheckboxEl = document.getElementById('outletAllCheckbox');
            const outletCheckboxEls = Array.from(document.querySelectorAll('.outlet-checkbox'));
            const dateEl = document.getElementById('date');
            const refreshBtn = document.getElementById('refreshBtn');
            const statusTextEl = document.getElementById('statusText');
            const lastUpdatedEl = document.getElementById('lastUpdated');

            const kpiTotalSalesEl = document.getElementById('kpiTotalSales');
            const kpiAvgTransactionEl = document.getElementById('kpiAvgTransaction');
            const trendSalesEl = document.getElementById('trendSales');
            const trendSalesPctEl = document.getElementById('trendSalesPct');

            const targetWrapEl = document.getElementById('targetWrap');
            const targetValueEl = document.getElementById('targetValue');
            const targetPctEl = document.getElementById('targetPct');
            const targetBarEl = document.getElementById('targetBar');

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
            const idr = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0, });
            const hourlyPalette = ['#4F46E5', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4', '#F97316'];
            const hourlyOthersColor = '#94A3B8';
            let timer = null; let isLoading = false; let hourlyTooltipEl = null;
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
                let clean = name.replace(/moresto/gi, '').trim();
                if (clean.toLowerCase() === 'ciplaz') return 'Cplz';
                if (clean.length > 10 && clean.includes(' ')) {
                    return clean.split(/\s+/).map(w => w[0].toUpperCase()).join('');
                }
                return clean;
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
            function buildUrl() {
                const date = dateEl.value || @json($defaultDate);
                const url = new URL(endpoint, window.location.origin);
                url.searchParams.set('date', date);

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
            function renderVelocityChart({ series, labels }) {
                const isStacked = series.length > 1;
                const hasData = series.some(s => s.data.some(v => v > 0));

                hourlyEmptyEl.classList.toggle('hidden', hasData);

                if (!hourlyChart) {
                    const options = {
                        series: series,
                        chart: {
                            type: 'area',
                            height: 250,
                            stacked: isStacked,
                            toolbar: { show: false },
                            zoom: { enabled: false },
                            fontFamily: 'Inter, sans-serif',
                            animations: {
                                enabled: true,
                                easing: 'easeinout',
                                speed: 800,
                            }
                        },
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
                                style: { colors: '#94a3b8', fontSize: '10px' },
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
                                style: { colors: '#94a3b8', fontSize: '10px' }
                            },
                            tickAmount: 4,
                        },
                        grid: {
                            borderColor: '#f1f5f9',
                            strokeDashArray: 4,
                            padding: { left: 10, right: 10 }
                        },
                        tooltip: {
                            theme: 'light',
                            x: { show: true },
                            y: {
                                formatter: (val) => idr.format(val)
                            }
                        },
                        colors: isStacked ? hourlyPalette : ['#4F46E5']
                    };

                    hourlyChart = new ApexCharts(document.querySelector("#hourlyBars"), options);
                    hourlyChart.render();
                } else {
                    hourlyChart.updateOptions({
                        chart: { stacked: isStacked },
                        xaxis: { categories: labels },
                        colors: isStacked ? hourlyPalette : ['#4F46E5']
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
                                                                                                                                                                        <div class="bg-orange-500 h-2.5 rounded-full transition-all duration-700 ease-out group-hover:bg-orange-600 relative" style="width: ${pct}%">
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
                outletPanelEl.classList.toggle('hidden', !isAllOutlets);
                if (!isAllOutlets) return;

                const amounts = rows.map(x => Number(x.amount || 0));
                const labels = rows.map(x => simplifyOutletName(x.outlet_name));
                const hasData = amounts.some(a => a > 0);

                outletEmptyEl.classList.toggle('hidden', hasData);

                if (!outletChart) {
                    const options = {
                        series: [{
                            name: 'Omzet',
                            data: amounts
                        }],
                        chart: {
                            type: 'area',
                            height: 250,
                            toolbar: { show: false },
                            zoom: { enabled: false },
                            fontFamily: 'Inter, sans-serif',
                            animations: {
                                enabled: true,
                                easing: 'easeinout',
                                speed: 800,
                            }
                        },
                        dataLabels: { enabled: false },
                        stroke: {
                            curve: 'smooth',
                            width: 3,
                            colors: ['#f97316']
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
                            colors: ['#f97316'],
                            strokeColors: '#fff',
                            strokeWidth: 2,
                            hover: { size: 6 }
                        },
                        xaxis: {
                            categories: labels,
                            labels: {
                                style: { colors: '#94a3b8', fontSize: '11px' },
                                rotate: -45,
                                rotateAlways: false,
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
                                style: { colors: '#94a3b8', fontSize: '10px' }
                            },
                            tickAmount: 4,
                        },
                        grid: {
                            borderColor: '#f1f5f9',
                            strokeDashArray: 4,
                            padding: { left: 10, right: 10 }
                        },
                        tooltip: {
                            theme: 'light',
                            x: { show: true },
                            y: {
                                formatter: (val) => idr.format(val)
                            }
                        },
                        colors: ['#f97316']
                    };

                    outletChart = new ApexCharts(document.querySelector("#outletBars"), options);
                    outletChart.render();
                } else {
                    outletChart.updateOptions({
                        xaxis: { categories: labels }
                    });
                    outletChart.updateSeries([{
                        data: amounts
                    }]);
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

            function setTarget(target) {
                const dailyTarget = Number(target?.daily_sales_target || 0);
                const pct = target?.progress_pct === null || target?.progress_pct === undefined ? null : Number(target.progress_pct);

                if (!dailyTarget || dailyTarget <= 0 || pct === null) {
                    targetWrapEl.classList.add('hidden');
                    return;
                }

                targetWrapEl.classList.remove('hidden');
                targetValueEl.textContent = idr.format(dailyTarget);
                targetPctEl.textContent = `${pct.toFixed(0)}%`;
                targetBarEl.style.width = `${Math.min(100, Math.max(0, pct))}%`;
            }

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

                const trendPct = data?.trend_vs_prev_day?.delta_total_sales_pct ?? null;
                const trendSalesAbs = data?.trend_vs_prev_day?.delta_total_sales ?? null;
                const trendPrev = data?.trend_vs_prev_day?.prev_total_sales ?? null;

                trendSalesEl.textContent = trendPrev === null ? '-' : `${idr.format(Number(trendSalesAbs || 0))} (${formatSignedPct(trendPct)})`;
                trendSalesPctEl.textContent = formatSignedPct(trendPct);

                setTarget(data?.target);

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
                        name: simplifyOutletName(name),
                        data: data.hourly_stacked.map(h => {
                            const seg = h.segments ? h.segments.find(s => s.outlet_name === name) : null;
                            return seg ? Number(seg.amount || 0) : 0;
                        })
                    }));

                    if (hasOthers) {
                        series.push({
                            name: 'Others',
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
            dateEl.addEventListener('change', () => fetchSummary(true));

            updateOutletLabel();
            fetchSummary(false);
            startAutoRefresh();
        })();
    </script>
@endpush