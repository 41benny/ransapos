@extends('layouts.admin')

@section('title', 'Profit & Loss Statement')
@section('page-title', 'Profit & Loss Statement')
@section('page-subtitle', 'Reports & Analytics')

@section('content')
    <div class="w-full py-4 animate-in fade-in slide-in-from-bottom-2 duration-500">
        {{-- Header Section --}}
        <div class="flex flex-col gap-6 mb-8 no-print">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">Laporan & Analitik</span>
                    <h1 class="text-3xl font-black text-slate-800 tracking-tight mt-1">Laba & Rugi</h1>
                </div>
                <a href="{{ route('admin.reports.index', ['tab' => 'ikhtisar']) }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 transition-all shadow-sm active:scale-95">
                    <i class="fas fa-arrow-left text-xs"></i>
                    <span>Kembali ke Katalog</span>
                </a>
            </div>
            
            <form method="GET" class="flex flex-wrap items-center gap-3 bg-white p-2 rounded-2xl border border-slate-200 shadow-sm w-fit">
                <input type="hidden" name="tab" value="{{ request('tab', 'ikhtisar') }}">
                <div class="flex flex-col px-3 border-r border-slate-100">
                    <span class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1 flex items-center gap-1">
                        <i class="far fa-calendar text-[10px]"></i> Rentang Tanggal
                    </span>
                    <div class="flex items-center gap-2">
                        <input type="date" name="date_from" value="{{ $dateFrom }}"
                            class="text-xs font-bold text-slate-700 bg-transparent outline-none border-none p-0 focus:ring-0">
                        <span class="text-slate-300">—</span>
                        <input type="date" name="date_to" value="{{ $dateTo }}"
                            class="text-xs font-bold text-slate-700 bg-transparent outline-none border-none p-0 focus:ring-0">
                    </div>
                </div>

                <div class="flex flex-col px-3 border-r border-slate-100">
                    <span class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1 flex items-center gap-1">
                        <i class="fas fa-store text-[10px]"></i> Outlet
                    </span>
                    <select name="outlet_id" onchange="this.form.submit()"
                        class="text-xs font-bold text-slate-700 bg-transparent outline-none border-none p-0 focus:ring-0 cursor-pointer appearance-none min-w-[120px]">
                        <option value="">All Outlets</option>
                        @foreach($outlets as $outlet)
                            <option value="{{ $outlet->id }}" {{ $outletId == $outlet->id ? 'selected' : '' }}>
                                {{ $outlet->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-2 px-1">
                    <button type="submit"
                        class="ui-btn ui-btn-primary inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-indigo-100 hover:bg-indigo-700 active:scale-95 transition-all">
                        <i class="fas fa-sync-alt text-[10px]"></i>
                        <span>Update</span>
                    </button>
                    
                    <a href="{{ route('admin.reports.profit-loss.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}"
                        class="h-10 w-10 flex items-center justify-center rounded-xl bg-slate-50 text-slate-400 hover:bg-indigo-50 hover:text-indigo-600 border border-slate-200 transition-all" title="Download Excel">
                        <i class="fas fa-download text-sm"></i>
                    </a>
                </div>
            </form>
        </div>

        {{-- Summary KPI Row --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Pendapatan -->
            <a href="{{ route('admin.reports.sales.index', ['date_from' => $dateFrom, 'date_to' => $dateTo, 'outlet_id' => $outletId]) }}" target="_blank"
                class="ui-card bg-white rounded-2xl border border-slate-200 p-6 shadow-sm relative overflow-hidden group hover:border-indigo-500 transition-all active:scale-95 cursor-pointer">
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Total Pendapatan</span>
                <h3 class="text-2xl font-black text-slate-800 mt-2 tracking-tight flex items-center gap-2">
                    Rp {{ number_format($report['total_revenue'], 0, ',', '.') }}
                    <i class="fas fa-external-link-alt text-[10px] text-slate-300 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                </h3>
                <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                    <i class="fas fa-money-bill-wave text-5xl"></i>
                </div>
            </a>

            <!-- COGS -->
            <a href="{{ route('admin.stocks.mutations', ['start_date' => $dateFrom, 'end_date' => $dateTo, 'outlet_id' => $outletId, 'reference_scope' => 'sales_cogs']) }}" target="_blank"
                class="ui-card bg-white rounded-2xl border border-slate-200 p-6 shadow-sm relative overflow-hidden group border-l-4 border-l-rose-500 hover:border-indigo-500 transition-all active:scale-95 cursor-pointer">
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-rose-500">COGS (HPP)</span>
                <h3 class="text-2xl font-black text-rose-500 mt-2 tracking-tight flex items-center gap-2">
                    Rp {{ number_format($report['total_cogs'] ?? 0, 0, ',', '.') }}
                    <i class="fas fa-external-link-alt text-[10px] text-rose-300 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                </h3>
                <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                    <i class="fas fa-boxes text-5xl text-rose-500"></i>
                </div>
            </a>

            <!-- Laba Kotor -->
            <div class="ui-card bg-white rounded-2xl border border-slate-200 p-6 shadow-sm relative overflow-hidden group border-l-4 border-l-emerald-500">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-black uppercase tracking-[0.2em] text-emerald-500">Laba Kotor</span>
                    <span class="bg-emerald-100 text-emerald-700 text-[10px] font-black px-2 py-0.5 rounded-full">
                        {{ number_format($report['gross_profit_margin'], 1) }}%
                    </span>
                </div>
                <h3 class="text-2xl font-black text-emerald-500 mt-2 tracking-tight">
                    Rp {{ number_format($report['gross_profit'], 0, ',', '.') }}
                </h3>
                <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                    <i class="fas fa-chart-line text-5xl text-emerald-500"></i>
                </div>
            </div>

            <!-- Laba Bersih -->
            <div class="ui-card bg-white rounded-2xl border border-slate-200 p-6 shadow-sm relative overflow-hidden group border-l-4 border-l-indigo-500">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-black uppercase tracking-[0.2em] text-indigo-500">Laba Bersih</span>
                    <span class="bg-indigo-100 text-indigo-700 text-[10px] font-black px-2 py-0.5 rounded-full">
                        {{ number_format($report['net_profit_margin'], 1) }}%
                    </span>
                </div>
                <h3 class="text-2xl font-black text-indigo-600 mt-2 tracking-tight">
                    Rp {{ number_format($report['net_profit'], 0, ',', '.') }}
                </h3>
                <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                    <i class="fas fa-wallet text-5xl text-indigo-600"></i>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            {{-- Main Table Section (7/12) --}}
            <div class="lg:col-span-8 flex flex-col gap-6">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col h-full">
                    <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-black text-slate-800">Rincian Keuangan</h3>
                            <p class="mt-1 text-[11px] font-medium text-slate-400">
                                Klik nama grup biaya untuk buka atau tutup rincian akun di bawahnya.
                            </p>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-slate-50/50">
                                    <th class="text-left px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Keterangan</th>
                                    <th class="text-center px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Kode Akun</th>
                                    <th class="text-right px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Saldo</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                {{-- REVENUE --}}
                                <tr class="group hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <i class="fas fa-chevron-down text-[8px] text-slate-300"></i>
                                            <span class="text-sm font-bold text-slate-800">Total Pendapatan</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-[10px] font-bold text-slate-400 tracking-widest">4-0000</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('admin.reports.sales.index', ['date_from' => $dateFrom, 'date_to' => $dateTo, 'outlet_id' => $outletId]) }}" target="_blank"
                                            class="text-sm font-bold text-slate-800 hover:text-indigo-600 transition-colors">
                                            Rp {{ number_format($report['total_revenue'], 0, ',', '.') }}
                                        </a>
                                    </td>
                                </tr>

                                {{-- COGS --}}
                                <tr class="group hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <i class="fas fa-chevron-down text-[8px] text-slate-300"></i>
                                            <span class="text-sm font-bold text-slate-800">Cost of Goods Sold (HPP)</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-[10px] font-bold text-slate-400 tracking-widest">5-0000</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('admin.stocks.mutations', ['start_date' => $dateFrom, 'end_date' => $dateTo, 'outlet_id' => $outletId, 'reference_scope' => 'sales_cogs']) }}" target="_blank"
                                            class="text-sm font-bold text-rose-500 hover:text-indigo-600 transition-colors">
                                            Rp {{ number_format($report['total_cogs'], 0, ',', '.') }}
                                        </a>
                                    </td>
                                </tr>

                                {{-- GROSS PROFIT --}}
                                <tr class="bg-emerald-50/70 border-y border-emerald-100 dark:bg-emerald-950/30 dark:border-emerald-800/80">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-900/70 dark:text-emerald-300">
                                                <i class="fas fa-chart-line text-[10px]"></i>
                                            </span>
                                            <span class="text-sm font-black uppercase tracking-wider text-emerald-700 dark:text-emerald-300">Laba Kotor</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-[10px] font-bold uppercase tracking-widest text-emerald-400 dark:text-emerald-300">Summary</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span class="text-sm font-black text-emerald-700 dark:text-emerald-300">
                                            Rp {{ number_format($report['gross_profit'], 0, ',', '.') }}
                                        </span>
                                    </td>
                                </tr>

                                {{-- EXPENSES BY GROUP --}}
                                @foreach($report['expenses_by_group'] as $group)
                                    @php
                                        $accountCount = count($group['accounts']);
                                        $groupKey = 'expense-group-' . $loop->index;
                                        $groupTitle = $accountCount === 1
                                            ? ($group['accounts'][0]['name'] ?? $group['group_name'])
                                            : $group['group_name'];
                                        $groupMeta = $accountCount === 1
                                            ? 'Grup ' . $group['group_name'] . ' - 1 akun'
                                            : 'Total grup - ' . $accountCount . ' akun';
                                    @endphp

                                    <tr class="bg-slate-50/70 border-t border-slate-100 dark:bg-slate-800/80 dark:border-slate-700">
                                        <td class="px-6 py-4" colspan="2">
                                            <button type="button"
                                                class="flex w-full items-center gap-3 text-left"
                                                data-expense-toggle="{{ $groupKey }}"
                                                aria-expanded="true">
                                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-white text-slate-400 shadow-sm transition-transform duration-200 dark:bg-slate-900 dark:text-slate-300 dark:shadow-none"
                                                    data-expense-chevron>
                                                    <i class="fas fa-chevron-down text-[10px]"></i>
                                                </span>
                                                <span class="inline-flex items-center rounded-full bg-slate-200/70 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.2em] text-slate-500 dark:bg-slate-700 dark:text-slate-200">
                                                    {{ $groupTitle }}
                                                </span>
                                                <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-400">
                                                    {{ $groupMeta }}
                                                </span>
                                            </button>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <a href="{{ route('admin.cash-transactions.index', ['date_from' => $dateFrom, 'date_to' => $dateTo, 'outlet_id' => $outletId, 'type' => 'out', 'coa_type' => 'expense', 'coa_group' => $group['group_name']]) }}" target="_blank"
                                                class="text-sm font-black text-slate-700 hover:text-indigo-600 transition-colors dark:text-slate-100 dark:hover:text-indigo-300">
                                                Rp {{ number_format($group['total'], 0, ',', '.') }}
                                            </a>
                                        </td>
                                    </tr>

                                    @foreach($group['accounts'] as $account)
                                        <tr class="group hover:bg-slate-50 transition-colors dark:hover:bg-slate-800/70"
                                            data-expense-detail="{{ $groupKey }}">
                                            <td class="px-10 py-3">
                                                <div class="flex items-center gap-3">
                                                    <span class="h-2 w-2 rounded-full bg-slate-300 dark:bg-slate-500"></span>

                                                    <div class="flex flex-col gap-1">
                                                        <span class="text-sm text-slate-600 dark:text-slate-200">
                                                            {{ $account['name'] }}
                                                        </span>
                                                        <span class="inline-flex w-fit items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-black uppercase tracking-[0.18em] text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                                                            Akun detail
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-3 text-center">
                                                <span class="text-[10px] font-medium text-slate-400 tracking-widest dark:text-slate-400">{{ $account['code'] }}</span>
                                            </td>
                                            <td class="px-6 py-3 text-right">
                                                <a href="{{ route('admin.cash-transactions.index', ['date_from' => $dateFrom, 'date_to' => $dateTo, 'outlet_id' => $outletId, 'type' => 'out', 'coa_account_id' => $account['id']]) }}" target="_blank"
                                                    class="text-sm font-medium text-slate-700 hover:text-indigo-600 transition-colors dark:text-slate-200 dark:hover:text-indigo-300">
                                                    Rp {{ number_format($account['amount'], 0, ',', '.') }}
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-indigo-50/50 dark:bg-slate-800/90 dark:border-t dark:border-slate-700">
                                    <td class="px-6 py-6" colspan="2">
                                        <span class="text-sm font-black text-indigo-700 uppercase tracking-widest dark:text-indigo-300">Laba / Rugi Bersih</span>
                                    </td>
                                    <td class="px-6 py-6 text-right">
                                        <span class="text-lg font-black text-indigo-700 dark:text-indigo-300">Rp {{ number_format($report['net_profit'], 0, ',', '.') }}</span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Sidebar Section (5/12) --}}
            <div class="lg:col-span-4 flex flex-col gap-6">
                {{-- Expense Distribution Chart --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                    <h3 class="text-lg font-black text-slate-800 mb-6">Distribusi Biaya</h3>
                    <div id="expenseDonut" class="h-64"></div>
                    <div id="expenseEmpty" class="hidden text-center py-10">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Data biaya tidak tersedia</p>
                    </div>
                </div>

                {{-- Revenue Trend Chart --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-black text-slate-800">Tren Pendapatan</h3>
                    </div>
                    <div id="revenueBarTrend" class="h-64"></div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('[data-expense-toggle]').forEach((toggle) => {
                toggle.addEventListener('click', function() {
                    const groupKey = this.dataset.expenseToggle;
                    const isExpanded = this.getAttribute('aria-expanded') === 'true';
                    const nextExpanded = !isExpanded;

                    this.setAttribute('aria-expanded', nextExpanded ? 'true' : 'false');

                    const chevron = this.querySelector('[data-expense-chevron]');
                    if (chevron) {
                        chevron.classList.toggle('rotate-180', nextExpanded);
                    }

                    document.querySelectorAll(`[data-expense-detail="${groupKey}"]`).forEach((row) => {
                        row.classList.toggle('hidden', !nextExpanded);
                    });
                });
            });

            // Expense Distribution Chart
            const expenseData = @json($report['expense_chart']);
            if (expenseData.length > 0) {
                const donutOptions = {
                    series: expenseData.map(item => item.value),
                    chart: {
                        type: 'donut',
                        height: 250,
                        fontFamily: 'Inter, sans-serif'
                    },
                    labels: expenseData.map(item => item.label),
                    colors: ['#4F46E5', '#10B981', '#F59E0B', '#F97316', '#8B5CF6', '#EC4899'],
                    legend: {
                        position: 'bottom',
                        fontSize: '11px',
                        fontWeight: 600,
                        markers: { radius: 12 }
                    },
                    dataLabels: { enabled: false },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '70%',
                                labels: {
                                    show: true,
                                    total: {
                                        show: true,
                                        label: 'TOTAL',
                                        fontSize: '10px',
                                        fontWeight: 900,
                                        color: '#94a3b8',
                                        formatter: function (w) {
                                            const sum = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                            if (sum >= 1000000) return (sum / 1000000).toFixed(1) + 'M';
                                            if (sum >= 1000) return (sum / 1000).toFixed(0) + 'k';
                                            return sum;
                                        }
                                    }
                                }
                            }
                        }
                    },
                    stroke: { width: 0 }
                };
                new ApexCharts(document.querySelector("#expenseDonut"), donutOptions).render();
            } else {
                document.querySelector("#expenseDonut").classList.add('hidden');
                document.querySelector("#expenseEmpty").classList.remove('hidden');
            }

            // Revenue Trend Chart
            const trendData = @json($report['revenue_trends']);
            const barOptions = {
                series: [{
                    name: 'Pendapatan',
                    data: trendData.map(item => item.amount)
                }],
                chart: {
                    type: 'bar',
                    height: 250,
                    toolbar: { show: false },
                    fontFamily: 'Inter, sans-serif'
                },
                plotOptions: {
                    bar: {
                        borderRadius: 6,
                        columnWidth: '45%',
                        distributed: true
                    }
                },
                colors: ['#EEF2FF', '#D8E2FF', '#C7D2FE', '#A5B4FC', '#6366F1'],
                dataLabels: { enabled: false },
                legend: { show: false },
                xaxis: {
                    categories: trendData.map(item => item.month),
                    labels: {
                        style: {
                            colors: '#94a3b8',
                            fontSize: '10px',
                            fontWeight: 600
                        }
                    },
                    axisBorder: { show: false },
                    axisTicks: { show: false }
                },
                yaxis: { show: false },
                grid: { show: false },
                tooltip: {
                    theme: 'dark',
                    y: {
                        formatter: function(val) {
                            return 'Rp ' + new Intl.NumberFormat('id-ID').format(val);
                        }
                    }
                }
            };
            new ApexCharts(document.querySelector("#revenueBarTrend"), barOptions).render();
        });
    </script>
    @endpush

    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: white !important;
            }
            .rounded-2xl {
                border-radius: 0 !important;
            }
            .shadow-sm {
                box-shadow: none !important;
            }
        }
    </style>
@endsection
