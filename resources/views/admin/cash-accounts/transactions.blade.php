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
                <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                    <!-- Outlet -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Outlet</label>
                        <select name="outlet_id"
                            class="w-full h-9 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-primary focus:border-primary">
                            <option value="">Semua Outlet</option>
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->id }}" {{ request('outlet_id') == $outlet->id ? 'selected' : '' }}>
                                    {{ $outlet->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

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

                <div class="mt-4 grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">COA Akun</label>
                        <select name="coa_account_id"
                            class="w-full h-9 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-primary focus:border-primary">
                            <option value="">Semua COA</option>
                            @foreach($coaAccounts as $coaAccount)
                                <option value="{{ $coaAccount->id }}" {{ request('coa_account_id') == $coaAccount->id ? 'selected' : '' }}>
                                    {{ $coaAccount->code }} - {{ $coaAccount->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipe COA</label>
                        <select name="coa_type"
                            class="w-full h-9 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-primary focus:border-primary">
                            <option value="">Semua Tipe</option>
                            <option value="income" {{ request('coa_type') === 'income' ? 'selected' : '' }}>Income</option>
                            <option value="expense" {{ request('coa_type') === 'expense' ? 'selected' : '' }}>Expense</option>
                            <option value="asset" {{ request('coa_type') === 'asset' ? 'selected' : '' }}>Asset</option>
                            <option value="liability" {{ request('coa_type') === 'liability' ? 'selected' : '' }}>Liability</option>
                            <option value="equity" {{ request('coa_type') === 'equity' ? 'selected' : '' }}>Equity</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Grup COA</label>
                        <select name="coa_group"
                            class="w-full h-9 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-primary focus:border-primary">
                            <option value="">Semua Grup</option>
                            @foreach($coaGroups as $coaGroup)
                                <option value="{{ $coaGroup }}" {{ request('coa_group') == $coaGroup ? 'selected' : '' }}>
                                    {{ $coaGroup }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Exclude Grup COA</label>
                        <input type="text" name="exclude_coa_group" value="{{ request('exclude_coa_group') }}"
                            placeholder="Contoh: HPP"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>

                    <div class="flex items-end">
                        <a href="{{ route('admin.cash-transactions.index') }}"
                            class="w-full text-center bg-gray-100 border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">
                            Reset
                        </a>
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
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="">
                            @foreach($transactions as $transaction)
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
                                        <div>{{ $transaction->description }}</div>
                                        @if($transaction->coaAccount)
                                            <div class="text-xs text-indigo-700 mt-1">
                                                COA: {{ $transaction->coaAccount->code }} - {{ $transaction->coaAccount->name }}
                                                ({{ $transaction->coaAccount->group }})
                                            </div>
                                        @endif
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
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <div class="flex items-center justify-center space-x-2">
                                            <a href="{{ route('admin.cash-transactions.show', $transaction) }}" 
                                               class="text-indigo-600 hover:text-indigo-900" 
                                               title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.cash-transactions.print', $transaction) }}" 
                                               target="_blank"
                                               class="text-gray-600 hover:text-gray-900" 
                                               title="Cetak Voucher">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            <a href="{{ route('admin.cash-transactions.edit', $transaction) }}" 
                                               class="text-amber-600 hover:text-amber-900" 
                                               title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('admin.cash-transactions.destroy', $transaction) }}" 
                                                  method="POST" 
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
