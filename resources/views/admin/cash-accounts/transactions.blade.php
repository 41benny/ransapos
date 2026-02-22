@extends('layouts.admin')

@section('title', 'Transaksi Kas & Bank')

@section('content')
    <div class="space-y-6">
        {{-- Header Section --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Transaksi Kas & Bank</h1>
                <p class="text-xs font-medium text-slate-500 mt-0.5">Kelola dan monitor semua arus kas masuk & keluar unit usaha</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.cash-transactions.create') }}"
                    class="group relative inline-flex items-center gap-2 overflow-hidden rounded-lg bg-slate-900 px-4 py-2 text-xs font-bold text-white transition-all hover:bg-slate-800 hover:shadow-md active:scale-95">
                    <i class="fas fa-plus text-[10px] transition-transform group-hover:rotate-90"></i>
                    <span>Catat Transaksi</span>
                    <div
                        class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/10 to-transparent transition-transform duration-500 group-hover:translate-x-full">
                    </div>
                </a>
            </div>
        </div>

        {{-- KPI Overview --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {{-- Total Debit Card --}}
            <div
                class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all hover:shadow-md">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[9px] font-bold uppercase tracking-[0.2em] text-emerald-500">Total Arus Masuk</span>
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 transition-colors group-hover:bg-emerald-600 group-hover:text-white">
                        <i class="fas fa-arrow-down-left text-xs"></i>
                    </div>
                </div>
                <div class="flex flex-col">
                    <h3 class="text-xl font-bold text-slate-800">Rp
                        {{ number_format($totals['debit'] ?? 0, 0, ',', '.') }}
                    </h3>
                    <p class="text-xs font-medium text-slate-400 mt-0.5">Total Debit Terhitung</p>
                </div>
                <div class="absolute bottom-0 left-0 h-1 w-0 bg-emerald-500 transition-all duration-500 group-hover:w-full">
                </div>
            </div>

            {{-- Total Kredit Card --}}
            <div
                class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all hover:shadow-md">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[9px] font-bold uppercase tracking-[0.2em] text-rose-500">Total Arus Keluar</span>
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600 transition-colors group-hover:bg-rose-600 group-hover:text-white">
                        <i class="fas fa-arrow-up-right text-xs"></i>
                    </div>
                </div>
                <div class="flex flex-col">
                    <h3 class="text-xl font-bold text-slate-800">Rp
                        {{ number_format($totals['credit'] ?? 0, 0, ',', '.') }}
                    </h3>
                    <p class="text-xs font-medium text-slate-400 mt-0.5">Total Kredit Terhitung</p>
                </div>
                <div class="absolute bottom-0 left-0 h-1 w-0 bg-rose-500 transition-all duration-500 group-hover:w-full">
                </div>
            </div>

            {{-- Filter Summary --}}
            <div
                class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all hover:shadow-md md:col-span-2 lg:col-span-1">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[9px] font-bold uppercase tracking-[0.2em] text-indigo-500">Status Pencarian</span>
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 transition-colors group-hover:bg-indigo-600 group-hover:text-white">
                        <i class="fas fa-filter text-xs"></i>
                    </div>
                </div>
                <div class="flex flex-col">
                    <h3 class="text-sm font-bold text-slate-800">{{ $transactions->total() }} Transaksi</h3>
                    <p class="text-xs font-medium text-slate-400 mt-0.5">Menampilkan baris kriteria filter</p>
                </div>
            </div>
        </div>

        {{-- Alert Messages --}}
        @if(session('success'))
            <div
                class="rounded-xl border border-emerald-100 bg-emerald-50 p-4 text-emerald-800 shadow-sm flex items-center gap-3">
                <i class="fas fa-check-circle text-lg"></i>
                <span class="text-sm font-bold">{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="rounded-xl border border-rose-100 bg-rose-50 p-4 text-rose-800 shadow-sm flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-lg"></i>
                <span class="text-sm font-bold">{{ session('error') }}</span>
            </div>
        @endif

        {{-- Main Table Section --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <h3 class="text-sm font-bold text-slate-800 uppercase tracking-widest">Transaction History</h3>
                <div class="flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-none">Live Access</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm" id="cashTransactionsTable"
                    style="table-layout: fixed;">
                    <colgroup id="cashTransactionsColgroup">
                        <col class="resizable-col" style="width: 140px;">
                        <col class="resizable-col" style="width: 110px;">
                        <col class="resizable-col" style="width: 150px;">
                        <col class="resizable-col" style="width: 220px;">
                        <col class="resizable-col" style="width: 120px;">
                        <col class="resizable-col" style="width: 120px;">
                        <col class="resizable-col" style="width: 140px;">
                        <col class="resizable-col" style="width: 200px;">
                        <col style="width: 120px;">
                    </colgroup>
                    <thead class="bg-slate-50/80 sticky top-0 backdrop-blur-sm z-10">
                        <tr>
                            <th class="resizable px-4 py-2.5 text-left text-[9px] font-bold uppercase tracking-widest text-slate-500"
                                data-min-width="120" style="position: relative;">
                                Voucher Number
                                <div class="resize-handle"></div>
                            </th>
                            <th class="resizable px-4 py-2.5 text-left text-[9px] font-bold uppercase tracking-widest text-slate-500"
                                data-min-width="100" style="position: relative;">
                                Date
                                <div class="resize-handle"></div>
                            </th>
                            <th class="resizable px-4 py-2.5 text-left text-[9px] font-bold uppercase tracking-widest text-slate-500"
                                data-min-width="140" style="position: relative;">
                                Cash Account
                                <div class="resize-handle"></div>
                            </th>
                            <th class="resizable px-4 py-2.5 text-left text-[9px] font-bold uppercase tracking-widest text-slate-500"
                                data-min-width="200" style="position: relative;">
                                Description
                                <div class="resize-handle"></div>
                            </th>
                            <th class="resizable px-4 py-2.5 text-right text-[9px] font-bold uppercase tracking-widest text-slate-500"
                                data-min-width="100" style="position: relative;">
                                Debit (In)
                                <div class="resize-handle"></div>
                            </th>
                            <th class="resizable px-4 py-2.5 text-right text-[9px] font-bold uppercase tracking-widest text-slate-500"
                                data-min-width="100" style="position: relative;">
                                Kredit (Out)
                                <div class="resize-handle"></div>
                            </th>
                            <th class="resizable px-4 py-2.5 text-right text-[9px] font-bold uppercase tracking-widest text-slate-500"
                                data-min-width="120" style="position: relative;">
                                Balance
                                <div class="resize-handle"></div>
                            </th>
                            <th class="resizable px-4 py-2.5 text-left text-[9px] font-bold uppercase tracking-widest text-slate-500"
                                data-min-width="180" style="position: relative;">
                                COA Account
                                <div class="resize-handle"></div>
                            </th>
                            <th
                                class="px-4 py-2.5 text-center text-[9px] font-bold uppercase tracking-widest text-slate-500">
                                Actions</th>
                        </tr>
                        <tr class="bg-white/50 border-b border-slate-100">
                            <th class="px-4 py-1.5">
                                <input type="text" class="filter-input w-full px-2 py-1 text-xs font-medium bg-white border border-slate-200 rounded focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition-all placeholder:text-slate-300" data-name="transaction_number" value="{{ request('transaction_number') }}" placeholder="Search #">
                            </th>
                            <th class="px-4 py-1.5">
                                <input type="date" class="filter-input w-full px-2 py-1 text-xs font-medium bg-white border border-slate-200 rounded focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition-all" data-name="transaction_date" value="{{ request('transaction_date') }}">
                            </th>
                            <th class="px-4 py-1.5">
                                <select class="filter-input w-full px-2 py-1 text-xs font-medium bg-white border border-slate-200 rounded focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition-all" data-name="cash_account_id">
                                    <option value="">All Accounts</option>
                                    @foreach($accounts as $account)
                                        <option value="{{ $account->id }}" {{ request('cash_account_id') == $account->id ? 'selected' : '' }}>{{ $account->name }}</option>
                                    @endforeach
                                </select>
                            </th>
                            <th class="px-4 py-1.5">
                                <input type="text" class="filter-input w-full px-2 py-1 text-xs font-medium bg-white border border-slate-200 rounded focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition-all placeholder:text-slate-300" data-name="description" value="{{ request('description') }}" placeholder="Search desc...">
                            </th>
                            <th class="px-4 py-1.5 text-right">
                                <span class="text-[9px] font-medium text-slate-300 uppercase italic">Auto</span>
                            </th>
                            <th class="px-4 py-1.5 text-right">
                                <span class="text-[9px] font-medium text-slate-300 uppercase italic">Auto</span>
                            </th>
                            <th class="px-4 py-1.5">
                                <input type="text" class="filter-input w-full px-2 py-1 text-xs font-medium bg-white border border-slate-200 rounded focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-right placeholder:text-slate-300" data-name="balance_after" value="{{ request('balance_after') }}" placeholder="Balance">
                            </th>
                            <th class="px-4 py-1.5">
                                <select class="filter-input w-full px-2 py-1 text-xs font-medium bg-white border border-slate-200 rounded focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition-all" data-name="coa_account_id">
                                    <option value="">All COA</option>
                                    @foreach($coaAccounts as $coaAccount)
                                        <option value="{{ $coaAccount->id }}" {{ request('coa_account_id') == $coaAccount->id ? 'selected' : '' }}>{{ $coaAccount->code }}</option>
                                    @endforeach
                                </select>
                            </th>
                            <th class="px-4 py-1.5 text-center">
                                <button type="button" id="clearFilters"
                                    class="inline-flex h-7 w-7 items-center justify-center rounded bg-slate-100 text-slate-500 hover:bg-rose-50 hover:text-rose-600 transition-all"
                                    title="Reset Filters">
                                    <i class="fas fa-undo-alt text-[10px]"></i>
                                </button>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $displayTransactions = $transactions->getCollection()
                                ->groupBy(function ($transaction) {
                                    if ($transaction->reference_type === 'general_batch' && !empty($transaction->reference_id)) {
                                        return 'batch:' . $transaction->reference_id;
                                    }

                                    // Fallback untuk data lama (sebelum ada reference general_batch):
                                    // satu submit multi-baris biasanya punya cap created_at detik yang sama.
                                    $isLegacyCandidate = empty($transaction->reference_type)
                                        && empty($transaction->reference_id)
                                        && !empty($transaction->created_at);

                                    if ($isLegacyCandidate) {
                                        $createdAtKey = optional($transaction->created_at)->format('Y-m-d H:i:s');
                                        $dateKey = optional($transaction->transaction_date)->format('Y-m-d');
                                        $notesHash = md5(trim((string) ($transaction->notes ?? '')));

                                        return 'legacy:' . implode('|', [
                                            (string) $transaction->cash_account_id,
                                            (string) ($transaction->created_by ?? 0),
                                            (string) $transaction->type,
                                            (string) $dateKey,
                                            (string) $createdAtKey,
                                            $notesHash,
                                        ]);
                                    }

                                    return 'single:' . $transaction->id;
                                })
                                ->map(function ($group, $groupKey) {
                                    $representative = $group->first();
                                    $rowCount = $group->count();
                                    $isGeneralBatch = $rowCount > 1 && $representative->reference_type === 'general_batch';
                                    $isLegacyBatch = $rowCount > 1 && Str::startsWith($groupKey, 'legacy:');
                                    $isBatch = $isGeneralBatch || $isLegacyBatch;

                                    $representative->setAttribute('display_amount', (float) $group->sum('amount'));
                                    $representative->setAttribute('display_row_count', $rowCount);
                                    $representative->setAttribute('display_is_batch', $isBatch);
                                    $representative->setAttribute(
                                        'display_voucher_number',
                                        (string) ($group->pluck('voucher_number')->filter()->sort()->first()
                                            ?? $representative->voucher_number
                                            ?? $representative->transaction_number)
                                    );
                                    $representative->setAttribute(
                                        'display_description',
                                        $isBatch ? 'Transaksi Umum (' . $rowCount . ' baris)' : $representative->description
                                    );

                                    return $representative;
                                })
                                ->values();
                        @endphp

                        @forelse($displayTransactions as $transaction)
                            @php
                                $displayAmount = (float) ($transaction->display_amount ?? $transaction->amount);
                                $displayRowCount = (int) ($transaction->display_row_count ?? 1);
                                $isBatchRow = (bool) ($transaction->display_is_batch ?? false);
                                $displayVoucherNumber = (string) ($transaction->display_voucher_number
                                    ?? $transaction->voucher_number
                                    ?? $transaction->transaction_number);
                            @endphp
                            <tr class="group hover:bg-slate-50/80 transition-colors">
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <div class="flex flex-col">
                                        <a href="{{ route('admin.cash-transactions.show', array_merge(['cashTransaction' => $transaction], request()->query())) }}"
                                            class="text-[11.5px] font-medium text-slate-800 hover:text-indigo-600 transition-colors">
                                            {{ $displayVoucherNumber }}
                                        </a>
                                        @if($isBatchRow)
                                            <span class="inline-flex items-center gap-1 mt-0.5 text-[9px] font-medium text-slate-400">
                                                <i class="fas fa-layer-group text-[8px]"></i>
                                                {{ $displayRowCount }} Items Merged
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-[11px] font-medium text-slate-600">
                                    {{ $transaction->transaction_date->format('d M Y') }}
                                </td>
                                <td class="px-4 py-2 text-[11px] font-medium text-slate-700">
                                    <div class="truncate max-w-[140px]" title="{{ $transaction->cashAccount->name }}">
                                        {{ $transaction->cashAccount->name }}
                                    </div>
                                </td>
                                <td class="px-4 py-2 text-[11px] font-medium text-slate-600">
                                    <div class="truncate max-w-[220px]" title="{{ $transaction->display_description }}">
                                        {{ $transaction->display_description }}
                                    </div>
                                    @if($isBatchRow)
                                        <span class="text-[8px] font-medium uppercase tracking-tighter text-indigo-400 block mt-0.5 italic leading-none">Consolidated Entry</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-right">
                                    @if($transaction->type === 'in')
                                        <div class="inline-flex items-center justify-end px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700">
                                            <span class="text-[11px] font-medium">Rp {{ number_format($displayAmount, 0, ',', '.') }}</span>
                                        </div>
                                    @else
                                        <span class="text-[11px] font-medium text-slate-200">--</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-right">
                                    @if($transaction->type === 'out')
                                        <div class="inline-flex items-center justify-end px-1.5 py-0.5 rounded bg-rose-50 text-rose-700">
                                            <span class="text-[11px] font-medium">Rp {{ number_format($displayAmount, 0, ',', '.') }}</span>
                                        </div>
                                    @else
                                        <span class="text-[11px] font-medium text-slate-200">--</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-right text-sm font-medium text-slate-800 tracking-tight">
                                    Rp {{ number_format($transaction->balance_after, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-[10px] font-medium text-slate-500">
                                    @if($isBatchRow)
                                        <span class="px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-500">Multi ({{ $displayRowCount }})</span>
                                    @elseif($transaction->coaAccount)
                                        <div class="max-w-[180px] truncate" title="{{ $transaction->coaAccount->code }} - {{ $transaction->coaAccount->name }}">
                                            <span class="text-slate-800 font-medium">{{ $transaction->coaAccount->code }}</span>
                                            <span class="text-slate-400 ml-0.5">· {{ Str::limit($transaction->coaAccount->name, 18) }}</span>
                                        </div>
                                    @else
                                        <span class="text-slate-300">--</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-center">
                                    <div
                                        class="flex items-center justify-center gap-1 transition-all duration-300">
                                        <a href="{{ route('admin.cash-transactions.show', array_merge(['cashTransaction' => $transaction], request()->query())) }}"
                                            class="inline-flex h-6 w-6 items-center justify-center rounded bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition-all shadow-sm"
                                            title="View">
                                            <i class="fas fa-eye text-[10px]"></i>
                                        </a>
                                        <a href="{{ route('admin.cash-transactions.print', $transaction) }}" target="_blank"
                                            class="inline-flex h-6 w-6 items-center justify-center rounded bg-slate-50 text-slate-600 hover:bg-slate-900 hover:text-white transition-all shadow-sm"
                                            title="Print">
                                            <i class="fas fa-print text-[10px]"></i>
                                        </a>
                                        <a href="{{ route('admin.cash-transactions.edit', array_merge(['cashTransaction' => $transaction], request()->query())) }}"
                                            class="inline-flex h-6 w-6 items-center justify-center rounded bg-amber-50 text-amber-600 hover:bg-amber-600 hover:text-white transition-all shadow-sm"
                                            title="Edit">
                                            <i class="fas fa-edit text-[10px]"></i>
                                        </a>
                                        <form action="{{ route('admin.cash-transactions.destroy', $transaction) }}"
                                            method="POST" class="inline-block"
                                            onsubmit="return confirm('Delete this transaction?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="inline-flex h-6 w-6 items-center justify-center rounded bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition-all shadow-sm"
                                                title="Delete">
                                                <i class="fas fa-trash text-[10px]"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-20 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="h-12 w-12 rounded-full bg-slate-50 flex items-center justify-center mb-3">
                                            <i class="fas fa-cash-register text-2xl text-slate-200"></i>
                                        </div>
                                        <h4 class="text-sm font-bold text-slate-800">No Transactions Found</h4>
                                        <p class="text-xs font-medium text-slate-400 mt-0.5 max-w-[200px] mx-auto italic">Adjust your filter or record a new transaction.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div
                class="px-4 py-3 border-t border-slate-100 bg-slate-50/30 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-6">
                    <div class="flex flex-col">
                        <span class="text-[9px] font-medium uppercase tracking-widest text-emerald-500">Cumulative In</span>
                        <span class="text-xs font-medium text-slate-800">Rp {{ number_format($totals['debit'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex flex-col border-l border-slate-200 pl-6">
                        <span class="text-[9px] font-medium uppercase tracking-widest text-rose-500">Cumulative Out</span>
                        <span class="text-xs font-medium text-slate-800">Rp {{ number_format($totals['credit'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <div class="px-4 py-2 border-t border-slate-100 bg-white">
                {{ $transactions->links() }}
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <style>
        .resize-handle {
            position: absolute;
            top: 0;
            right: 0;
            width: 4px;
            height: 100%;
            cursor: col-resize;
            user-select: none;
            z-index: 10;
            touch-action: none;
            transition: all 0.2s;
        }

        .resize-handle:hover {
            background-color: #6366f1;
            width: 6px;
        }

        .resizing {
            cursor: col-resize;
            user-select: none;
        }

        .resizing * {
            user-select: none !important;
        }
    </style>

    <script>
        const table = document.getElementById('cashTransactionsTable');
        const colgroup = document.getElementById('cashTransactionsColgroup');
        const resizableCols = colgroup ? colgroup.querySelectorAll('col.resizable-col') : [];
        const STORAGE_KEY = 'cash_transactions_table_column_widths';
        const filterInputs = document.querySelectorAll('#cashTransactionsTable .filter-input');
        const clearFiltersBtn = document.getElementById('clearFilters');

        function loadColumnWidths() {
            if (!table) {
                return;
            }

            const savedWidths = localStorage.getItem(STORAGE_KEY);
            if (!savedWidths) {
                return;
            }

            try {
                const widths = JSON.parse(savedWidths);
                const headers = table.querySelectorAll('th.resizable');
                headers.forEach((th, index) => {
                    if (widths[index]) {
                        const minWidth = parseInt(th.dataset.minWidth || '90', 10);
                        const safeWidth = Math.max(minWidth, Number(widths[index]));
                        th.style.width = safeWidth + 'px';
                        if (resizableCols[index]) {
                            resizableCols[index].style.width = safeWidth + 'px';
                        }
                    }
                });
            } catch (e) {
                console.error('Error loading cash table widths:', e);
            }
        }

        function saveColumnWidths() {
            if (!table) {
                return;
            }

            const headers = table.querySelectorAll('th.resizable');
            const widths = Array.from(headers).map((th, index) => {
                const colWidth = resizableCols[index] ? parseFloat(resizableCols[index].style.width) : null;
                return Number.isFinite(colWidth) ? colWidth : th.offsetWidth;
            });
            localStorage.setItem(STORAGE_KEY, JSON.stringify(widths));
        }

        function initResizableColumns() {
            if (!table) {
                return;
            }

            const headers = table.querySelectorAll('th.resizable');

            headers.forEach(th => {
                const handle = th.querySelector('.resize-handle');
                if (!handle) {
                    return;
                }

                handle.addEventListener('mousedown', function (e) {
                    e.preventDefault();

                    const startX = e.pageX;
                    const startWidth = th.offsetWidth;

                    document.body.classList.add('resizing');

                    function onMouseMove(moveEvent) {
                        const diff = moveEvent.pageX - startX;
                        const minWidth = parseInt(th.dataset.minWidth || '90', 10);
                        const newWidth = Math.max(minWidth, startWidth + diff);
                        th.style.width = newWidth + 'px';
                        const headers = table.querySelectorAll('th.resizable');
                        const headerIndex = Array.from(headers).indexOf(th);
                        if (headerIndex >= 0 && resizableCols[headerIndex]) {
                            resizableCols[headerIndex].style.width = newWidth + 'px';
                        }
                    }

                    function onMouseUp() {
                        document.body.classList.remove('resizing');
                        document.removeEventListener('mousemove', onMouseMove);
                        document.removeEventListener('mouseup', onMouseUp);
                        saveColumnWidths();
                    }

                    document.addEventListener('mousemove', onMouseMove);
                    document.addEventListener('mouseup', onMouseUp);
                });
            });
        }

        let debounceTimer;
        function updateFilter(name, value) {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const url = new URL(window.location.href);

                if (value !== null && value.toString().trim() !== '') {
                    url.searchParams.set(name, value.toString().trim());
                } else {
                    url.searchParams.delete(name);
                }

                url.searchParams.delete('page');
                window.location.href = url.toString();
            }, 400);
        }

        filterInputs.forEach(input => {
            const name = input.dataset.name;
            const isSelect = input.tagName === 'SELECT';
            const isDate = input.type === 'date';

            if (isSelect || isDate) {
                input.addEventListener('change', e => updateFilter(name, e.target.value));
            } else {
                input.addEventListener('input', e => updateFilter(name, e.target.value));
            }
        });

        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', () => {
                const url = new URL(window.location.href);
                const params = [
                    'transaction_number',
                    'transaction_date',
                    'cash_account_id',
                    'description',
                    'type',
                    'amount',
                    'balance_after',
                    'outlet_id',
                    'date_from',
                    'date_to',
                    'reference_type',
                    'coa_account_id',
                    'coa_type',
                    'coa_group',
                    'exclude_coa_group',
                ];

                params.forEach(param => url.searchParams.delete(param));
                url.searchParams.delete('page');

                window.location.href = url.toString();
            });
        }

        loadColumnWidths();
        initResizableColumns();
    </script>
@endpush