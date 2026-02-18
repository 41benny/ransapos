@extends('layouts.pos_theme')

@section('content')
@php
    $rows = old('rows');
    if (!is_array($rows) || count($rows) === 0) {
        $rows = [
            [
                'recipient_name' => '',
                'description' => '',
                'amount' => '',
            ],
        ];
    }
@endphp
<div class="max-w-5xl mx-auto">
    <div class="bg-surface-light rounded-2xl shadow-soft overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/60 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-gray-900">Input Petty Cash Outlet</h2>
                <p class="text-sm text-gray-500 mt-0.5">Kas kecil terpisah dari kas sales POS</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('pos.petty-cash.index') }}"
                   class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-sm text-gray-700 font-medium transition">
                    <span class="material-icons-round text-base">list_alt</span>
                    Riwayat
                </a>
                <a href="{{ route('pos.dashboard') }}"
                   class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-sm text-gray-700 font-medium transition">
                    <span class="material-icons-round text-base">arrow_back</span>
                    Dashboard
                </a>
            </div>
        </div>

        <div class="p-6 space-y-5">
            @if(session('success'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-800 px-4 py-3 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="rounded-lg border border-rose-200 bg-rose-50 text-rose-800 px-4 py-3 text-sm">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-lg border border-rose-200 bg-rose-50 text-rose-800 px-4 py-3 text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="rounded-xl border border-gray-200 bg-white p-4">
                    <p class="text-xs uppercase tracking-wide text-gray-500 mb-1">Akun Kas Otomatis</p>
                    @if($pettyCashAccount)
                        <p class="text-sm font-semibold text-gray-900">{{ $pettyCashAccount->name }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $pettyCashAccount->code }}</p>
                        <p class="text-xs text-amber-700 mt-2 font-medium">
                            Saldo: Rp {{ number_format($pettyCashAccount->current_balance, 0, ',', '.') }}
                        </p>
                    @else
                        <p class="text-sm font-semibold text-rose-700">Belum disetting</p>
                        <p class="text-xs text-rose-600 mt-1">Admin harus set akun kas dengan tipe penggunaan "Petty Cash Outlet".</p>
                    @endif
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-4">
                    <p class="text-xs uppercase tracking-wide text-gray-500 mb-1">Akun Expense Otomatis</p>
                    @if($defaultExpenseAccount)
                        <p class="text-sm font-semibold text-gray-900">{{ $defaultExpenseAccount->name }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $defaultExpenseAccount->code }}</p>
                    @else
                        <p class="text-sm font-semibold text-rose-700">Belum disetting</p>
                        <p class="text-xs text-rose-600 mt-1">Akun "Keperluan Outlet Lainnya" belum tersedia.</p>
                    @endif
                </div>
            </div>

            <form action="{{ route('pos.petty-cash.store') }}" method="POST" class="space-y-4" novalidate>
                @csrf

                <div>
                    <label for="transaction_date" class="block text-sm font-medium text-gray-700 mb-2">
                        Tanggal Transaksi <span class="text-rose-600">*</span>
                    </label>
                    <input type="date"
                           name="transaction_date"
                           id="transaction_date"
                           value="{{ old('transaction_date', now()->format('Y-m-d')) }}"
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                </div>

                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-primary font-semibold">Detail Pengeluaran</p>
                        <h3 class="text-base font-semibold text-gray-900">Tambah Baris Pengeluaran</h3>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" id="add-row"
                                class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-sm text-white font-semibold transition">
                            <span class="material-icons-round text-base">add</span>
                            Tambah Baris
                        </button>
                        <button type="button" id="clear-rows"
                                class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-rose-300 text-sm text-rose-700 hover:bg-rose-50 font-semibold transition">
                            <span class="material-icons-round text-base">delete_sweep</span>
                            Hapus Semua
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
                    <table class="min-w-[960px] w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold w-10">#</th>
                                <th class="px-3 py-2 text-left font-semibold min-w-[220px]">Nama Penerima</th>
                                <th class="px-3 py-2 text-left font-semibold min-w-[420px]">Deskripsi</th>
                                <th class="px-3 py-2 text-left font-semibold w-[220px]">Jumlah (Rp)</th>
                                <th class="px-3 py-2 text-right font-semibold w-16">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="petty-cash-rows" class="divide-y divide-gray-100">
                            @foreach($rows as $i => $row)
                                <tr data-index="{{ $i }}">
                                    <td class="px-3 py-2 text-gray-500" data-role="row-number">{{ $i + 1 }}</td>
                                    <td class="px-3 py-2">
                                        <input type="text"
                                               name="rows[{{ $i }}][recipient_name]"
                                               value="{{ $row['recipient_name'] ?? '' }}"
                                               maxlength="60"
                                               placeholder="Contoh: Budi / Toko Sumber Rejeki"
                                               required
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent @error('rows.'.$i.'.recipient_name') border-rose-400 @enderror">
                                        @error('rows.'.$i.'.recipient_name')
                                            <p class="mt-1 text-xs text-rose-700">{{ $message }}</p>
                                        @enderror
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="text"
                                               name="rows[{{ $i }}][description]"
                                               value="{{ $row['description'] ?? '' }}"
                                               maxlength="150"
                                               placeholder="Contoh: Pembelian tisu outlet"
                                               required
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent @error('rows.'.$i.'.description') border-rose-400 @enderror">
                                        @error('rows.'.$i.'.description')
                                            <p class="mt-1 text-xs text-rose-700">{{ $message }}</p>
                                        @enderror
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number"
                                               name="rows[{{ $i }}][amount]"
                                               value="{{ $row['amount'] ?? '' }}"
                                               min="0.01"
                                               step="0.01"
                                               placeholder="0"
                                               required
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent @error('rows.'.$i.'.amount') border-rose-400 @enderror">
                                        @error('rows.'.$i.'.amount')
                                            <p class="mt-1 text-xs text-rose-700">{{ $message }}</p>
                                        @enderror
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <button type="button"
                                                data-action="remove-row"
                                                title="Hapus baris"
                                                class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-rose-700 hover:bg-rose-50 transition">
                                            <span class="material-icons-round text-base">delete</span>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end">
                    <div class="w-full max-w-sm rounded-xl border border-amber-100 bg-amber-50/40 px-4 py-3">
                        <div class="flex items-center justify-between text-sm text-gray-600">
                            <span>Total Baris</span>
                            <span id="rows-count-summary" class="font-semibold text-gray-900">{{ count($rows) }} baris</span>
                        </div>
                        <div class="mt-1 flex items-center justify-between">
                            <span class="text-sm text-gray-700 font-medium">Total Pengeluaran</span>
                            <span id="rows-total-amount" class="text-lg font-bold text-rose-700">Rp 0</span>
                        </div>
                    </div>
                </div>

                <div class="pt-2 flex items-center justify-end gap-3">
                    <a href="{{ route('pos.petty-cash.index') }}"
                       class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-medium">
                        Batal
                    </a>
                    <button type="submit"
                            {{ (!$pettyCashAccount || !$defaultExpenseAccount) ? 'disabled' : '' }}
                            class="px-4 py-2 rounded-lg bg-primary hover:bg-red-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-sm font-semibold">
                        Simpan Pengeluaran
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<template id="petty-row-template">
    <tr data-index="__INDEX__">
        <td class="px-3 py-2 text-gray-500" data-role="row-number"></td>
        <td class="px-3 py-2">
            <input type="text"
                   name="rows[__INDEX__][recipient_name]"
                   maxlength="60"
                   placeholder="Contoh: Budi / Toko Sumber Rejeki"
                   required
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
        </td>
        <td class="px-3 py-2">
            <input type="text"
                   name="rows[__INDEX__][description]"
                   maxlength="150"
                   placeholder="Contoh: Pembelian tisu outlet"
                   required
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
        </td>
        <td class="px-3 py-2">
            <input type="number"
                   name="rows[__INDEX__][amount]"
                   min="0.01"
                   step="0.01"
                   placeholder="0"
                   required
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
        </td>
        <td class="px-3 py-2 text-right">
            <button type="button"
                    data-action="remove-row"
                    title="Hapus baris"
                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-rose-700 hover:bg-rose-50 transition">
                <span class="material-icons-round text-base">delete</span>
            </button>
        </td>
    </tr>
