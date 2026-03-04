@extends('layouts.admin')

@section('title', 'Edit Transaksi Kas/Bank')

@section('content')
    <div class="container mx-auto px-4 py-6">
        <div class="mb-6">
            <div class="flex items-center space-x-2 text-sm text-gray-600 mb-2">
                <a href="{{ route('admin.cash-transactions.index', request()->query()) }}" class="hover:text-indigo-600">Transaksi</a>
                <span>/</span>
                <span class="text-gray-900">Edit Transaksi</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Transaksi Kas/Bank</h1>
            <p class="text-gray-600 mt-1">
                {{ $cashTransaction->transaction_number }}
                <span
                    class="px-2 py-0.5 rounded text-xs ml-2 {{ $cashTransaction->type == 'in' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    {{ $cashTransaction->type == 'in' ? 'Kas Masuk' : 'Kas Keluar' }}
                </span>
            </p>
        </div>

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
                <div class="font-semibold mb-2">Periksa formulir:</div>
                <ul class="list-disc list-inside space-y-1 text-sm">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded-lg shadow">
            <form action="{{ route('admin.cash-transactions.update', array_merge(['cashTransaction' => $cashTransaction], request()->query())) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="p-6 space-y-6">
                    {{-- Info Readonly --}}
                    <div class="bg-indigo-50/50 border border-indigo-100 p-4 rounded-lg flex items-center justify-between">
                        <div>
                            <span class="text-xs text-indigo-500 font-semibold uppercase tracking-wide">Akun Kas/Bank</span>
                            <div class="font-bold text-gray-900 mt-1">{{ $cashTransaction->cashAccount->name }}
                                ({{ $cashTransaction->cashAccount->code }})</div>
                        </div>
                        <div class="text-right">
                            <span class="text-xs text-indigo-500 font-semibold uppercase tracking-wide">Input Oleh</span>
                            <div class="font-medium text-gray-900 mt-1">{{ $cashTransaction->creator->name ?? '-' }}</div>
                        </div>
                    </div>

@php
    $rows = old('rows');
    if (!is_array($rows) || count($rows) === 0) {
        $rows = [];
        foreach ($relatedTransactions as $rt) {
            $rows[] = [
                'id' => $rt->id,
                'coa_account_id' => $rt->coa_account_id,
                'amount' => $rt->amount,
                'description' => $rt->description,
            ];
        }
    }

    $coaOptions = $coaAccounts->map(fn($coa) => [
        'id' => (string) $coa->id,
        'label' => $coa->code . ' - ' . $coa->name,
    ])->values();
