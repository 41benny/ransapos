@extends('layouts.admin')

@section('title', 'Detail Akun - ' . $cashAccount->name)

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center space-x-2 text-sm text-gray-600 mb-2">
            <a href="{{ route('admin.cash-accounts.index') }}" class="hover:text-indigo-600">Kas & Bank</a>
            <span>/</span>
            <span class="text-gray-900">{{ $cashAccount->code }}</span>
        </div>
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $cashAccount->name }}</h1>
                <p class="text-gray-600 mt-1">{{ $cashAccount->code }}</p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('admin.cash-accounts.mutation-report', $cashAccount) }}" 
                   class="px-4 py-2 bg-white border border-amber-300 text-amber-900 rounded-lg hover:bg-amber-50">
                    Lihat Laporan Mutasi
                </a>
                <a href="{{ route('admin.cash-accounts.edit', $cashAccount) }}" 
                   class="px-4 py-2 bg-gradient-to-r from-emerald-500 via-teal-500 to-sky-500 text-white rounded-lg shadow-md hover:shadow-lg">
                    Edit Akun
                </a>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    <!-- Account Info Card -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Informasi Akun -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Informasi Akun</h2>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-600">Jenis Akun:</dt>
                    <dd>
                        @if($cashAccount->type === 'cash')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                Kas Tunai
                            </span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                Bank
                            </span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-600">Status:</dt>
                    <dd>
                        @if($cashAccount->is_active)
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                Aktif
                            </span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                Nonaktif
                            </span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between border-t pt-3">
                    <dt class="text-sm text-gray-600">Dibuat oleh:</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $cashAccount->creator->name ?? '-' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-600">Dibuat pada:</dt>
                    <dd class="text-sm text-gray-900">{{ $cashAccount->created_at->format('d M Y H:i') }}</dd>
                </div>
                @if($cashAccount->notes)
                <div class="border-t pt-3">
                    <dt class="text-sm text-gray-600 mb-1">Catatan:</dt>
                    <dd class="text-sm text-gray-900">{{ $cashAccount->notes }}</dd>
                </div>
                @endif
            </dl>
        </div>

        <!-- Saldo -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Saldo</h2>
            <div class="space-y-4">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Saldo Awal</p>
                    <p class="text-2xl font-bold text-gray-900">Rp {{ number_format($cashAccount->opening_balance, 0, ',', '.') }}</p>
                </div>
                <div class="border-t pt-4">
                    <p class="text-sm text-gray-600 mb-1">Saldo Saat Ini</p>
                    <p class="text-3xl font-bold text-indigo-600">Rp {{ number_format($cashAccount->current_balance, 0, ',', '.') }}</p>
                </div>
                <div class="grid grid-cols-2 gap-4 border-t pt-4">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Total Masuk</p>
                        <p class="text-lg font-semibold text-green-600">Rp {{ number_format($totalIn, 0, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Total Keluar</p>
                        <p class="text-lg font-semibold text-red-600">Rp {{ number_format($totalOut, 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-900">Transaksi Terakhir</h2>
            <a href="{{ route('admin.cash-transactions.index', ['cash_account_id' => $cashAccount->id]) }}" 
               class="text-indigo-600 hover:text-indigo-700 text-sm font-medium">
                Lihat Semua →
            </a>
        </div>

        @if($cashAccount->transactions && $cashAccount->transactions->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nomor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Deskripsi</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jenis</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Jumlah</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Saldo Akhir</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($cashAccount->transactions as $transaction)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $transaction->transaction_number }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $transaction->transaction_date->format('d M Y') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <div>{{ $transaction->description }}</div>
                                    @if($transaction->reference_type)
                                        <div class="text-xs text-gray-500">Ref: {{ ucfirst($transaction->reference_type) }} #{{ $transaction->reference_id }}</div>
                                    @endif
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
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada transaksi</h3>
                <p class="mt-1 text-sm text-gray-500">Transaksi akan muncul di sini setelah dicatat.</p>
            </div>
        @endif
    </div>
</div>
@endsection

