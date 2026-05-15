@extends('layouts.admin')

@section('title', 'Profit & Loss Statement')
@section('page-title', 'Profit & Loss Statement')
@section('page-subtitle', 'Reports & Analytics')

@section('content')
    @php
        $activeView = $activeView ?? request('view', 'konsolidasi');
        $selectedOutletIds = collect($outletIds ?? $report['outlet_ids'] ?? [])->map(fn($id) => (int) $id)->filter()->values()->all();
        $singleOutletId = count($selectedOutletIds) === 1 ? $selectedOutletIds[0] : null;
        $outletComparison = $report['outlet_comparison'] ?? ['outlets' => [], 'totals' => []];
        $comparisonMetrics = [
            ['key' => 'revenue', 'label' => 'Pendapatan', 'type' => 'currency'],
            ['key' => 'cogs', 'label' => 'COGS / HPP', 'type' => 'currency'],
            ['key' => 'gross_profit', 'label' => 'Laba Kotor', 'type' => 'currency'],
            ['key' => 'gross_margin', 'label' => 'Gross Margin %', 'type' => 'percent'],
        ];
        $baseQuery = request()->except('view');
        $comparisonOutlets = $outletComparison['outlets'] ?? [];
        $shortOutletName = function (?string $name): string {
            $clean = trim((string) $name);
            if ($clean === '') {
                return '-';
            }

            $clean = preg_replace('/\bRansa\b/i', '', $clean) ?? $clean;
            $clean = trim($clean);
            if ($clean === '') {
                $clean = trim((string) $name);
            }

            if (strtolower($clean) === 'ciplaz') {
                return 'CPLZ';
            }

            if (strlen($clean) > 10 && str_contains($clean, ' ')) {
                return collect(preg_split('/\s+/', $clean) ?: [])
                    ->filter()
                    ->map(fn (string $word) => strtoupper(substr($word, 0, 1)))
                    ->implode('');
            }

            return strtoupper($clean);
        };

        $columnToneClasses = [
            ['header' => 'bg-emerald-50 text-emerald-700 border-emerald-100 dark:bg-emerald-950/35 dark:text-emerald-200 dark:border-emerald-900/70', 'cell' => 'bg-emerald-50/45 dark:bg-emerald-950/22'],
            ['header' => 'bg-rose-50 text-rose-700 border-rose-100 dark:bg-rose-950/35 dark:text-rose-200 dark:border-rose-900/70', 'cell' => 'bg-rose-50/45 dark:bg-rose-950/22'],
            ['header' => 'bg-amber-50 text-amber-700 border-amber-100 dark:bg-amber-950/35 dark:text-amber-200 dark:border-amber-900/70', 'cell' => 'bg-amber-50/45 dark:bg-amber-950/22'],
            ['header' => 'bg-sky-50 text-sky-700 border-sky-100 dark:bg-sky-950/35 dark:text-sky-200 dark:border-sky-900/70', 'cell' => 'bg-sky-50/45 dark:bg-sky-950/22'],
            ['header' => 'bg-violet-50 text-violet-700 border-violet-100 dark:bg-violet-950/35 dark:text-violet-200 dark:border-violet-900/70', 'cell' => 'bg-violet-50/45 dark:bg-violet-950/22'],
            ['header' => 'bg-teal-50 text-teal-700 border-teal-100 dark:bg-teal-950/35 dark:text-teal-200 dark:border-teal-900/70', 'cell' => 'bg-teal-50/45 dark:bg-teal-950/22'],
        ];

        $highestProfitOutletId = null;
        $lowestProfitOutletId = null;
        if (!empty($comparisonOutlets)) {
            $highestProfitOutlet = collect($comparisonOutlets)->sortByDesc('gross_profit')->first();
            $lowestProfitOutlet = collect($comparisonOutlets)->sortBy('gross_profit')->first();
            $highestProfitOutletId = is_array($highestProfitOutlet) ? ($highestProfitOutlet['id'] ?? null) : null;
            $lowestProfitOutletId = is_array($lowestProfitOutlet) ? ($lowestProfitOutlet['id'] ?? null) : null;
        }

        $salesReportParams = ['date_from' => $dateFrom, 'date_to' => $dateTo];
        if (!empty($selectedOutletIds)) {
            $salesReportParams['outlet_ids'] = $selectedOutletIds;
        }

        $stockMutationParams = [
            'start_date' => $dateFrom,
            'end_date' => $dateTo,
            'reference_scope' => 'sales_cogs',
        ];
        if (!is_null($singleOutletId)) {
            $stockMutationParams['outlet_id'] = $singleOutletId;
        }
    @endphp
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
                <input type="hidden" name="view" value="{{ $activeView }}">
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
                    <div class="relative min-w-[220px]" id="profitLossOutletFilterWrap">
                        <button type="button" id="profitLossOutletDropdownBtn"
                            class="flex min-w-[220px] items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-left text-xs font-bold text-slate-700 shadow-sm transition-all hover:bg-white focus:ring-2 focus:ring-indigo-500">
                            <span id="profitLossOutletDropdownLabel" class="truncate">Semua Outlet</span>
                            <i class="fas fa-chevron-down text-[10px] text-slate-400"></i>
                        </button>
                        <div id="profitLossOutletDropdownMenu"
                            class="hidden absolute top-full left-0 z-50 mt-2 w-full rounded-2xl border border-slate-200 bg-white p-3 shadow-xl">
                            <label class="mb-2 flex cursor-pointer items-center gap-3 border-b border-slate-100 px-2 py-2 text-[13px] font-bold text-slate-700">
                                <input type="checkbox" id="profitLossOutletAllCheckbox"
                                    class="h-5 w-5 rounded-lg border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                    {{ count($selectedOutletIds) === 0 ? 'checked' : '' }}>
                                <span>Pilih Semua</span>
                            </label>
                            <div class="custom-scrollbar space-y-1 pr-1" style="max-height: 12rem; overflow-y: auto;">
                                @foreach($outlets as $outlet)
                                    <label class="flex cursor-pointer items-center gap-3 rounded-lg px-2 py-2 text-[13px] text-slate-600 transition-colors hover:bg-slate-50">
                                        <input type="checkbox"
                                            name="outlet_ids[]"
                                            value="{{ $outlet->id }}"
                                            {{ count($selectedOutletIds) === 0 || in_array((int) $outlet->id, $selectedOutletIds, true) ? 'checked' : '' }}
                                            class="profit-loss-outlet-checkbox h-5 w-5 rounded-lg border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                        <span class="truncate font-medium">{{ $outlet->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <p class="ml-2 mt-2 text-[10px] font-medium text-slate-400">Kosong = semua outlet aktif</p>
                        </div>
                    </div>
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

            <div class="inline-flex w-fit items-center gap-2 rounded-2xl border border-slate-200 bg-white p-1 shadow-sm">
                <a href="{{ route('admin.reports.profit-loss.index', array_merge($baseQuery, ['view' => 'konsolidasi'])) }}"
                    class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-black uppercase tracking-[0.18em] transition-all {{ $activeView === 'konsolidasi' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                    <i class="fas fa-layer-group text-[10px]"></i>
                    <span>Konsolidasi</span>
                </a>
                <a href="{{ route('admin.reports.profit-loss.index', array_merge($baseQuery, ['view' => 'per-outlet'])) }}"
                    class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-black uppercase tracking-[0.18em] transition-all {{ $activeView === 'per-outlet' ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-100' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                    <i class="fas fa-store-alt text-[10px]"></i>
                    <span>Per Outlet</span>
                </a>
            </div>
        </div>

        @if($activeView === 'konsolidasi')
        {{-- Summary KPI Row --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Pendapatan -->
            <a href="{{ route('admin.reports.sales.index', $salesReportParams) }}" target="_blank"
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
            <a href="{{ route('admin.stocks.mutations', $stockMutationParams) }}" target="_blank"
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
                                        <a href="{{ route('admin.reports.sales.index', $salesReportParams) }}" target="_blank"
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
                                        <a href="{{ route('admin.stocks.mutations', $stockMutationParams) }}" target="_blank"
                                            class="text-sm font-bold text-rose-500 hover:text-indigo-600 transition-colors">
                                            Rp {{ number_format($report['total_cogs'], 0, ',', '.') }}
                                        </a>
                                    </td>
                                </tr>

                                {{-- GROSS PROFIT --}}
                                <tr class="pl-summary-row bg-emerald-50/70 border-y border-emerald-100 dark:bg-emerald-950/30 dark:border-emerald-800/80">
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

                                    <tr class="pl-group-row bg-slate-50/70 border-t border-slate-100 dark:bg-slate-800/80 dark:border-slate-700">
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
                                            <a href="{{ route('admin.cash-transactions.index', array_merge([
                                                'date_from' => $dateFrom,
                                                'date_to' => $dateTo,
                                                'type' => 'out',
                                                'coa_type' => 'expense',
                                                'coa_group' => $group['group_name'],
                                            ], !is_null($singleOutletId) ? ['outlet_id' => $singleOutletId] : [])) }}" target="_blank"
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
                                                <a href="{{ route('admin.cash-transactions.index', array_merge([
                                                    'date_from' => $dateFrom,
                                                    'date_to' => $dateTo,
                                                    'type' => 'out',
                                                    'coa_account_id' => $account['id'],
                                                ], !is_null($singleOutletId) ? ['outlet_id' => $singleOutletId] : [])) }}" target="_blank"
                                                    class="text-sm font-medium text-slate-700 hover:text-indigo-600 transition-colors dark:text-slate-200 dark:hover:text-indigo-300">
                                                    Rp {{ number_format($account['amount'], 0, ',', '.') }}
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="pl-net-row bg-indigo-50/50 dark:bg-slate-800/90 dark:border-t dark:border-slate-700">
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
        @else
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden dark:bg-slate-900 dark:border-slate-700">
            <div class="border-b border-slate-100 px-6 py-5 dark:border-slate-700">
                <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                    <div>
                        <h3 class="text-lg font-black text-slate-800 dark:text-slate-100">Perbandingan Per Outlet</h3>
                        <p class="mt-1 text-[11px] font-medium text-slate-400 dark:text-slate-400">
                            Bandingkan omzet, HPP, laba kotor, dan gross margin antar outlet dalam satu tabel.
                        </p>
                    </div>
                    <span class="inline-flex w-fit items-center rounded-full bg-indigo-50 px-3 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-indigo-600 dark:bg-indigo-950/45 dark:text-indigo-200">
                        {{ count($outletComparison['outlets'] ?? []) }} Outlet Ditampilkan
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[960px] w-full border-separate border-spacing-0">
                    <thead>
                        <tr class="bg-slate-50/80 dark:bg-slate-800/75">
                            <th class="sticky left-0 z-10 w-[140px] min-w-[140px] border-b border-slate-200 bg-slate-50/95 px-4 py-4 text-left text-[10px] font-black uppercase tracking-widest text-slate-400 dark:border-slate-700 dark:bg-slate-800/95 dark:text-slate-300">
                                Outlet Name
                            </th>
                            <th class="border-b border-slate-200 px-6 py-4 text-right text-[10px] font-black uppercase tracking-widest text-slate-500 dark:border-slate-700 dark:text-slate-300">
                                Revenue (Gross)
                            </th>
                            <th class="border-b border-slate-200 px-6 py-4 text-right text-[10px] font-black uppercase tracking-widest text-slate-500 dark:border-slate-700 dark:text-slate-300">
                                COGS (HPP)
                            </th>
                            <th class="border-b border-slate-200 px-6 py-4 text-right text-[10px] font-black uppercase tracking-widest text-slate-500 dark:border-slate-700 dark:text-slate-300">
                                Gross Profit
                            </th>
                            <th class="border-b border-slate-200 px-6 py-4 text-center text-[10px] font-black uppercase tracking-widest text-slate-500 dark:border-slate-700 dark:text-slate-300">
                                Margin %
                            </th>
                            <th class="border-b border-slate-200 px-6 py-4 text-center text-[10px] font-black uppercase tracking-widest text-slate-500 dark:border-slate-700 dark:text-slate-300">
                                Status
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $averageOutletMargin = count($comparisonOutlets) > 0
                                ? (float) collect($comparisonOutlets)->avg('gross_margin')
                                : 0.0;
                        @endphp
                        @forelse(($outletComparison['outlets'] ?? []) as $outletRow)
                            @php
                                $tone = $columnToneClasses[$loop->index % count($columnToneClasses)];
                                $isHighestProfit = !is_null($highestProfitOutletId) && (int) ($outletRow['id'] ?? 0) === (int) $highestProfitOutletId;
                                $isLowestProfit = !is_null($lowestProfitOutletId) && (int) ($outletRow['id'] ?? 0) === (int) $lowestProfitOutletId;
                                $marginValue = (float) ($outletRow['gross_margin'] ?? 0);
                                $marginChipClass = $marginValue >= 50
                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-200'
                                    : ($marginValue >= 30
                                        ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-200'
                                        : 'bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-200');
                                $statusText = $isHighestProfit
                                    ? 'Top'
                                    : ($isLowestProfit ? 'Low' : 'OK');
                                $statusClass = $isHighestProfit
                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-200'
                                    : ($isLowestProfit
                                        ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-200'
                                        : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200');
                            @endphp
                            <tr class="group">
                                <td class="sticky left-0 z-10 w-[140px] min-w-[140px] border-b border-slate-100 bg-white px-4 py-4 dark:border-slate-700 dark:bg-slate-900">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-[11px] font-black {{ $tone['header'] }}">
                                            {{ $shortOutletName($outletRow['name'] ?? null) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="border-b border-slate-100 px-6 py-4 text-right text-sm font-semibold text-slate-700 dark:border-slate-700 dark:text-slate-100 {{ $tone['cell'] }}">
                                    Rp {{ number_format((float) ($outletRow['revenue'] ?? 0), 0, ',', '.') }}
                                </td>
                                <td class="border-b border-slate-100 px-6 py-4 text-right text-sm font-semibold text-slate-700 dark:border-slate-700 dark:text-slate-100 {{ $tone['cell'] }}">
                                    Rp {{ number_format((float) ($outletRow['cogs'] ?? 0), 0, ',', '.') }}
                                </td>
                                <td class="border-b border-slate-100 px-6 py-4 text-right text-sm font-black text-slate-800 dark:border-slate-700 dark:text-slate-100 {{ $tone['cell'] }}">
                                    Rp {{ number_format((float) ($outletRow['gross_profit'] ?? 0), 0, ',', '.') }}
                                </td>
                                <td class="border-b border-slate-100 px-6 py-4 text-center dark:border-slate-700 {{ $tone['cell'] }}">
                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-black {{ $marginChipClass }}">
                                        {{ number_format($marginValue, 1) }}%
                                    </span>
                                </td>
                                <td class="border-b border-slate-100 px-6 py-4 text-center dark:border-slate-700 {{ $tone['cell'] }}">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.14em] {{ $statusClass }}">
                                        {{ $statusText }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-sm font-semibold text-slate-400 dark:text-slate-500">
                                    Data outlet tidak tersedia untuk filter saat ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-50/80 dark:bg-slate-800/70">
                            <td class="border-t border-slate-200 px-6 py-4 text-[11px] font-black uppercase tracking-[0.16em] text-slate-500 dark:border-slate-700 dark:text-slate-300">
                                Summary (Aggregated)
                            </td>
                            <td class="border-t border-slate-200 px-6 py-4 text-right text-sm font-black text-slate-700 dark:border-slate-700 dark:text-slate-100">
                                Rp {{ number_format((float) ($outletComparison['totals']['revenue'] ?? 0), 0, ',', '.') }}
                            </td>
                            <td class="border-t border-slate-200 px-6 py-4 text-right text-sm font-black text-slate-700 dark:border-slate-700 dark:text-slate-100">
                                Rp {{ number_format((float) ($outletComparison['totals']['cogs'] ?? 0), 0, ',', '.') }}
                            </td>
                            <td class="border-t border-slate-200 px-6 py-4 text-right text-sm font-black text-slate-700 dark:border-slate-700 dark:text-slate-100">
                                Rp {{ number_format((float) ($outletComparison['totals']['gross_profit'] ?? 0), 0, ',', '.') }}
                            </td>
                            <td class="border-t border-slate-200 px-6 py-4 text-center text-sm font-black text-indigo-600 dark:border-slate-700 dark:text-indigo-300">
                                {{ number_format($averageOutletMargin, 1) }}%
                            </td>
                            <td class="border-t border-slate-200 px-6 py-4 text-center text-[10px] font-black uppercase tracking-[0.14em] text-slate-500 dark:border-slate-700 dark:text-slate-300">
                                Avg Margin
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="mt-6 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h3 class="text-lg font-black text-slate-800">Chart Perbandingan Outlet</h3>
            <p class="mt-1 text-[11px] font-medium text-slate-400">
                Pendapatan dan HPP per outlet, dilengkapi gross margin ringkas.
            </p>
            <div id="outletComparisonChart" class="mt-4 h-80"></div>
            <div id="outletComparisonChartEmpty" class="hidden text-center py-10">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Data outlet tidak tersedia</p>
            </div>
            <div id="outletComparisonMarginStrip" class="mt-6 hidden border-t border-slate-100 pt-5">
                <div class="mb-3 flex items-center gap-2">
                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-blue-600"></span>
                    <span class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-500">Gross Margin</span>
                </div>
                <div id="outletComparisonMarginItems" class="grid grid-cols-2 gap-3 md:grid-cols-4 xl:grid-cols-6"></div>
            </div>
        </div>
        @endif
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            (function() {
                const wrap = document.getElementById('profitLossOutletFilterWrap');
                const btn = document.getElementById('profitLossOutletDropdownBtn');
                const label = document.getElementById('profitLossOutletDropdownLabel');
                const menu = document.getElementById('profitLossOutletDropdownMenu');
                const allCheckbox = document.getElementById('profitLossOutletAllCheckbox');
                const itemCheckboxes = Array.from(document.querySelectorAll('.profit-loss-outlet-checkbox'));

                if (!wrap || !btn || !label || !menu || !allCheckbox) return;

                const updateLabel = () => {
                    const checkedItems = itemCheckboxes.filter((checkbox) => checkbox.checked);

                    if (checkedItems.length === itemCheckboxes.length || checkedItems.length === 0) {
                        allCheckbox.checked = checkedItems.length !== 0;
                        label.textContent = 'Semua Outlet';
                    } else if (checkedItems.length === 1) {
                        allCheckbox.checked = false;
                        label.textContent = checkedItems[0].parentElement.textContent.trim();
                    } else {
                        allCheckbox.checked = false;
                        label.textContent = `${checkedItems.length} Outlet`;
                    }
                };

                btn.addEventListener('click', () => menu.classList.toggle('hidden'));

                document.addEventListener('click', (event) => {
                    if (!wrap.contains(event.target)) {
                        menu.classList.add('hidden');
                    }
                });

                allCheckbox.addEventListener('change', () => {
                    itemCheckboxes.forEach((checkbox) => {
                        checkbox.checked = allCheckbox.checked;
                    });

                    updateLabel();
                });

                itemCheckboxes.forEach((checkbox) => {
                    checkbox.addEventListener('change', updateLabel);
                });

                updateLabel();
            })();

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
            const expenseDonut = document.querySelector("#expenseDonut");
            const expenseEmpty = document.querySelector("#expenseEmpty");
            if (expenseDonut && expenseEmpty && expenseData.length > 0) {
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
                new ApexCharts(expenseDonut, donutOptions).render();
            } else if (expenseDonut && expenseEmpty) {
                expenseDonut.classList.add('hidden');
                expenseEmpty.classList.remove('hidden');
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
            const revenueBarTrend = document.querySelector("#revenueBarTrend");
            if (revenueBarTrend) {
                new ApexCharts(revenueBarTrend, barOptions).render();
            }

            // Outlet comparison charts (tab Per Outlet)
            const outletComparisonData = @json($comparisonOutlets);
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

            function formatRupiahCompact(value) {
                const val = Number(value || 0);
                const absVal = Math.abs(val);
                if (absVal >= 1000000000) {
                    return (val / 1000000000).toFixed(1).replace('.', ',') + ' M';
                }
                if (absVal >= 1000000) {
                    return (val / 1000000).toFixed(0).replace('.', ',') + ' jt';
                }
                if (absVal >= 1000) {
                    return (val / 1000).toFixed(0).replace('.', ',') + ' rb';
                }
                return String(Math.round(val));
            }

            function formatMarginCompact(value) {
                const val = Number(value || 0);
                return val.toFixed(1) + '%';
            }

            const outletFullNames = outletComparisonData.map(item => item.name || 'Outlet');
            const outletNames = outletComparisonData.map(item => simplifyOutletName(item.name));
            const outletCategoryKeys = outletComparisonData.map(function(_, index) {
                return 'outlet_' + index;
            });

            const outletComparisonChartEl = document.querySelector("#outletComparisonChart");
            const outletComparisonChartEmptyEl = document.querySelector("#outletComparisonChartEmpty");
            const outletComparisonMarginStripEl = document.querySelector("#outletComparisonMarginStrip");
            const outletComparisonMarginItemsEl = document.querySelector("#outletComparisonMarginItems");
            if (outletComparisonChartEl && outletComparisonChartEmptyEl && outletComparisonData.length > 0) {
                const outletComparisonOptions = {
                    series: [
                        { name: 'Pendapatan', data: outletComparisonData.map(item => Number(item.revenue || 0)) },
                        { name: 'COGS / HPP', data: outletComparisonData.map(item => Number(item.cogs || 0)) }
                    ],
                    chart: {
                        type: 'bar',
                        height: 320,
                        toolbar: { show: false },
                        zoom: { enabled: false },
                        fontFamily: 'Inter, sans-serif',
                        background: 'transparent',
                        stacked: false
                    },
                    colors: ['#16A34A', '#DC2626'],
                    dataLabels: { enabled: false },
                    legend: {
                        show: true,
                        position: 'top',
                        horizontalAlign: 'center'
                    },
                    xaxis: {
                        categories: outletCategoryKeys,
                        labels: {
                            formatter: function(value) {
                                const index = outletCategoryKeys.indexOf(value);
                                return index >= 0 ? (outletNames[index] || '') : value;
                            },
                            rotate: -45,
                            hideOverlappingLabels: true,
                            style: {
                                colors: '#64748b',
                                fontSize: '11px',
                                fontWeight: 600
                            }
                        },
                        axisBorder: { show: false },
                        axisTicks: { show: false }
                    },
                    plotOptions: {
                        bar: {
                            horizontal: false,
                            columnWidth: '52%',
                            borderRadius: 6
                        }
                    },
                    stroke: {
                        show: true,
                        width: 1,
                        colors: ['transparent']
                    },
                    grid: {
                        borderColor: '#e2e8f0',
                        strokeDashArray: 0,
                        padding: { left: 10, right: 10 }
                    },
                    yaxis: {
                        labels: {
                            formatter: function(val) {
                                return formatRupiahCompact(val);
                            },
                            style: {
                                colors: '#64748b',
                                fontSize: '10px'
                            }
                        }
                    },
                    tooltip: {
                        shared: true,
                        intersect: false,
                        x: {
                            formatter: function(_, opts) {
                                const index = opts && typeof opts.dataPointIndex === 'number' ? opts.dataPointIndex : -1;
                                return index >= 0 ? (outletFullNames[index] || '') : '';
                            }
                        },
                        y: {
                            formatter: function(val, context) {
                                return formatRupiahCompact(val);
                            }
                        }
                    }
                };

                try {
                    new ApexCharts(outletComparisonChartEl, outletComparisonOptions).render();
                } catch (error) {
                    console.error('Failed to render outlet comparison chart', error);
                    outletComparisonChartEl.classList.add('hidden');
                    outletComparisonChartEmptyEl.classList.remove('hidden');
                }

                if (outletComparisonMarginStripEl && outletComparisonMarginItemsEl) {
                    outletComparisonMarginStripEl.classList.remove('hidden');
                    outletComparisonMarginItemsEl.innerHTML = outletComparisonData.map(function(item) {
                        return `
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3">
                                <div class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-500">${simplifyOutletName(item.name)}</div>
                                <div class="mt-1 text-lg font-black text-blue-600">${formatMarginCompact(item.gross_margin)}</div>
                            </div>
                        `;
                    }).join('');
                }
            } else if (outletComparisonChartEl && outletComparisonChartEmptyEl) {
                outletComparisonChartEl.classList.add('hidden');
                outletComparisonChartEmptyEl.classList.remove('hidden');
                if (outletComparisonMarginStripEl) {
                    outletComparisonMarginStripEl.classList.add('hidden');
                }
            }
        });
    </script>
    @endpush

    <style>
        .dark .ui-admin-body .pl-group-row {
            background-color: #1f2937 !important;
            border-color: #334155 !important;
        }

        .dark .ui-admin-body .pl-summary-row {
            background-color: rgba(6, 78, 59, 0.32) !important;
            border-color: rgba(16, 185, 129, 0.35) !important;
        }

        .dark .ui-admin-body .pl-net-row {
            background-color: #1e293b !important;
            border-color: #334155 !important;
        }

        .dark .ui-admin-body .pl-group-row [data-expense-chevron] {
            background-color: #0f172a !important;
            color: #cbd5e1 !important;
            box-shadow: none !important;
        }

        .dark .ui-admin-body .pl-group-row .bg-slate-200\/70 {
            background-color: #334155 !important;
            color: #e2e8f0 !important;
        }

        .dark .ui-admin-body .pl-group-row .text-slate-700,
        .dark .ui-admin-body .pl-group-row .text-slate-600 {
            color: #e2e8f0 !important;
        }

        .dark .ui-admin-body .pl-group-row .text-slate-400,
        .dark .ui-admin-body .pl-group-row .text-slate-500 {
            color: #94a3b8 !important;
        }

        .dark .ui-admin-body [data-expense-detail] .bg-slate-100 {
            background-color: #1e293b !important;
            color: #cbd5e1 !important;
        }

        .dark .ui-admin-body [data-expense-detail] .text-slate-600,
        .dark .ui-admin-body [data-expense-detail] .text-slate-700 {
            color: #e2e8f0 !important;
        }

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


