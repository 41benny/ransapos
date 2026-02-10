@extends('layouts.admin')

@section('title', 'Transaksi Kas & Bank')

@section('content')
    <div class="container mx-auto px-4 py-6">
        <!-- Header -->
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

        <!-- Filter -->
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <form method="GET" action="{{ route('admin.cash-transactions.index') }}">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <!-- Akun -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Akun</label>
                        <select name="cash_account_id"
                            class="w-full h-9 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-primary focus:border-primary">
                            <option value="">Semua Akun</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}" {{ request('cash_account_id') == $account->id ? 'selected' : '' }}>
                                    {{ $account->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Jenis -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jenis</label>
                        <select name="type" class="w-full h-9 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-primary focus:border-primary">
                            <option value="">Semua</option>
                            <option value="in" {{ request('type') == 'in' ? 'selected' : '' }}>Masuk</option>
                            <option value="out" {{ request('type') == 'out' ? 'selected' : '' }}>Keluar</option>
                        </select>
                    </div>

                    <!-- Dari Tanggal -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>

                    <!-- Sampai Tanggal -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>

                    <!-- Button -->
                    <div class="flex items-end">
                        <button type="submit" class="imperial-btn w-full">
                            Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Alert Messages -->
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        <!-- Transactions List -->
        <div class="t6-card shadow overflow-hidden">
            @if($transactions->count() > 0)
                <div class="overflow-x-auto">
                    <table class="imperial-table min-w-full">
                        <thead class="">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nomor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Akun</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Deskripsi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jenis</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Jumlah</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Saldo</th>
                            </tr>
                        </thead>
                        <tbody class="">
                            @foreach($transactions as $transaction)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $transaction->transaction_number }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $transaction->transaction_date->format('d M Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $transaction->cashAccount->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $transaction->cashAccount->code }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <div>{{ $transaction->description }}</div>
                                        @if($transaction->reference_type)
                                            <div class="text-xs text-gray-500 mt-1">Ref: {{ ucfirst($transaction->reference_type) }}
                                                #{{ $transaction->reference_id }}</div>
                                        @endif
                                        @if($transaction->notes)
                                            <div class="text-xs text-gray-500 mt-1">{{ Str::limit($transaction->notes, 50) }}</div>
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

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $transactions->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada transaksi</h3>
                    <p class="mt-1 text-sm text-gray-500">Belum ada transaksi yang dicatat atau coba ubah filter.</p>
                </div>
            @endif
        </div>
    </div>
@endsection