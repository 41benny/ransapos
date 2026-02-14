@extends('layouts.admin')

@section('title', 'Dashboard')

@section('page-title', 'Dashboard Penjualan')

@section('content')
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
    <div class="flex flex-col lg:flex-row gap-4 lg:items-end lg:justify-between">
        <div>
            <p class="text-sm text-slate-500">Pantau omzet (near real-time)</p>
            <h2 class="text-xl font-bold text-slate-900">Ringkasan Penjualan</h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 w-full lg:w-auto">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Outlet</label>
                <select id="outletId"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-400">
                    <option value="all">Semua Outlet</option>
                    @foreach ($outlets as $outlet)
                        <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Tanggal</label>
                <input id="date" type="date" value="{{ $defaultDate }}"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-400" />
            </div>
            <div class="flex items-end gap-2">
                <button id="refreshBtn" type="button"
                    class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 text-white px-4 py-2 text-sm font-semibold hover:bg-blue-700 active:bg-blue-800 transition-colors">
                    <i class="fas fa-rotate"></i>
                    Refresh
                </button>
            </div>
        </div>
    </div>

    <div class="mt-4 flex items-center justify-between text-xs text-slate-500">
        <div id="statusText">Memuat data...</div>
        <div>Last updated: <span id="lastUpdated">-</span></div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-start justify-between mb-3">
            <div class="p-3 bg-green-50 rounded-xl">
                <i class="fas fa-coins text-green-600"></i>
            </div>
            <div class="relative group">
                <button type="button"
                    class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-slate-200 text-slate-500 hover:text-slate-700 hover:bg-slate-50 transition-colors"
                    aria-label="Lihat breakdown omzet per outlet">
                    <i class="fas fa-circle-info"></i>
                </button>
                <div
                    class="pointer-events-none absolute right-0 top-full mt-2 w-80 rounded-xl border border-slate-200 bg-white shadow-lg p-3 text-xs text-slate-700 opacity-0 translate-y-1 group-hover:opacity-100 group-hover:translate-y-0 transition-all">
                    <div class="font-semibold text-slate-900 mb-1">Breakdown Omzet per Outlet</div>
                    <div id="outletBreakdownHint" class="text-slate-600">Pilih <span class="font-semibold">Semua Outlet</span> untuk melihat rinciannya.</div>
                    <div id="outletBreakdownWrap" class="hidden mt-2">
                        <div class="max-h-56 overflow-auto rounded-lg border border-slate-100">
                            <table class="w-full">
                                <thead class="bg-slate-50 text-slate-600">
                                    <tr>
                                        <th class="text-left px-3 py-2 font-semibold">Outlet</th>
                                        <th class="text-right px-3 py-2 font-semibold">Omzet</th>
                                    </tr>
                                </thead>
                                <tbody id="outletBreakdownRows" class="divide-y divide-slate-100"></tbody>
                            </table>
                        </div>
                        <div class="mt-2 text-[11px] text-slate-500">Angka menghitung transaksi <span class="font-semibold">completed</span>.</div>
                    </div>
                </div>
            </div>
        </div>
        <p class="text-sm text-slate-500 font-medium mb-1">Omzet</p>
        <p id="kpiTotalSales" class="text-2xl font-bold text-slate-900">-</p>
        <div class="mt-2 text-xs text-slate-500 space-y-1">
            <div>Status: completed</div>
            <div>Vs kemarin: <span id="trendSales" class="font-semibold text-slate-700">-</span></div>
        </div>
        <div id="targetWrap" class="mt-3 hidden">
            <div class="flex items-center justify-between text-[11px] text-slate-500 mb-1">
                <div>Target harian</div>
                <div><span id="targetValue">-</span> (<span id="targetPct">-</span>)</div>
            </div>
            <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                <div id="targetBar" class="h-2 rounded-full bg-emerald-500/80" style="width: 0%"></div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-start justify-between mb-3">
            <div class="p-3 bg-blue-50 rounded-xl">
                <i class="fas fa-receipt text-blue-600"></i>
            </div>
        </div>
        <p class="text-sm text-slate-500 font-medium mb-1">Transaksi</p>
        <p id="kpiTransactions" class="text-2xl font-bold text-slate-900">-</p>
        <p class="text-xs text-slate-500 mt-2">Jumlah transaksi selesai</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-start justify-between mb-3">
            <div class="p-3 bg-purple-50 rounded-xl">
                <i class="fas fa-chart-simple text-purple-600"></i>
            </div>
        </div>
        <p class="text-sm text-slate-500 font-medium mb-1">Rata-rata Transaksi</p>
        <p id="kpiAvgTransaction" class="text-2xl font-bold text-slate-900">-</p>
        <p class="text-xs text-slate-500 mt-2">Omzet / transaksi</p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-start justify-between mb-3">
            <div class="p-3 bg-amber-50 rounded-xl">
                <i class="fas fa-tags text-amber-600"></i>
            </div>
        </div>
        <p class="text-sm text-slate-500 font-medium mb-1">Total Diskon</p>
        <p id="kpiDiscountTotal" class="text-2xl font-bold text-slate-900">-</p>
        <p class="text-xs text-slate-500 mt-2">Dari transaksi completed</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-start justify-between mb-3">
            <div class="p-3 bg-rose-50 rounded-xl">
                <i class="fas fa-ban text-rose-600"></i>
            </div>
        </div>
        <p class="text-sm text-slate-500 font-medium mb-1">Void (Cancelled)</p>
        <p class="text-2xl font-bold text-slate-900"><span id="kpiCancelledCount">-</span></p>
        <p class="text-xs text-slate-500 mt-2">Nilai: <span id="kpiCancelledAmount" class="font-semibold text-slate-700">-</span></p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-start justify-between mb-3">
            <div class="p-3 bg-slate-50 rounded-xl">
                <i class="fas fa-arrow-trend-up text-slate-700"></i>
            </div>
        </div>
        <p class="text-sm text-slate-500 font-medium mb-1">Trend vs Kemarin</p>
        <p id="trendSalesPct" class="text-2xl font-bold text-slate-900">-</p>
        <p class="text-xs text-slate-500 mt-2">Omzet & transaksi dibanding H-1</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 lg:col-span-2">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-bold text-slate-900">Omzet per Jam</h3>
                <p class="text-xs text-slate-500">Berdasarkan jam transaksi (created_at)</p>
            </div>
            <div class="text-xs text-slate-500">00:00 - 23:00 • hover untuk detail</div>
        </div>

        <div class="h-48 flex items-end gap-1 relative" id="hourlyBars" aria-label="Sales per hour chart"></div>
        <div class="mt-3 grid grid-cols-12 text-[10px] text-slate-400">
            @for ($i = 0; $i < 24; $i += 2)
                <div class="col-span-1 text-left">{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</div>
            @endfor
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-bold text-slate-900">Per Kategori</h3>
                <p class="text-xs text-slate-500">Top 10 (item subtotal)</p>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-100">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600 text-xs">
                    <tr>
                        <th class="text-left px-3 py-2 font-semibold">Kategori</th>
                        <th class="text-right px-3 py-2 font-semibold">Omzet</th>
                    </tr>
                </thead>
                <tbody id="categoryRows" class="divide-y divide-slate-100"></tbody>
            </table>
        </div>

        <div id="categoryEmpty" class="hidden text-center text-sm text-slate-500 py-6">Belum ada data.</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-bold text-slate-900">Metode Pembayaran</h3>
                <p class="text-xs text-slate-500">Komposisi pembayaran (completed)</p>
            </div>
        </div>
        <div class="overflow-hidden rounded-xl border border-slate-100">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600 text-xs">
                    <tr>
                        <th class="text-left px-3 py-2 font-semibold">Metode</th>
                        <th class="text-right px-3 py-2 font-semibold">Jumlah</th>
                    </tr>
                </thead>
                <tbody id="paymentRows" class="divide-y divide-slate-100"></tbody>
            </table>
        </div>
        <div id="paymentEmpty" class="hidden text-center text-sm text-slate-500 py-6">Belum ada data.</div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-bold text-slate-900">Top Produk</h3>
                <p class="text-xs text-slate-500">Top 10 (item subtotal)</p>
            </div>
        </div>
        <div class="overflow-hidden rounded-xl border border-slate-100">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600 text-xs">
                    <tr>
                        <th class="text-left px-3 py-2 font-semibold">Produk</th>
                        <th class="text-right px-3 py-2 font-semibold">Qty</th>
                        <th class="text-right px-3 py-2 font-semibold">Omzet</th>
                    </tr>
                </thead>
                <tbody id="productRows" class="divide-y divide-slate-100"></tbody>
            </table>
        </div>
        <div id="productEmpty" class="hidden text-center text-sm text-slate-500 py-6">Belum ada data.</div>
    </div>
</div>

<div id="outletPanel" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="text-lg font-bold text-slate-900">Omzet per Outlet</h3>
            <p class="text-xs text-slate-500">Muncul saat memilih "Semua Outlet"</p>
        </div>
    </div>

    <div id="outletBars" class="space-y-3"></div>

    <div id="outletEmpty" class="hidden text-center text-sm text-slate-500 py-6">Belum ada data.</div>
</div>

<noscript>
    <div class="mt-6 bg-amber-50 border border-amber-200 text-amber-900 rounded-xl p-4">
        Dashboard ini butuh JavaScript untuk update data otomatis.
    </div>
</noscript>

@endsection

@push('scripts')
    <script>
        (() => {
            const endpoint = @json(route('admin.dashboard.summary'));

            const outletIdEl = document.getElementById('outletId');
            const dateEl = document.getElementById('date');
            const refreshBtn = document.getElementById('refreshBtn');

            const statusTextEl = document.getElementById('statusText');
            const lastUpdatedEl = document.getElementById('lastUpdated');

            const outletBreakdownHintEl = document.getElementById('outletBreakdownHint');
            const outletBreakdownWrapEl = document.getElementById('outletBreakdownWrap');
            const outletBreakdownRowsEl = document.getElementById('outletBreakdownRows');

            const kpiTotalSalesEl = document.getElementById('kpiTotalSales');
            const kpiTransactionsEl = document.getElementById('kpiTransactions');
            const kpiAvgTransactionEl = document.getElementById('kpiAvgTransaction');
            const kpiDiscountTotalEl = document.getElementById('kpiDiscountTotal');
            const kpiCancelledCountEl = document.getElementById('kpiCancelledCount');
            const kpiCancelledAmountEl = document.getElementById('kpiCancelledAmount');

            const trendSalesEl = document.getElementById('trendSales');
            const trendSalesPctEl = document.getElementById('trendSalesPct');

            const targetWrapEl = document.getElementById('targetWrap');
            const targetValueEl = document.getElementById('targetValue');
            const targetPctEl = document.getElementById('targetPct');
            const targetBarEl = document.getElementById('targetBar');

            const hourlyBarsEl = document.getElementById('hourlyBars');

            const categoryRowsEl = document.getElementById('categoryRows');
            const categoryEmptyEl = document.getElementById('categoryEmpty');

            const paymentRowsEl = document.getElementById('paymentRows');
            const paymentEmptyEl = document.getElementById('paymentEmpty');

            const productRowsEl = document.getElementById('productRows');
            const productEmptyEl = document.getElementById('productEmpty');

            const outletPanelEl = document.getElementById('outletPanel');
            const outletBarsEl = document.getElementById('outletBars');
            const outletEmptyEl = document.getElementById('outletEmpty');

            const idr = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                maximumFractionDigits: 0,
            });

            const hourlyPalette = [
                'bg-emerald-500/80',
                'bg-blue-500/80',
                'bg-amber-500/80',
                'bg-violet-500/80',
                'bg-rose-500/80',
            ];
            const hourlyOthersClass = 'bg-slate-400/80';

            let timer = null;
            let isLoading = false;
            let hourlyTooltipEl = null;

            function setStatus(text, type = 'info') {
                statusTextEl.textContent = text;
                statusTextEl.className = 'text-xs';

                if (type === 'error') {
                    statusTextEl.classList.add('text-red-600');
                } else if (type === 'success') {
                    statusTextEl.classList.add('text-green-600');
                } else {
                    statusTextEl.classList.add('text-slate-500');
                }
            }

            function setLoadingState(loading) {
                isLoading = loading;
                refreshBtn.disabled = loading;
                refreshBtn.classList.toggle('opacity-60', loading);
                refreshBtn.classList.toggle('cursor-not-allowed', loading);
            }

            function buildUrl() {
                const outletId = outletIdEl.value || 'all';
                const date = dateEl.value || @json($defaultDate);
                const url = new URL(endpoint, window.location.origin);
                url.searchParams.set('outlet_id', outletId);
                url.searchParams.set('date', date);
                return url.toString();
            }

            function ensureHourlyTooltip() {
                if (hourlyTooltipEl) return hourlyTooltipEl;

                hourlyTooltipEl = document.createElement('div');
                hourlyTooltipEl.className = 'absolute z-20 hidden min-w-56 max-w-sm rounded-xl border border-slate-200 bg-white shadow-lg p-3 text-xs text-slate-700';
                hourlyTooltipEl.style.left = '0px';
                hourlyTooltipEl.style.top = '0px';
                hourlyBarsEl.appendChild(hourlyTooltipEl);
                return hourlyTooltipEl;
            }

            function showHourlyTooltip(html, clientX, clientY) {
                const tip = ensureHourlyTooltip();
                tip.innerHTML = html;
                tip.classList.remove('hidden');

                const rect = hourlyBarsEl.getBoundingClientRect();
                const padding = 12;

                const desiredLeft = clientX - rect.left + 10;
                const desiredTop = clientY - rect.top + 10;

                tip.style.left = `${Math.max(padding, Math.min(desiredLeft, rect.width - tip.offsetWidth - padding))}px`;
                tip.style.top = `${Math.max(padding, Math.min(desiredTop, rect.height - tip.offsetHeight - padding))}px`;
            }

            function hideHourlyTooltip() {
                if (!hourlyTooltipEl) return;
                hourlyTooltipEl.classList.add('hidden');
            }

            function renderHourlyBars(series) {
                const max = Math.max(...series.map(x => Number(x.amount || 0)), 0);

                hourlyBarsEl.innerHTML = '';
                for (const point of series) {
                    const amount = Number(point.amount || 0);
                    const pct = max > 0 ? Math.max(2, Math.round((amount / max) * 100)) : 2;
                    const bar = document.createElement('div');
                    bar.className = 'flex-1 rounded-t-lg bg-blue-500/70 hover:bg-blue-600 transition-colors';
                    bar.style.height = `${pct}%`;
                    bar.title = `${String(point.hour).padStart(2,'0')}:00 - ${idr.format(amount)}`;
                    hourlyBarsEl.appendChild(bar);
                }
            }

            function renderHourlyStacked(hourlyStacked, meta) {
                const topOutlets = Array.isArray(meta?.top_outlets) ? meta.top_outlets : [];
                const topIdToIndex = new Map(topOutlets.map((o, idx) => [Number(o.outlet_id), idx]));

                const maxTotal = Math.max(...hourlyStacked.map(x => Number(x.total || 0)), 0);

                hourlyBarsEl.innerHTML = '';
                hourlyTooltipEl = null;

                for (const point of hourlyStacked) {
                    const hour = Number(point.hour || 0);
                    const total = Number(point.total || 0);
                    const barHeightPct = maxTotal > 0 ? Math.max(2, Math.round((total / maxTotal) * 100)) : 2;

                    const outer = document.createElement('div');
                    outer.className = 'flex-1 rounded-t-lg bg-slate-100 overflow-hidden';
                    outer.style.height = `${barHeightPct}%`;

                    const stack = document.createElement('div');
                    stack.className = 'h-full w-full flex flex-col-reverse';

                    const segments = Array.isArray(point.segments) ? point.segments : [];
                    const others = point.others || null;

                    for (const seg of segments) {
                        const amt = Number(seg.amount || 0);
                        if (amt <= 0 || total <= 0) continue;

                        const idx = topIdToIndex.has(Number(seg.outlet_id)) ? topIdToIndex.get(Number(seg.outlet_id)) : 0;
                        const colorClass = hourlyPalette[idx % hourlyPalette.length];

                        const segEl = document.createElement('div');
                        segEl.className = `w-full ${colorClass}`;
                        segEl.style.height = `${(amt / total) * 100}%`;

                        const outletName = escapeHtml(seg.outlet_name);
                        const tooltipHtml = `
                            <div class="font-semibold text-slate-900 mb-1">${String(hour).padStart(2,'0')}:00</div>
                            <div class="text-slate-700">${outletName}: <span class="font-semibold">${escapeHtml(idr.format(amt))}</span></div>
                            <div class="mt-1 text-slate-500">Total jam ini: ${escapeHtml(idr.format(total))}</div>
                        `;

                        segEl.addEventListener('mouseenter', (e) => showHourlyTooltip(tooltipHtml, e.clientX, e.clientY));
                        segEl.addEventListener('mousemove', (e) => showHourlyTooltip(tooltipHtml, e.clientX, e.clientY));
                        segEl.addEventListener('mouseleave', hideHourlyTooltip);

                        stack.appendChild(segEl);
                    }

                    const othersAmt = Number(others?.amount || 0);
                    if (othersAmt > 0 && total > 0) {
                        const segEl = document.createElement('div');
                        segEl.className = `w-full ${hourlyOthersClass}`;
                        segEl.style.height = `${(othersAmt / total) * 100}%`;

                        const breakdown = Array.isArray(others?.breakdown) ? others.breakdown : [];
                        const rows = breakdown
                            .filter(r => Number(r.amount || 0) > 0)
                            .slice(0, 8)
                            .map(r => `<div class="flex items-center justify-between gap-3"><div class="truncate" title="${escapeHtml(r.outlet_name)}">${escapeHtml(r.outlet_name)}</div><div class="font-semibold">${escapeHtml(idr.format(Number(r.amount || 0)))}</div></div>`)
                            .join('');
                        const moreCount = Math.max(0, breakdown.length - 8);

                        const tooltipHtml = `
                            <div class="font-semibold text-slate-900 mb-1">${String(hour).padStart(2,'0')}:00</div>
                            <div class="text-slate-700">Others: <span class="font-semibold">${escapeHtml(idr.format(othersAmt))}</span></div>
                            <div class="mt-2 text-slate-600 space-y-1">${rows || '<div class="text-slate-500">Tidak ada breakdown.</div>'}</div>
                            ${moreCount > 0 ? `<div class="mt-2 text-[11px] text-slate-500">+${moreCount} outlet lainnya</div>` : ''}
                            <div class="mt-2 text-slate-500">Total jam ini: ${escapeHtml(idr.format(total))}</div>
                        `;

                        segEl.addEventListener('mouseenter', (e) => showHourlyTooltip(tooltipHtml, e.clientX, e.clientY));
                        segEl.addEventListener('mousemove', (e) => showHourlyTooltip(tooltipHtml, e.clientX, e.clientY));
                        segEl.addEventListener('mouseleave', hideHourlyTooltip);

                        stack.appendChild(segEl);
                    }

                    outer.title = `${String(hour).padStart(2,'0')}:00 - ${idr.format(total)}`;
                    outer.appendChild(stack);
                    hourlyBarsEl.appendChild(outer);
                }
            }

            function renderCategoryRows(rows) {
                categoryRowsEl.innerHTML = '';
                if (!rows || rows.length === 0) {
                    categoryEmptyEl.classList.remove('hidden');
                    return;
                }

                categoryEmptyEl.classList.add('hidden');
                for (const row of rows) {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td class="px-3 py-2 text-slate-700">${escapeHtml(row.category)}</td>
                        <td class="px-3 py-2 text-right font-semibold text-slate-900">${idr.format(Number(row.amount || 0))}</td>
                    `;
                    categoryRowsEl.appendChild(tr);
                }
            }

            function renderPaymentRows(rows) {
                paymentRowsEl.innerHTML = '';
                if (!rows || rows.length === 0) {
                    paymentEmptyEl.classList.remove('hidden');
                    return;
                }

                paymentEmptyEl.classList.add('hidden');
                for (const row of rows) {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td class="px-3 py-2 text-slate-700">${escapeHtml(row.payment_method_name)}</td>
                        <td class="px-3 py-2 text-right font-semibold text-slate-900">${idr.format(Number(row.amount || 0))}</td>
                    `;
                    paymentRowsEl.appendChild(tr);
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
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td class="px-3 py-2 text-slate-700">${escapeHtml(row.product_name)}</td>
                        <td class="px-3 py-2 text-right text-slate-700">${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Number(row.qty || 0))}</td>
                        <td class="px-3 py-2 text-right font-semibold text-slate-900">${idr.format(Number(row.amount || 0))}</td>
                    `;
                    productRowsEl.appendChild(tr);
                }
            }

            function renderOutletRows(rows, isAllOutlets) {
                outletPanelEl.classList.toggle('hidden', !isAllOutlets);
                if (!isAllOutlets) return;

                outletBarsEl.innerHTML = '';
                if (!rows || rows.length === 0) {
                    outletEmptyEl.classList.remove('hidden');
                    return;
                }

                outletEmptyEl.classList.add('hidden');

                const max = Math.max(...rows.map(x => Number(x.amount || 0)), 0);
                for (const row of rows) {
                    const amount = Number(row.amount || 0);
                    const pct = max > 0 ? Math.max(4, Math.round((amount / max) * 100)) : 0;
                    const transactions = Number(row.transactions || 0);
                    const lastSaleAt = row.last_sale_at ? new Date(row.last_sale_at) : null;

                    const wrap = document.createElement('div');
                    wrap.className = 'grid grid-cols-12 gap-3 items-center';
                    wrap.innerHTML = `
                        <div class="col-span-4 sm:col-span-3">
                            <div class="text-sm text-slate-700 truncate" title="${escapeHtml(row.outlet_name)}">${escapeHtml(row.outlet_name)}</div>
                            <div class="text-[11px] text-slate-500">
                                ${transactions} trx${lastSaleAt ? ` • last: ${lastSaleAt.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}` : ''}
                            </div>
                        </div>
                        <div class="col-span-5 sm:col-span-7">
                            <div class="h-3 rounded-full bg-slate-100 overflow-hidden">
                                <div class="h-3 rounded-full bg-emerald-500/80" style="width: ${pct}%"></div>
                            </div>
                        </div>
                        <div class="col-span-3 sm:col-span-2 text-right text-sm font-semibold text-slate-900">${idr.format(amount)}</div>
                    `;
                    outletBarsEl.appendChild(wrap);
                }
            }

            function formatSignedPct(value) {
                if (value === null || value === undefined || Number.isNaN(Number(value))) return '-';
                const n = Number(value);
                const sign = n > 0 ? '+' : '';
                return `${sign}${n.toFixed(1)}%`;
            }

            function renderOutletBreakdown(rows, isAllOutlets) {
                outletBreakdownRowsEl.innerHTML = '';

                if (!isAllOutlets) {
                    outletBreakdownHintEl.classList.remove('hidden');
                    outletBreakdownWrapEl.classList.add('hidden');
                    return;
                }

                outletBreakdownHintEl.classList.add('hidden');
                outletBreakdownWrapEl.classList.remove('hidden');

                const safeRows = Array.isArray(rows) ? rows : [];
                if (safeRows.length === 0) {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `<td class="px-3 py-2 text-slate-500" colspan="2">Belum ada data.</td>`;
                    outletBreakdownRowsEl.appendChild(tr);
                    return;
                }

                for (const row of safeRows) {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td class="px-3 py-2 text-slate-700">${escapeHtml(row.outlet_name)}</td>
                        <td class="px-3 py-2 text-right font-semibold text-slate-900">${idr.format(Number(row.amount || 0))}</td>
                    `;
                    outletBreakdownRowsEl.appendChild(tr);
                }
            }

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
                kpiTransactionsEl.textContent = new Intl.NumberFormat('id-ID').format(Number(data?.kpis?.total_transactions || 0));
                kpiAvgTransactionEl.textContent = idr.format(Number(data?.kpis?.avg_transaction || 0));
                kpiDiscountTotalEl.textContent = idr.format(Number(data?.kpis?.discount_total || 0));
                kpiCancelledCountEl.textContent = new Intl.NumberFormat('id-ID').format(Number(data?.kpis?.cancelled_transactions || 0));
                kpiCancelledAmountEl.textContent = idr.format(Number(data?.kpis?.cancelled_amount || 0));

                const trendPct = data?.trend_vs_prev_day?.delta_total_sales_pct ?? null;
                const trendSalesAbs = data?.trend_vs_prev_day?.delta_total_sales ?? null;
                const trendPrev = data?.trend_vs_prev_day?.prev_total_sales ?? null;

                trendSalesEl.textContent = trendPrev === null ? '-' : `${idr.format(Number(trendSalesAbs || 0))} (${formatSignedPct(trendPct)})`;
                trendSalesPctEl.textContent = formatSignedPct(trendPct);

                setTarget(data?.target);

                if (!data?.outlet_id && Array.isArray(data?.hourly_stacked)) {
                    renderHourlyStacked(data.hourly_stacked, data?.hourly_stacked_meta);
                } else {
                    renderHourlyBars(Array.isArray(data?.sales_per_hour) ? data.sales_per_hour : []);
                }
                renderCategoryRows(Array.isArray(data?.category_sales) ? data.category_sales : []);
                renderPaymentRows(Array.isArray(data?.payment_mix) ? data.payment_mix : []);
                renderTopProducts(Array.isArray(data?.top_products) ? data.top_products : []);
                renderOutletBreakdown(Array.isArray(data?.outlet_sales) ? data.outlet_sales : [], !data?.outlet_id);
                renderOutletRows(Array.isArray(data?.outlet_sales) ? data.outlet_sales : [], !data?.outlet_id);

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
                        } catch (e) {}
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
            outletIdEl.addEventListener('change', () => fetchSummary(true));
            dateEl.addEventListener('change', () => fetchSummary(true));

            fetchSummary(false);
            startAutoRefresh();
        })();
    </script>
@endpush
