@extends('layouts.admin')

@section('title', 'Chart of Accounts (COA)')
@section('page-title', 'Chart of Accounts')

@section('content')
    <div class="w-full">
        <!-- Filter -->
        <div class="card bg-white p-4 mb-6">
            <form method="GET">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="form-label">Type</label>
                        <select name="type" class="form-input">
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
                        <label class="form-label">Group</label>
                        <select name="group" class="form-input">
                            <option value="">Semua Group</option>
                            @foreach($groups as $group)
                                <option value="{{ $group }}" {{ request('group') == $group ? 'selected' : '' }}>
                                    {{ $group }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Status</label>
                        <select name="is_active" class="form-input">
                            <option value="">Semua</option>
                            <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
                            <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Nonaktif</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="btn btn-primary w-full justify-center">
                            Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Alert Messages -->
        @if(session('success'))
            <div class="mb-4 bg-green-50 text-green-700 p-4 rounded-lg border border-green-200">
                <i class="fas fa-check-circle mr-2"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 bg-red-50 text-red-700 p-4 rounded-lg border border-red-200">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- COA List -->
        <div class="card bg-white p-6">

            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Daftar Akun</h2>
                    <p class="text-sm text-gray-500">Kelola akun pendapatan dan biaya</p>
                </div>
                <a href="{{ route('admin.coa-accounts.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    <span>Tambah Akun Baru</span>
                </a>
            </div>

            <div class="table-container">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama Akun</th>
                            <th>Type</th>
                            <th>Group</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($coaAccounts as $coa)
                            <tr>
                                <td class="font-mono font-medium">{{ $coa->code }}</td>
                                <td>
                                    <div class="font-medium text-gray-900">{{ $coa->name }}</div>
                                    @if($coa->notes)
                                        <div class="text-xs text-gray-500 mt-0.5 truncate max-w-xs">
                                            {{ Str::limit($coa->notes, 50) }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($coa->type === 'income')
                                        <span class="badge badge-success">Income</span>
                                    @elseif($coa->type === 'expense')
                                        <span class="badge badge-danger">Expense</span>
                                    @else
                                        <span class="badge badge-gray">{{ ucfirst($coa->type) }}</span>
                                    @endif
                                </td>
                                <td>{{ $coa->group }}</td>
                                <td>
                                    @if($coa->is_active)
                                        <span class="badge badge-success">Aktif</span>
                                    @else
                                        <span class="badge badge-gray">Nonaktif</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.coa-accounts.edit', $coa) }}"
                                            class="text-amber-600 hover:text-amber-800" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @if($coa->cashTransactions->count() == 0)
                                            <form action="{{ route('admin.coa-accounts.destroy', $coa) }}" method="POST"
                                                class="inline" onsubmit="return confirm('Yakin hapus akun ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-800" title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <i class="fas fa-book text-4xl text-gray-300 mb-3"></i>
                                        <p>Tidak ada akun COA</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($coaAccounts->hasPages())
                <div class="mt-6">
                    {{ $coaAccounts->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection