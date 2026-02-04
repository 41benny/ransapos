@extends('layouts.admin')

@section('title', 'Chart of Accounts (COA)')

@section('content')
    <div class="page-fullwidth px-0">
        <div class="px-6 py-6 page-card-fill">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Chart of Accounts (COA)</h1>
                    <p class="text-gray-600 mt-1">Kelola akun pendapatan dan biaya</p>
                </div>
                <a href="{{ route('admin.coa-accounts.create') }}"
                    class="bg-gradient-to-r from-emerald-500 via-teal-500 to-sky-500 text-white px-4 py-2 rounded-lg flex items-center space-x-2 shadow-md hover:shadow-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Tambah Akun Baru</span>
                </a>
            </div>

            <!-- Filter -->
            <div class="bg-white rounded-lg shadow p-4 mb-6">
                <form method="GET">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                            <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                <option value="">Semua Type</option>
                                <option value="income" {{ request('type') == 'income' ? 'selected' : '' }}>Income (Pendapatan)
                                </option>
                                <option value="expense" {{ request('type') == 'expense' ? 'selected' : '' }}>Expense (Biaya)
                                </option>
                                <option value="asset" {{ request('type') == 'asset' ? 'selected' : '' }}>Asset</option>
                                <option value="liability" {{ request('type') == 'liability' ? 'selected' : '' }}>Liability
                                </option>
                                <option value="equity" {{ request('type') == 'equity' ? 'selected' : '' }}>Equity</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Group</label>
                            <select name="group" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                <option value="">Semua Group</option>
                                @foreach($groups as $group)
                                    <option value="{{ $group }}" {{ request('group') == $group ? 'selected' : '' }}>
                                        {{ $group }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="is_active" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                <option value="">Semua</option>
                                <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
                                <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Nonaktif</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button type="submit"
                                class="w-full px-4 py-2 bg-gradient-to-r from-emerald-500 via-teal-500 to-sky-500 text-white rounded-lg shadow-md hover:shadow-lg">
                                Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Alert Messages -->
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                    {{ session('error') }}
                </div>
            @endif

            <!-- COA List -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                @if($coaAccounts->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="imperial-table min-w-full">
                            <thead class="">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kode</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Akun</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Group</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="">
                                @foreach($coaAccounts as $coa)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-sm font-medium text-gray-900">{{ $coa->code }}</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900">{{ $coa->name }}</div>
                                            @if($coa->notes)
                                                <div class="text-xs text-gray-500">{{ Str::limit($coa->notes, 50) }}</div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($coa->type === 'income')
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                    Income
                                                </span>
                                            @elseif($coa->type === 'expense')
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                                    Expense
                                                </span>
                                            @else
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                                    {{ ucfirst($coa->type) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $coa->group }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($coa->is_active)
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                    Aktif
                                                </span>
                                            @else
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                                    Nonaktif
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <a href="{{ route('admin.coa-accounts.edit', $coa) }}"
                                                    class="text-amber-700 hover:text-amber-900">Edit</a>
                                                @if($coa->cashTransactions->count() == 0)
                                                    <form action="{{ route('admin.coa-accounts.destroy', $coa) }}" method="POST"
                                                        onsubmit="return confirm('Yakin hapus akun ini?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $coaAccounts->links() }}
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada akun COA</h3>
                        <p class="mt-1 text-sm text-gray-500">Mulai dengan menambah akun baru atau ubah filter.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection