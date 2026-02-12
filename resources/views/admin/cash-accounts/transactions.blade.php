@extends('layouts.admin')

@section('title', 'Transaksi Kas & Bank')

@section('content')
    <div class="container mx-auto px-4 py-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Transaksi Kas & Bank</h1>
                <p class="text-gray-600 mt-1">Daftar semua transaksi kas masuk dan keluar</p>
            </div>
            <a href="{{ route('admin.cash-transactions.create') }}" class="imperial-btn flex items-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Catat Transaksi Baru</span>
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        <div class="t6-card shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="imperial-table min-w-full" id="cashTransactionsTable" style="table-layout: fixed;">
                    <colgroup id="cashTransactionsColgroup">
                        <col class="resizable-col" style="width: 140px;">
                        <col class="resizable-col" style="width: 140px;">
                        <col class="resizable-col" style="width: 220px;">
                        <col class="resizable-col" style="width: 260px;">
                        <col class="resizable-col" style="width: 120px;">
                        <col class="resizable-col" style="width: 150px;">
                        <col class="resizable-col" style="width: 170px;">
                        <col class="resizable-col" style="width: 220px;">
                        <col style="width: 120px;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="resizable px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                                style="min-width: 140px; position: relative;">
                                Nomor
                                <div class="resize-handle"></div>
                            </th>
                            <th class="resizable px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                                style="min-width: 140px; position: relative;">
                                Tanggal
                                <div class="resize-handle"></div>
                            </th>
                            <th class="resizable px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                                style="min-width: 220px; position: relative;">
                                Akun
                                <div class="resize-handle"></div>
                            </th>
                            <th class="resizable px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                                style="min-width: 260px; position: relative;">
                                Deskripsi
                                <div class="resize-handle"></div>
                            </th>
                            <th class="resizable px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                                style="min-width: 120px; position: relative;">
                                Jenis
                                <div class="resize-handle"></div>
                            </th>
                            <th class="resizable px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"
                                style="min-width: 150px; position: relative;">
                                Jumlah
                                <div class="resize-handle"></div>
                            </th>
                            <th class="resizable px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"
                                style="min-width: 170px; position: relative;">
                                Saldo
                                <div class="resize-handle"></div>
                            </th>
                            <th class="resizable px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                                style="min-width: 220px; position: relative;">
                                COA
                                <div class="resize-handle"></div>
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                        <tr class="filter-row bg-gray-50">
                            <th class="px-3 py-1">
                                <input type="text"
                                    class="filter-input w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    data-name="transaction_number" value="{{ request('transaction_number') }}"
                                    placeholder="Filter nomor...">
                            </th>
                            <th class="px-3 py-1">
                                <input type="date"
                                    class="filter-input w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    data-name="transaction_date" value="{{ request('transaction_date') }}">
                            </th>
                            <th class="px-3 py-1">
                                <select
                                    class="filter-input w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    data-name="cash_account_id">
                                    <option value="">Semua Akun</option>
                                    @foreach($accounts as $account)
                                        <option value="{{ $account->id }}" {{ request('cash_account_id') == $account->id ? 'selected' : '' }}>
                                            {{ $account->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </th>
                            <th class="px-3 py-1">
                                <input type="text"
                                    class="filter-input w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    data-name="description" value="{{ request('description') }}"
                                    placeholder="Filter deskripsi...">
                            </th>
                            <th class="px-3 py-1">
                                <select
                                    class="filter-input w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    data-name="type">
                                    <option value="">Semua</option>
                                    <option value="in" {{ request('type') === 'in' ? 'selected' : '' }}>Masuk</option>
                                    <option value="out" {{ request('type') === 'out' ? 'selected' : '' }}>Keluar</option>
                                </select>
                            </th>
                            <th class="px-3 py-1">
                                <input type="text"
                                    class="filter-input w-full px-2 py-1 text-xs border border-gray-300 rounded text-right focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    data-name="amount" value="{{ request('amount') }}" placeholder="Filter jumlah...">
                            </th>
                            <th class="px-3 py-1">
                                <input type="text"
                                    class="filter-input w-full px-2 py-1 text-xs border border-gray-300 rounded text-right focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    data-name="balance_after" value="{{ request('balance_after') }}" placeholder="Filter saldo...">
                            </th>
                            <th class="px-3 py-1">
                                <select
                                    class="filter-input w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    data-name="coa_account_id">
                                    <option value="">Semua COA</option>
                                    @foreach($coaAccounts as $coaAccount)
                                        <option value="{{ $coaAccount->id }}" {{ request('coa_account_id') == $coaAccount->id ? 'selected' : '' }}>
                                            {{ $coaAccount->code }}
                                        </option>
                                    @endforeach
                                </select>
                            </th>
                            <th class="px-3 py-1">
                                <button type="button" id="clearFilters"
                                    class="w-full px-2 py-1 text-xs bg-gray-200 hover:bg-gray-300 rounded transition-colors"
                                    title="Clear all filters">
                                    <i class="fas fa-times"></i>
                                </button>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <a href="{{ route('admin.cash-transactions.show', $transaction) }}" class="hover:text-indigo-600 hover:underline">
                                        {{ $transaction->transaction_number }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $transaction->transaction_date->format('d M Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $transaction->cashAccount->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $transaction->cashAccount->code }}</div>
                                    <div class="text-xs text-gray-500">{{ $transaction->cashAccount->outlet->name ?? 'Outlet tidak diset' }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <div class="truncate max-w-[360px]" title="{{ $transaction->description }}">
                                        {{ $transaction->description }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($transaction->type === 'in')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                            Masuk
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                            Keluar
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    @if($transaction->type === 'in')
                                        <span class="text-sm font-semibold text-green-600">
                                            + Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                                        </span>
                                    @else
                                        <span class="text-sm font-semibold text-red-600">
                                            - Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                                    Rp {{ number_format($transaction->balance_after, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    @if($transaction->coaAccount)
                                        <span title="{{ $transaction->coaAccount->code }} - {{ $transaction->coaAccount->name }}">
                                            {{ $transaction->coaAccount->code }} - {{ Str::limit($transaction->coaAccount->name, 28) }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <div class="flex items-center justify-center space-x-2">
                                        <a href="{{ route('admin.cash-transactions.show', $transaction) }}"
                                            class="text-indigo-600 hover:text-indigo-900" title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.cash-transactions.print', $transaction) }}" target="_blank"
                                            class="text-gray-600 hover:text-gray-900" title="Cetak Voucher">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <a href="{{ route('admin.cash-transactions.edit', $transaction) }}"
                                            class="text-amber-600 hover:text-amber-900" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.cash-transactions.destroy', $transaction) }}" method="POST"
                                            class="inline-block"
                                            onsubmit="return confirm('Apakah Anda yakin ingin menghapus transaksi ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <i class="fas fa-file-invoice-dollar text-4xl text-gray-300 mb-3"></i>
                                        <p>Tidak ada transaksi</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-gray-200">
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
            width: 8px;
            height: 100%;
            cursor: col-resize;
            user-select: none;
            z-index: 10;
            touch-action: none;
        }

        .resize-handle:hover {
            background-color: rgba(59, 130, 246, 0.5);
        }

        .resizing {
            cursor: col-resize;
            user-select: none;
        }

        .filter-row th {
            border-bottom: 2px solid #e5e7eb;
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
                        th.style.width = widths[index] + 'px';
                        if (resizableCols[index]) {
                            resizableCols[index].style.width = widths[index] + 'px';
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
                        const newWidth = Math.max(90, startWidth + diff);
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
