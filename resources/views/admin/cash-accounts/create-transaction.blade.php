@extends('layouts.admin')

@section('title', 'Catat Transaksi Kas/Bank')

@section('content')
@php
    $rows = old('rows');
    if (!is_array($rows) || count($rows) === 0) {
        $rows = [
            [
                'coa_account_id' => '',
                'amount' => '',
                'description' => '',
            ],
        ];
    }

    $coaByType = [
        'in' => $coaIncomeAccounts->map(fn($coa) => [
            'id' => (string) $coa->id,
            'label' => $coa->code . ' - ' . $coa->name,
        ])->values(),
        'out' => $coaExpenseAccounts->map(fn($coa) => [
            'id' => (string) $coa->id,
            'label' => $coa->code . ' - ' . $coa->name,
        ])->values(),
    ];

    $requestedCategory = request('transaction_category');
    $allowedCategories = ['general', 'purchase_payment', 'book_transfer'];
    $transactionCategory = old(
        'transaction_category',
        in_array($requestedCategory, $allowedCategories, true) ? $requestedCategory : 'general'
    );
@endphp
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <div class="flex items-center space-x-2 text-sm text-gray-600 mb-2">
            <a href="{{ route('admin.cash-transactions.index') }}" class="hover:text-indigo-600">Transaksi</a>
            <span>/</span>
            <span class="text-gray-900">Catat Baru</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">Catat Transaksi Kas/Bank</h1>
        <p class="text-gray-600 mt-1">Satu form untuk transaksi umum, bayar hutang purchase, dan pindah buku</p>
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
        <form action="{{ route('admin.cash-transactions.store') }}" method="POST" novalidate>
            @csrf

            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div>
                        <label for="transaction_category" class="block text-sm font-medium text-gray-700 mb-2">
                            Kategori Transaksi <span class="text-red-500">*</span>
                        </label>
                        <select id="transaction_category"
                                name="transaction_category"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('transaction_category') border-red-500 @enderror"
                                required>
                            <option value="general" {{ $transactionCategory === 'general' ? 'selected' : '' }}>Transaksi Umum</option>
                            <option value="purchase_payment" {{ $transactionCategory === 'purchase_payment' ? 'selected' : '' }}>Bayar Hutang Purchase</option>
                            <option value="book_transfer" {{ $transactionCategory === 'book_transfer' ? 'selected' : '' }}>Pindah Buku</option>
                        </select>
                        @error('transaction_category')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="cash_account_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Akun Kas/Bank <span class="text-red-500">*</span>
                        </label>
                        <select id="cash_account_id"
                                name="cash_account_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('cash_account_id') border-red-500 @enderror"
                                required>
                            <option value="">-- Pilih Akun --</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}" {{ old('cash_account_id') == $account->id ? 'selected' : '' }}>
                                    {{ $account->name }} ({{ $account->code }}) - Saldo: Rp {{ number_format($account->current_balance, 0, ',', '.') }}
                                </option>
                            @endforeach
                        </select>
                        @error('cash_account_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="type_display" class="block text-sm font-medium text-gray-700 mb-2">
                            Jenis Transaksi <span class="text-red-500">*</span>
                        </label>
                        <select id="type_display"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('type') border-red-500 @enderror"
                                required>
                            <option value="">-- Pilih Jenis --</option>
                            <option value="in" {{ old('type') === 'in' ? 'selected' : '' }}>Kas Masuk</option>
                            <option value="out" {{ old('type') === 'out' ? 'selected' : '' }}>Kas Keluar</option>
                        </select>
                        @error('type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="transaction_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Tanggal Transaksi <span class="text-red-500">*</span>
                        </label>
                        <input type="date"
                               id="transaction_date"
                               name="transaction_date"
                               value="{{ old('transaction_date', date('Y-m-d')) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('transaction_date') border-red-500 @enderror"
                               required>
                        @error('transaction_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div id="general-transaction-section" class="space-y-6">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-indigo-600 font-semibold">Detail</p>
                            <h2 class="text-lg font-semibold text-gray-900">Daftar Transaksi Umum</h2>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" id="add-row"
                                    class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium shadow-sm transition-all flex items-center gap-2">
                                <i class="fas fa-plus"></i> Tambah Baris
                            </button>
                            <button type="button" id="clear-rows"
                                    class="px-3 py-1.5 bg-white border border-rose-300 text-rose-700 hover:bg-rose-50 rounded-lg text-sm font-medium shadow-sm transition-colors flex items-center gap-2">
                                <i class="fas fa-times"></i> Hapus Semua
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
                                        <td class="px-4 py-3 text-gray-500" data-role="row-number">{{ $i + 1 }}</td>
                                        <td class="px-4 py-3">
                                            <select name="rows[{{ $i }}][coa_account_id]"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('rows.'.$i.'.coa_account_id') border-red-500 @enderror"
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
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('rows.'.$i.'.description') border-red-500 @enderror"
                                                   placeholder="Contoh: Pembayaran listrik"
                                                   required>
                                            @error('rows.'.$i.'.description')
                                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                            @enderror
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="relative">
                                                <span class="absolute left-3 top-2 text-gray-500">Rp</span>
                                                <input type="number"
                                                       name="rows[{{ $i }}][amount]"
                                                       value="{{ $row['amount'] ?? '' }}"
                                                       step="0.01"
                                                       min="0.01"
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

                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Catatan Global</label>
                        <textarea id="notes"
                                  name="notes"
                                  rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('notes') border-red-500 @enderror"
                                  placeholder="Catatan untuk seluruh transaksi (opsional)">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div id="purchase-payment-section" class="hidden rounded-lg border border-indigo-200 bg-indigo-50/30 p-5 space-y-4">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-indigo-600 font-semibold mb-1">Kategori: Bayar Hutang Purchase</p>
                        <h2 class="text-lg font-semibold text-gray-900">Pembayaran Purchase Belum Lunas</h2>
                    </div>
                    <div>
                        <label for="purchase_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Pilih Purchase <span class="text-red-500">*</span>
                        </label>
                        <select id="purchase_id"
                                name="purchase_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('purchase_id') border-red-500 @enderror">
                            <option value="">-- Pilih Purchase --</option>
                            @foreach($outstandingPurchases as $purchase)
                                <option value="{{ $purchase->id }}"
                                        data-remaining="{{ $purchase->remaining_amount }}"
                                        {{ (string) old('purchase_id') === (string) $purchase->id ? 'selected' : '' }}>
                                    {{ $purchase->purchase_number }} | {{ $purchase->supplier->name ?? '-' }} | Outlet {{ $purchase->outlet->name ?? '-' }} | Sisa: Rp {{ number_format($purchase->remaining_amount, 0, ',', '.') }}
                                </option>
                            @endforeach
                        </select>
                        @error('purchase_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="purchase_amount" class="block text-sm font-medium text-gray-700 mb-2">
                                Jumlah Pembayaran <span class="text-red-500">*</span>
                            </label>
                            <input type="number"
                                   id="purchase_amount"
                                   name="purchase_amount"
                                   value="{{ old('purchase_amount') }}"
                                   min="0.01"
                                   step="0.01"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('purchase_amount') border-red-500 @enderror"
                                   placeholder="0">
                            @error('purchase_amount')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p id="purchase-remaining-info" class="mt-1 text-xs text-gray-500"></p>
                        </div>
                        <div>
                            <label for="purchase_notes" class="block text-sm font-medium text-gray-700 mb-2">
                                Catatan Pembayaran
                            </label>
                            <textarea id="purchase_notes"
                                      name="purchase_notes"
                                      rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('purchase_notes') border-red-500 @enderror"
                                      placeholder="Catatan pembayaran purchase (opsional)">{{ old('purchase_notes') }}</textarea>
                            @error('purchase_notes')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div id="book-transfer-section" class="hidden rounded-lg border border-emerald-200 bg-emerald-50/30 p-5 space-y-4">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-emerald-600 font-semibold mb-1">Kategori: Pindah Buku</p>
                        <h2 class="text-lg font-semibold text-gray-900">Transfer Antar Rekening</h2>
                    </div>

                    <div>
                        <label for="transfer_to_cash_account_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Rekening Tujuan <span class="text-red-500">*</span>
                        </label>
                        <select id="transfer_to_cash_account_id"
                                name="transfer_to_cash_account_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('transfer_to_cash_account_id') border-red-500 @enderror">
                            <option value="">-- Pilih Rekening Tujuan --</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}" {{ (string) old('transfer_to_cash_account_id') === (string) $account->id ? 'selected' : '' }}>
                                    {{ $account->name }} ({{ $account->code }}) - Saldo: Rp {{ number_format($account->current_balance, 0, ',', '.') }}
                                </option>
                            @endforeach
                        </select>
                        @error('transfer_to_cash_account_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="transfer_amount" class="block text-sm font-medium text-gray-700 mb-2">
                                Jumlah Pindah Buku <span class="text-red-500">*</span>
                            </label>
                            <input type="number"
                                   id="transfer_amount"
                                   name="transfer_amount"
                                   value="{{ old('transfer_amount') }}"
                                   min="0.01"
                                   step="0.01"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('transfer_amount') border-red-500 @enderror"
                                   placeholder="0">
                            @error('transfer_amount')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="transfer_notes" class="block text-sm font-medium text-gray-700 mb-2">
                                Catatan Pindah Buku
                            </label>
                            <textarea id="transfer_notes"
                                      name="transfer_notes"
                                      rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('transfer_notes') border-red-500 @enderror"
                                      placeholder="Catatan pindah buku (opsional)">{{ old('transfer_notes') }}</textarea>
                            @error('transfer_notes')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="transfer_description" class="block text-sm font-medium text-gray-700 mb-2">
                            Deskripsi Pindah Buku <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               id="transfer_description"
                               name="transfer_description"
                               value="{{ old('transfer_description') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('transfer_description') border-red-500 @enderror"
                               placeholder="Contoh: Pindah modal operasional outlet"
                               maxlength="500">
                        @error('transfer_description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3 rounded-b-lg">
                <input type="hidden" name="type" id="type_hidden" value="{{ old('type') }}">
                <a href="{{ route('admin.cash-transactions.index') }}"
                   class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Batal
                </a>
                <button type="submit"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg">
                    Catat Transaksi
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
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    data-role="coa"
                    required>
                <option value="">-- Pilih Akun --</option>
            </select>
        </td>
        <td class="px-4 py-3">
            <input type="text"
                   name="rows[__INDEX__][description]"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                   placeholder="Contoh: Pembayaran listrik"
                   required>
        </td>
        <td class="px-4 py-3">
            <div class="relative">
                <span class="absolute left-3 top-2 text-gray-500">Rp</span>
                <input type="number"
                       name="rows[__INDEX__][amount]"
                       step="0.01"
                       min="0.01"
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
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const categoryEl = document.getElementById('transaction_category');
        const typeEl = document.getElementById('type_display');
        const generalSection = document.getElementById('general-transaction-section');
        const purchaseSection = document.getElementById('purchase-payment-section');
        const transferSection = document.getElementById('book-transfer-section');
        const purchaseSelectEl = document.getElementById('purchase_id');
        const purchaseAmountEl = document.getElementById('purchase_amount');
        const purchaseRemainingInfoEl = document.getElementById('purchase-remaining-info');

        const rowsEl = document.getElementById('transaction-rows');
        const addBtn = document.getElementById('add-row');
        const clearBtn = document.getElementById('clear-rows');
        const template = document.getElementById('row-template').innerHTML.trim();
        const coaByType = @json($coaByType);

        let nextIndex = 0;

        function refreshRowNumbers() {
            rowsEl.querySelectorAll('tr').forEach((row, idx) => {
                const numEl = row.querySelector('[data-role="row-number"]');
                if (numEl) {
                    numEl.textContent = idx + 1;
                }
            });
        }

        function fillCoaOptions(select, type, selectedValue) {
            if (!select) {
                return;
            }

            const options = coaByType[type] || [];
            const placeholder = '<option value="">-- Pilih Akun --</option>';
            const optionHtml = options.map((item) => {
                const selected = String(item.id) === String(selectedValue || '') ? ' selected' : '';
                return `<option value="${item.id}"${selected}>${item.label}</option>`;
            }).join('');

            select.innerHTML = placeholder + optionHtml;
        }

        function syncAllCoaByType() {
            const type = typeEl.value;
            rowsEl.querySelectorAll('[data-role="coa"]').forEach((select) => {
                const current = select.value;
                fillCoaOptions(select, type, current);
            });
        }

        function syncCategoryUI() {
            const category = categoryEl ? categoryEl.value : 'general';

            if (generalSection) {
                generalSection.classList.toggle('hidden', category !== 'general');
            }
            if (purchaseSection) {
                purchaseSection.classList.toggle('hidden', category !== 'purchase_payment');
            }
            if (transferSection) {
                transferSection.classList.toggle('hidden', category !== 'book_transfer');
            }

            if (typeEl) {
                if (category === 'general') {
                    if (!typeEl.value) {
                        typeEl.value = '{{ old('type') }}';
                    }
                    typeEl.removeAttribute('disabled');
                } else {
                    typeEl.value = 'out';
                    typeEl.setAttribute('disabled', 'disabled');
                }
            }

            syncAllCoaByType();
        }

        function syncPurchaseRemainingInfo() {
            if (!purchaseSelectEl || !purchaseRemainingInfoEl) {
                return;
            }

            const selectedOption = purchaseSelectEl.options[purchaseSelectEl.selectedIndex];
            const remaining = selectedOption ? Number(selectedOption.getAttribute('data-remaining')) : NaN;

            if (!selectedOption || !selectedOption.value || Number.isNaN(remaining)) {
                purchaseRemainingInfoEl.textContent = '';
                return;
            }

            purchaseRemainingInfoEl.textContent = 'Sisa hutang purchase: Rp ' + remaining.toLocaleString('id-ID');

            if (purchaseAmountEl && !purchaseAmountEl.value) {
                purchaseAmountEl.value = remaining;
            }
        }

        function bindRow(row) {
            const removeBtn = row.querySelector('[data-action="remove-row"]');
            if (removeBtn) {
                removeBtn.addEventListener('click', function () {
                    const rows = rowsEl.querySelectorAll('tr');
                    if (rows.length <= 1) {
                        row.querySelectorAll('input').forEach((input) => input.value = '');
                        const coa = row.querySelector('[data-role="coa"]');
                        fillCoaOptions(coa, typeEl.value, '');
                        return;
                    }
                    row.remove();
                    refreshRowNumbers();
                });
            }

            const coa = row.querySelector('[data-role="coa"]');
            const selected = coa ? coa.getAttribute('data-selected') : '';
            fillCoaOptions(coa, typeEl.value, selected);
            if (coa) {
                coa.removeAttribute('data-selected');
            }
        }

        function buildRow(index) {
            const wrapper = document.createElement('tbody');
            wrapper.innerHTML = template.replace(/__INDEX__/g, index);
            return wrapper.firstElementChild;
        }

        rowsEl.querySelectorAll('tr[data-index]').forEach((row) => {
            const idx = parseInt(row.getAttribute('data-index'), 10);
            if (!Number.isNaN(idx)) {
                nextIndex = Math.max(nextIndex, idx + 1);
            }
            bindRow(row);
        });
        refreshRowNumbers();
        syncCategoryUI();
        syncPurchaseRemainingInfo();

        if (typeEl) {
            typeEl.addEventListener('change', function () {
                syncAllCoaByType();
            });
        }
        if (categoryEl) {
            categoryEl.addEventListener('change', syncCategoryUI);
        }
        if (purchaseSelectEl) {
            purchaseSelectEl.addEventListener('change', syncPurchaseRemainingInfo);
        }

        const formEl = rowsEl ? rowsEl.closest('form') : null;
        const typeHiddenEl = document.getElementById('type_hidden');
        if (formEl && typeHiddenEl && typeEl) {
            formEl.addEventListener('submit', function () {
                typeHiddenEl.value = typeEl.value || '';
            });
        }

        if (addBtn) {
            addBtn.addEventListener('click', function () {
                const newRow = buildRow(nextIndex++);
                rowsEl.appendChild(newRow);
                bindRow(newRow);
                refreshRowNumbers();
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                rowsEl.innerHTML = '';
                const newRow = buildRow(nextIndex++);
                rowsEl.appendChild(newRow);
                bindRow(newRow);
                refreshRowNumbers();
            });
        }
    });
</script>
@endpush