@endphp
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="transaction_date" class="block text-sm font-medium text-gray-700 mb-2">
                                Tanggal Transaksi <span class="text-red-500">*</span>
                            </label>
                            <input type="date" id="transaction_date" name="transaction_date"
                                value="{{ old('transaction_date', $cashTransaction->transaction_date->format('Y-m-d')) }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('transaction_date') border-red-500 @enderror"
                                required>
                            @error('transaction_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1.5 text-xs text-amber-600 flex items-center gap-1.5">
                                <i class="fas fa-exclamation-triangle"></i> Mengubah tanggal akan memicu perhitungan ulang saldo.
                            </p>
                        </div>
                    </div>

                    <input type="hidden" name="transaction_category" value="{{ $transactionCategory }}">

                    @if($transactionCategory === 'purchase_payment')
                    <div id="purchase-payment-section" class="rounded-lg border border-indigo-200 bg-indigo-50/30 p-5 space-y-4 mt-6">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-indigo-600 font-semibold mb-1">Kategori: Bayar Hutang Purchase</p>
                            <h2 class="text-lg font-semibold text-gray-900">Pembayaran Purchase</h2>
                        </div>
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label for="purchase_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Pilih Purchase <span class="text-red-500">*</span>
                                </label>
                                <select id="purchase_id"
                                        name="purchase_id"
                                        class="ui-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('purchase_id') border-red-500 @enderror">
                                    <option value="">-- Pilih Purchase --</option>
                                    @foreach($outstandingPurchases as $purchase)
                                        <option value="{{ $purchase->id }}"
                                                data-remaining="{{ $purchase->remaining_amount }}"
                                                data-supplier-name="{{ $purchase->supplier->name ?? '-' }}"
                                                data-po-number="{{ $purchase->purchase_number }}"
                                                data-po-date="{{ $purchase->purchase_date->format('d/m/Y') }}"
                                                {{ (string) old('purchase_id', $cashTransaction->reference_id) === (string) $purchase->id ? 'selected' : '' }}>
                                            {{ $purchase->purchase_number }} | {{ $purchase->supplier->name ?? '-' }} | Outlet {{ $purchase->outlet->name ?? '-' }} | Sisa: Rp {{ number_format($purchase->remaining_amount, 0, ',', '.') }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('purchase_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="purchase_description" class="block text-sm font-medium text-gray-700 mb-2">
                                    Deskripsi Voucher <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       id="purchase_description"
                                       name="purchase_description"
                                       value="{{ old('purchase_description', $cashTransaction->description) }}"
                                       class="w-full px-3 py-2 border border-gray-300 bg-gray-50 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('purchase_description') border-red-500 @enderror"
                                       placeholder="Otomatis terisi setelah pilih purchase"
                                       required>
                                @error('purchase_description')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="purchase_amount" class="block text-sm font-medium text-gray-700 mb-2">
                                    Jumlah Pembayaran <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       id="purchase_amount"
                                       name="purchase_amount"
                                       value="{{ old('purchase_amount', $cashTransaction->amount) }}"
                                       inputmode="decimal"
                                       data-currency-input="1"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('purchase_amount') border-red-500 @enderror"
                                       placeholder="0">
                                @error('purchase_amount')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p id="purchase-remaining-info" class="mt-1 text-xs text-gray-500"></p>
                            </div>
                            <div>
                                <label for="purchase_notes" class="block text-sm font-medium text-gray-700 mb-2">
                                    Catatan (Internal)
                                </label>
                                <textarea id="purchase_notes"
                                          name="purchase_notes"
                                          rows="3"
                                          class="ui-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('purchase_notes') border-red-500 @enderror"
                                          placeholder="Catatan tambahan (opsional)">{{ old('purchase_notes', $cashTransaction->notes) }}</textarea>
                                @error('purchase_notes')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs uppercase tracking-wide text-indigo-600 font-semibold">Detail</p>
                                <h2 class="text-lg font-semibold text-gray-900">Daftar Transaksi</h2>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" id="add-row"
                                        class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium shadow-sm transition-all flex items-center gap-2">
                                    <i class="fas fa-plus"></i> Tambah Baris
                                </button>
                            </div>
                        </div>

                        <div class="overflow-x-auto rounded-lg border border-gray-200">
                            <table class="min-w-[980px] w-full text-sm">
                                <thead class="bg-gray-50 text-gray-600">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold w-12">#</th>
                                        <th class="px-4 py-3 text-left font-semibold min-w-[360px]">Pilih Akun</th>
                                        <th class="px-4 py-3 text-left font-semibold min-w-[320px]">Deskripsi</th>
                                        <th class="px-4 py-3 text-left font-semibold w-56">Jumlah</th>
                                        <th class="px-4 py-3 text-right font-semibold w-16"></th>
                                    </tr>
                                </thead>
                                <tbody id="transaction-rows" class="divide-y divide-gray-200">
                                    @foreach($rows as $i => $row)
                                        <tr data-index="{{ $i }}">
                                            <input type="hidden" name="rows[{{ $i }}][id]" value="{{ $row['id'] ?? '' }}">
                                            <td class="px-4 py-3 text-gray-500" data-role="row-number">{{ $i + 1 }}</td>
                                            <td class="px-4 py-3">
                                                <select name="rows[{{ $i }}][coa_account_id]"
                                                        class="ui-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('rows.'.$i.'.coa_account_id') border-red-500 @enderror"
                                                        data-role="coa"
                                                        data-selected="{{ $row['coa_account_id'] ?? '' }}"
                                                        required>
                                                    <option value="">-- Pilih Akun --</option>
                                                </select>
                                                @error('rows.'.$i.'.coa_account_id')
                                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                                @enderror
                                            </td>
                                            <td class="px-4 py-3">
                                                <input type="text"
                                                       name="rows[{{ $i }}][description]"
                                                       value="{{ $row['description'] ?? '' }}"
                                                       class="ui-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('rows.'.$i.'.description') border-red-500 @enderror"
                                                       placeholder="Contoh: Pembayaran listrik"
                                                       required>
                                                @error('rows.'.$i.'.description')
                                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                                @enderror
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="relative">
                                                    <span class="absolute left-3 top-2 text-gray-500">Rp</span>
                                                    <input type="text"
                                                           name="rows[{{ $i }}][amount]"
                                                           value="{{ $row['amount'] ?? '' }}"
                                                           inputmode="decimal"
                                                           data-currency-input="1"
                                                           class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('rows.'.$i.'.amount') border-red-500 @enderror"
                                                           placeholder="0"
                                                           required>
                                                </div>
                                                @error('rows.'.$i.'.amount')
                                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                                @enderror
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <button type="button"
                                                        class="px-2.5 py-2 text-rose-600 hover:bg-rose-50 rounded-lg transition"
                                                        data-action="remove-row"
                                                        title="Hapus baris">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="flex justify-end">
                            <div class="w-full max-w-md rounded-lg border border-indigo-100 bg-indigo-50/40 px-4 py-3">
                                <div class="flex items-center justify-between text-sm text-gray-600">
                                    <span>Total Baris</span>
                                    <span id="rows-count-summary" class="font-semibold text-gray-800">{{ count($rows) }} baris</span>
                                </div>
                                <div class="mt-1 flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-700">Total Jumlah</span>
                                    <span id="rows-total-amount" class="text-lg font-bold text-indigo-700">Rp 0</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Catatan Global</label>
                        <textarea id="notes" name="notes" rows="3"
                            class="ui-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('notes') border-red-500 @enderror"
                            placeholder="Catatan untuk seluruh transaksi">{{ old('notes', $cashTransaction->notes) }}</textarea>
                        @error('notes')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    @endif
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3 rounded-b-lg">
                    <a href="{{ route('admin.cash-transactions.index', request()->query()) }}"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                        Batal
                    </a>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg shadow-sm transition-colors flex items-center gap-2"
                        onclick="return confirm('Apakah Anda yakin ingin menyimpan perubahan? Saldo akan dihitung ulang.');">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <template id="row-template">
        <tr data-index="__INDEX__">
            <td class="px-4 py-3 text-gray-500" data-role="row-number"></td>
            <td class="px-4 py-3">
                <select name="rows[__INDEX__][coa_account_id]"
                        class="ui-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        data-role="coa"
                        required>
                    <option value="">-- Pilih Akun --</option>
                </select>
            </td>
            <td class="px-4 py-3">
                <input type="text"
                       name="rows[__INDEX__][description]"
                       class="ui-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                       placeholder="Contoh: Pembayaran listrik"
                       required>
            </td>
            <td class="px-4 py-3">
                <div class="relative">
                    <span class="absolute left-3 top-2 text-gray-500">Rp</span>
                    <input type="text"
                           name="rows[__INDEX__][amount]"
                           inputmode="decimal"
                           data-currency-input="1"
                           class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                           placeholder="0"
                           required>
                </div>
            </td>
            <td class="px-4 py-3 text-right">
                <button type="button"
                        class="px-2.5 py-2 text-rose-600 hover:bg-rose-50 rounded-lg transition"
                        data-action="remove-row"
                        title="Hapus baris">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    </template>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const coaOptions = @json($coaOptions);
            const form = document.querySelector('form[action*="cash-transactions"]');
            const rowsEl = document.getElementById('transaction-rows');
            const addBtn = document.getElementById('add-row');
            const rowsCountSummaryEl = document.getElementById('rows-count-summary');
            const rowsTotalAmountEl = document.getElementById('rows-total-amount');
            const template = document.getElementById('row-template').innerHTML.trim();
            
            let nextIndex = 0;

            function parseCurrencyInput(value) {
                const raw = String(value ?? '').trim().replace(/[^\d,.\-]/g, '');
                if (!raw) {
                    return 0;
                }

                let normalized = raw;
                const hasComma = normalized.includes(',');
                const dotCount = (normalized.match(/\./g) || []).length;

                if (hasComma) {
                    normalized = normalized.replace(/\./g, '').replace(',', '.');
                } else if (dotCount > 0) {
                    const dotParts = normalized.split('.');
                    const decimalLike = dotCount === 1
                        && dotParts[1]
                        && dotParts[1].length > 0
                        && dotParts[1].length <= 2;

                    if (!decimalLike) {
                        normalized = normalized.replace(/\./g, '');
                    }
                }

                normalized = normalized.replace(/(?!^)-/g, '');
                const parsed = Number(normalized);
                return Number.isFinite(parsed) ? parsed : 0;
            }

            function formatCurrencyInput(value) {
                const numeric = Number(value || 0);
                if (!Number.isFinite(numeric)) {
                    return '';
                }

                return numeric.toLocaleString('id-ID', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2,
                });
            }

            function sanitizeCurrencyInputValue(value) {
                const raw = String(value ?? '').trim();
                if (!raw) {
                    return '';
                }
                return String(parseCurrencyInput(raw));
            }

            function attachCurrencyInput(input, onChange) {
                if (!input || input.dataset.currencyBound === '1') {
                    return;
                }

                input.dataset.currencyBound = '1';
                const render = () => {
                    const raw = String(input.value ?? '').trim();
                    if (!raw) {
                        if (typeof onChange === 'function') {
                            onChange();
                        }
                        return;
                    }

                    input.value = formatCurrencyInput(parseCurrencyInput(raw));
                    if (typeof onChange === 'function') {
                        onChange();
                    }
                };

                input.addEventListener('input', render);
                input.addEventListener('blur', render);
                render();
            }

            function formatCurrency(amount) {
                return 'Rp ' + amount.toLocaleString('id-ID', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2,
                });
            }

            function refreshRowNumbers() {
                rowsEl.querySelectorAll('tr').forEach((row, idx) => {
                    const numEl = row.querySelector('[data-role="row-number"]');
                    if (numEl) {
                        numEl.textContent = idx + 1;
                    }
                });
            }

            function refreshRowsSummary() {
                const rows = rowsEl.querySelectorAll('tr');
                const totalAmount = Array.from(rows).reduce((carry, row) => {
                    const input = row.querySelector('input[name$="[amount]"]');
                    if (!input) {
                        return carry;
                    }

                    const parsed = parseCurrencyInput(input.value);
                    if (Number.isNaN(parsed) || parsed <= 0) {
                        return carry;
                    }

                    return carry + parsed;
                }, 0);

                if (rowsCountSummaryEl) {
                    rowsCountSummaryEl.textContent = rows.length + ' baris';
                }
                if (rowsTotalAmountEl) {
                    rowsTotalAmountEl.textContent = formatCurrency(totalAmount);
                }
            }

            function fillCoaOptions(select, selectedValue) {
                if (!select) {
                    return;
                }

                const placeholder = '<option value="">-- Pilih Akun --</option>';
                const optionHtml = coaOptions.map((item) => {
                    const selected = String(item.id) === String(selectedValue || '') ? ' selected' : '';
                    return `<option value="${item.id}"${selected}>${item.label}</option>`;
                }).join('');

                select.innerHTML = placeholder + optionHtml;
            }

            function bindRow(row) {
                const removeBtn = row.querySelector('[data-action="remove-row"]');
                if (removeBtn) {
                    removeBtn.addEventListener('click', function () {
                        const rows = rowsEl.querySelectorAll('tr');
                        if (rows.length <= 1) {
                            row.querySelectorAll('input:not([type="hidden"])').forEach((input) => input.value = '');
                            const coa = row.querySelector('[data-role="coa"]');
                            fillCoaOptions(coa, '');
                            refreshRowsSummary();
                            return;
                        }
                        
                        // Add markup for deleted row tracking if it had an ID
                        const idInput = row.querySelector('input[name$="[id]"]');
                        if (idInput && idInput.value) {
                            const hiddenDelete = document.createElement('input');
                            hiddenDelete.type = 'hidden';
                            hiddenDelete.name = 'deleted_row_ids[]';
                            hiddenDelete.value = idInput.value;
                            form.appendChild(hiddenDelete);
                        }

                        row.remove();
                        refreshRowNumbers();
                        refreshRowsSummary();
                    });
                }

                const coa = row.querySelector('[data-role="coa"]');
                const selected = coa ? coa.getAttribute('data-selected') : '';
                fillCoaOptions(coa, selected);
                if (coa) {
                    coa.removeAttribute('data-selected');
                }

                const amountInput = row.querySelector('input[name$="[amount]"]');
                if (amountInput) {
                    attachCurrencyInput(amountInput, refreshRowsSummary);
                    amountInput.addEventListener('input', refreshRowsSummary);
                    amountInput.addEventListener('change', refreshRowsSummary);
                }
            }

            function buildRow(index) {
                const wrapper = document.createElement('tbody');
                wrapper.innerHTML = template.replace(/__INDEX__/g, index);
                return wrapper.firstElementChild;
            }

            if (rowsEl) {
                rowsEl.querySelectorAll('tr[data-index]').forEach((row) => {
                    const idx = parseInt(row.getAttribute('data-index'), 10);
                    if (!Number.isNaN(idx)) {
                        nextIndex = Math.max(nextIndex, idx + 1);
                    }
                    bindRow(row);
                });
                refreshRowNumbers();
                refreshRowsSummary();
            }

            if (addBtn) {
                addBtn.addEventListener('click', function () {
                    const newRow = buildRow(nextIndex++);
                    rowsEl.appendChild(newRow);
                    bindRow(newRow);
                    refreshRowNumbers();
                    refreshRowsSummary();
                });
            }

            if (form) {
                form.addEventListener('submit', function () {
                    form.querySelectorAll('[data-currency-input="1"]').forEach((input) => {
                        input.value = sanitizeCurrencyInputValue(input.value);
                    });
                });
            }

            const purchaseSelectEl = document.getElementById('purchase_id');
            const purchaseAmountEl = document.getElementById('purchase_amount');
            const purchaseDescriptionEl = document.getElementById('purchase_description');
            const purchaseRemainingInfoEl = document.getElementById('purchase-remaining-info');

            function syncPurchaseSelection() {
                if (!purchaseSelectEl || !purchaseRemainingInfoEl) {
                    return;
                }

                const selectedOption = purchaseSelectEl.options[purchaseSelectEl.selectedIndex];
                const remaining = selectedOption ? Number(selectedOption.getAttribute('data-remaining')) : NaN;
                const supplier = selectedOption ? selectedOption.getAttribute('data-supplier-name') : '';
                const poNumber = selectedOption ? selectedOption.getAttribute('data-po-number') : '';
                const poDate = selectedOption ? selectedOption.getAttribute('data-po-date') : '';

                if (!selectedOption || !selectedOption.value || Number.isNaN(remaining)) {
                    purchaseRemainingInfoEl.innerHTML = '';
                    return;
                }

                purchaseRemainingInfoEl.innerHTML = `Sisa hutang: <span class="font-medium text-gray-900">Rp ${remaining.toLocaleString('id-ID')}</span>`;

                // Sync description otomatis jika masih kosong atau berisi pola yang sama
                if (purchaseDescriptionEl) {
                    const newDesc = `Pembayaran hutang supplier ${supplier}, no po ${poNumber} tgl ${poDate}`;
                    // Isi otomatis jika tujuan deskripsi kosong atau hanya default placeholder atau pola pembayaran lama
                    if (!purchaseDescriptionEl.value || 
                        purchaseDescriptionEl.value === '' || 
                        purchaseDescriptionEl.value.startsWith('Pembayaran hutang supplier') ||
                        purchaseDescriptionEl.value.startsWith('Pembayaran Purchase #')) {
                        purchaseDescriptionEl.value = newDesc;
                    }
                }
            }
            
            if (purchaseSelectEl) {
                purchaseSelectEl.addEventListener('change', syncPurchaseSelection);
                syncPurchaseSelection(); // initial render
            }

            // Add validation for max amount
            if (purchaseAmountEl) {
                purchaseAmountEl.addEventListener('blur', function() {
                    const selectedOption = purchaseSelectEl.options[purchaseSelectEl.selectedIndex];
                    const remaining = selectedOption ? Number(selectedOption.getAttribute('data-remaining')) : NaN;
                    
                    if (!Number.isNaN(remaining)) {
                        let entered = parseCurrencyInput(this.value);
                        if (entered > remaining) {
                            this.value = formatCurrencyInput(remaining);
                            this.classList.add('bg-amber-50', 'border-amber-400');
                            setTimeout(() => this.classList.remove('bg-amber-50', 'border-amber-400'), 500);
                        }
                    }
                });
            }
        });
    </script>
@endsection
