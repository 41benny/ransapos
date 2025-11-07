@extends('layouts.admin')

@section('title', 'Edit Akun Kas/Bank')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center space-x-2 text-sm text-gray-600 mb-2">
            <a href="{{ route('admin.cash-accounts.index') }}" class="hover:text-indigo-600">Kas & Bank</a>
            <span>/</span>
            <a href="{{ route('admin.cash-accounts.show', $cashAccount) }}" class="hover:text-indigo-600">{{ $cashAccount->code }}</a>
            <span>/</span>
            <span class="text-gray-900">Edit</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">Edit Akun: {{ $cashAccount->name }}</h1>
    </div>

    <!-- Alert Messages -->
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <!-- Form -->
    <div class="bg-white rounded-lg shadow">
        <form action="{{ route('admin.cash-accounts.update', $cashAccount) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="p-6 space-y-6">
                <!-- Info: Saldo tidak bisa diubah -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex">
                        <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                Saldo awal dan saldo saat ini tidak bisa diubah melalui form edit. 
                                Gunakan transaksi untuk mengubah saldo.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Nama Akun -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Nama Akun <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           value="{{ old('name', $cashAccount->name) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('name') border-red-500 @enderror"
                           required>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Kode Akun -->
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-2">
                        Kode Akun <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="code" 
                           name="code" 
                           value="{{ old('code', $cashAccount->code) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('code') border-red-500 @enderror"
                           required>
                    @error('code')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Jenis Akun -->
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
                        Jenis Akun <span class="text-red-500">*</span>
                    </label>
                    <select id="type" 
                            name="type" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('type') border-red-500 @enderror"
                            required>
                        <option value="cash" {{ old('type', $cashAccount->type) == 'cash' ? 'selected' : '' }}>Kas Tunai</option>
                        <option value="bank" {{ old('type', $cashAccount->type) == 'bank' ? 'selected' : '' }}>Bank</option>
                    </select>
                    @error('type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Saldo (Read-only) -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Saldo Awal</label>
                        <div class="px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg text-gray-700">
                            Rp {{ number_format($cashAccount->opening_balance, 0, ',', '.') }}
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Saldo Saat Ini</label>
                        <div class="px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg text-indigo-600 font-semibold">
                            Rp {{ number_format($cashAccount->current_balance, 0, ',', '.') }}
                        </div>
                    </div>
                </div>

                <!-- Status Aktif -->
                <div>
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" 
                               id="is_active" 
                               name="is_active" 
                               value="1"
                               {{ old('is_active', $cashAccount->is_active) ? 'checked' : '' }}
                               class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                        <span class="text-sm font-medium text-gray-700">Akun Aktif</span>
                    </label>
                    <p class="mt-1 text-sm text-gray-500 ml-6">Centang jika akun ini aktif dan bisa digunakan</p>
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
                              placeholder="Catatan tambahan">{{ old('notes', $cashAccount->notes) }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-between rounded-b-lg">
                <div>
                    @if($cashAccount->transactions()->count() == 0)
                        <form action="{{ route('admin.cash-accounts.destroy', $cashAccount) }}" 
                              method="POST" 
                              onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg">
                                Hapus Akun
                            </button>
                        </form>
                    @endif
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('admin.cash-accounts.show', $cashAccount) }}" 
                       class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Batal
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg">
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

