@extends('layouts.admin')

@section('title', 'Pembayaran Purchase - ' . $purchase->purchase_number)

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center space-x-2 text-sm text-gray-600 mb-2">
            <a href="{{ route('admin.purchases.index') }}" class="hover:text-indigo-600">Pembelian</a>
            <span>/</span>
            <a href="{{ route('admin.purchases.show', $purchase) }}" class="hover:text-indigo-600">{{ $purchase->purchase_number }}</a>
            <span>/</span>
            <span class="text-gray-900">Pembayaran</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">Catat Pembayaran Purchase</h1>
        <p class="text-gray-600 mt-1">{{ $purchase->purchase_number }}</p>
    </div>

    <!-- Alert Messages -->
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <!-- Purchase Info -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <p class="text-sm text-gray-600 mb-1">Total Purchase</p>
                <p class="text-2xl font-bold text-gray-900">Rp {{ number_format($purchase->total_amount, 0, ',', '.') }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">Sudah Dibayar</p>
                <p class="text-2xl font-bold text-green-600">Rp {{ number_format($totalPaid, 0, ',', '.') }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">Sisa Tagihan</p>
                <p class="text-2xl font-bold text-red-600">Rp {{ number_format($remaining, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>

    <!-- Payment History -->
    @if($purchase->cashTransactions && $purchase->cashTransactions->count() > 0)
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Riwayat Pembayaran</h2>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    @foreach($purchase->cashTransactions as $payment)
                        <div class="flex justify-between items-center py-3 border-b border-gray-100 last:border-0">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $payment->transaction_number }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ $payment->transaction_date->format('d M Y') }} - 
                                    {{ $payment->cashAccount->name }}
                                </p>
                            </div>
                            <p class="text-sm font-semibold text-gray-900">
                                Rp {{ number_format($payment->amount, 0, ',', '.') }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Payment Form -->
    @if($remaining > 0)
        <div class="bg-white rounded-lg shadow max-w-2xl">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Catat Pembayaran Baru</h2>
            </div>

            <form action="{{ route('admin.purchases.payment.store', $purchase) }}" method="POST">
                @csrf

                <div class="p-6 space-y-6">
                    <!-- Akun Kas/Bank -->
                    <div>
                        <label for="cash_account_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Bayar Dari Akun <span class="text-red-500">*</span>
                        </label>
                        <select id="cash_account_id" 
                                name="cash_account_id" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('cash_account_id') border-red-500 @enderror"
                                required>
                            <option value="">-- Pilih Akun --</option>
                            @foreach($cashAccounts as $account)
                                <option value="{{ $account->id }}" {{ old('cash_account_id') == $account->id ? 'selected' : '' }}>
                                    {{ $account->name }} - Saldo: Rp {{ number_format($account->current_balance, 0, ',', '.') }}
                                </option>
                            @endforeach
                        </select>
                        @error('cash_account_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Jumlah Pembayaran -->
                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">
                            Jumlah Pembayaran <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-gray-500">Rp</span>
                            <input type="number" 
                                   id="amount" 
                                   name="amount" 
                                   value="{{ old('amount', $remaining) }}"
                                   step="0.01"
                                   min="0.01"
                                   max="{{ $remaining }}"
                                   class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('amount') border-red-500 @enderror"
                                   required>
                        </div>
                        @error('amount')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-gray-500">Maksimal: Rp {{ number_format($remaining, 0, ',', '.') }}</p>
                    </div>

                    <!-- Tanggal Pembayaran -->
                    <div>
                        <label for="transaction_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Tanggal Pembayaran <span class="text-red-500">*</span>
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

                    <!-- Catatan -->
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                            Catatan
                        </label>
                        <textarea id="notes" 
                                  name="notes" 
                                  rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('notes') border-red-500 @enderror"
                                  placeholder="Catatan tambahan (opsional)">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3 rounded-b-lg">
                    <a href="{{ route('admin.purchases.show', $purchase) }}" 
                       class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Batal
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg">
                        Catat Pembayaran
                    </button>
                </div>
            </form>
        </div>
    @else
        <div class="bg-green-50 border border-green-200 rounded-lg p-6">
            <div class="flex">
                <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-green-800">Purchase Sudah Lunas</h3>
                    <p class="mt-2 text-sm text-green-700">
                        Purchase ini sudah dibayar penuh. Tidak ada sisa tagihan.
                    </p>
                    <div class="mt-4">
                        <a href="{{ route('admin.purchases.show', $purchase) }}" 
                           class="text-sm font-medium text-green-800 hover:text-green-700">
                            Kembali ke Detail Purchase →
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