</template>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const rowsEl = document.getElementById('petty-cash-rows');
        const addBtn = document.getElementById('add-row');
        const clearBtn = document.getElementById('clear-rows');
        const templateEl = document.getElementById('petty-row-template');
        const rowsCountSummaryEl = document.getElementById('rows-count-summary');
        const rowsTotalAmountEl = document.getElementById('rows-total-amount');

        if (!rowsEl || !addBtn || !clearBtn || !templateEl) {
            return;
        }

        const template = templateEl.innerHTML.trim();
        let nextIndex = 0;

        function formatCurrency(amount) {
            return 'Rp ' + amount.toLocaleString('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2,
            });
        }

        function refreshRowNumbers() {
            rowsEl.querySelectorAll('tr').forEach((row, idx) => {
                const numberEl = row.querySelector('[data-role="row-number"]');
                if (numberEl) {
                    numberEl.textContent = idx + 1;
                }
            });
        }

        function refreshSummary() {
            const rows = rowsEl.querySelectorAll('tr');
            const total = Array.from(rows).reduce((carry, row) => {
                const amountInput = row.querySelector('input[name$="[amount]"]');
                if (!amountInput) {
                    return carry;
                }

                const amount = parseFloat(amountInput.value);
                if (Number.isNaN(amount) || amount <= 0) {
                    return carry;
                }

                return carry + amount;
            }, 0);

            if (rowsCountSummaryEl) {
                rowsCountSummaryEl.textContent = rows.length + ' baris';
            }
            if (rowsTotalAmountEl) {
                rowsTotalAmountEl.textContent = formatCurrency(total);
            }
        }

        function bindRow(row) {
            const removeBtn = row.querySelector('[data-action="remove-row"]');
            if (removeBtn) {
                removeBtn.addEventListener('click', function () {
                    const rows = rowsEl.querySelectorAll('tr');
                    if (rows.length <= 1) {
                        row.querySelectorAll('input').forEach((input) => {
                            input.value = '';
                        });
                        refreshSummary();
                        return;
                    }

                    row.remove();
                    refreshRowNumbers();
                    refreshSummary();
                });
            }

            const amountInput = row.querySelector('input[name$="[amount]"]');
            if (amountInput) {
                amountInput.addEventListener('input', refreshSummary);
                amountInput.addEventListener('change', refreshSummary);
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
        refreshSummary();

        addBtn.addEventListener('click', function () {
            const newRow = buildRow(nextIndex++);
            rowsEl.appendChild(newRow);
            bindRow(newRow);
            refreshRowNumbers();
            refreshSummary();
        });

        clearBtn.addEventListener('click', function () {
            rowsEl.innerHTML = '';
            const newRow = buildRow(nextIndex++);
            rowsEl.appendChild(newRow);
            bindRow(newRow);
            refreshRowNumbers();
            refreshSummary();
        });
    });
</script>
@endsection
