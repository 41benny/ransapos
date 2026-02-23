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

                        <div>
                            <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">
                                Jumlah (Rp) <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-2 text-gray-500">Rp</span>
                                <input type="text" id="amount" name="amount"
                                    value="{{ old('amount', $cashTransaction->amount) }}"
                                    inputmode="decimal"
                                    data-currency-input="1"
                                    class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('amount') border-red-500 @enderror"
                                    required>
                            </div>
                            @error('amount')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="coa_account_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Akun Lawan (COA)
                            </label>
                            <select id="coa_account_id" name="coa_account_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('coa_account_id') border-red-500 @enderror">
                                <option value="">-- Pilih Akun COA --</option>
                                @foreach($coaAccounts as $coa)
                                    <option value="{{ $coa->id }}" {{ old('coa_account_id', $cashTransaction->coa_account_id) == $coa->id ? 'selected' : '' }}>
                                        {{ $coa->code }} - {{ $coa->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('coa_account_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                Deskripsi <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="description" name="description"
                                value="{{ old('description', $cashTransaction->description) }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('description') border-red-500 @enderror"
                                required>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Catatan Tambahan</label>
                        <textarea id="notes" name="notes" rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('notes') border-red-500 @enderror"
                            placeholder="Opsional">{{ old('notes', $cashTransaction->notes) }}</textarea>
                        @error('notes')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
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
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('form[action*="cash-transactions"]');
            const amountInput = document.getElementById('amount');
            if (!form || !amountInput) {
                return;
            }

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

            function renderCurrency() {
                const raw = String(amountInput.value ?? '').trim();
                if (!raw) {
                    return;
                }

                amountInput.value = formatCurrencyInput(parseCurrencyInput(raw));
            }

            amountInput.addEventListener('input', renderCurrency);
            amountInput.addEventListener('blur', renderCurrency);
            renderCurrency();

            form.addEventListener('submit', function () {
                const raw = String(amountInput.value ?? '').trim();
                amountInput.value = raw ? String(parseCurrencyInput(raw)) : '';
            });
        });
    </script>
@endsection
