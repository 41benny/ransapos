@extends('layouts.admin')

@section('title', 'Tambah Akun COA')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center space-x-2 text-sm text-gray-600 mb-2">
            <a href="{{ route('admin.coa-accounts.index') }}" class="hover:text-indigo-600">Chart of Accounts</a>
            <span>/</span>
            <span class="text-gray-900">Tambah Baru</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">Tambah Akun COA Baru</h1>
    </div>

    <!-- Alert Messages -->
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
            {{ session('error') }}
        </div>
    @endif

    <!-- Form -->
    <div class="bg-white rounded-lg shadow max-w-2xl">
        <form action="{{ route('admin.coa-accounts.store') }}" method="POST">
            @csrf

            <div class="p-6 space-y-6">
                <!-- Kode -->
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-2">
                        Kode Akun <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="code" 
                           name="code" 
                           value="{{ old('code') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('code') border-red-500 @enderror"
                           placeholder="Contoh: 6-135"
                           required>
                    @error('code')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-500">Format: X-YYY (4=Income, 5=HPP, 6=Expense)</p>
                </div>

                <!-- Nama -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Nama Akun <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           value="{{ old('name') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('name') border-red-500 @enderror"
                           placeholder="Contoh: Biaya Konsumsi"
                           required>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Type -->
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
                        Type <span class="text-red-500">*</span>
                    </label>
                    <select id="type" 
                            name="type" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('type') border-red-500 @enderror"
                            required>
                        <option value="">-- Pilih Type --</option>
                        <option value="income" {{ old('type') == 'income' ? 'selected' : '' }}>Income (Pendapatan)</option>
                        <option value="expense" {{ old('type') == 'expense' ? 'selected' : '' }}>Expense (Biaya)</option>
                        <option value="asset" {{ old('type') == 'asset' ? 'selected' : '' }}>Asset (Aset)</option>
                        <option value="liability" {{ old('type') == 'liability' ? 'selected' : '' }}>Liability (Kewajiban)</option>
                        <option value="equity" {{ old('type') == 'equity' ? 'selected' : '' }}>Equity (Modal)</option>
                    </select>
                    @error('type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Group -->
                <div>
                    <label for="group" class="block text-sm font-medium text-gray-700 mb-2">
                        Group <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="group" 
                           name="group" 
                           value="{{ old('group') }}"
                           list="group-suggestions"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('group') border-red-500 @enderror"
                           placeholder="Contoh: BIAYA OPERASIONAL"
                           required>
                    <datalist id="group-suggestions">
                        <option value="PENDAPATAN">
                        <option value="HPP">
                        <option value="BIAYA OPERASIONAL">
                        <option value="BIAYA MARKETING">
                        <option value="BIAYA ADMINISTRASI">
                    </datalist>
                    @error('group')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-500">Group untuk pengelompokan di laporan</p>
                </div>

                <!-- Status Aktif -->
                <div>
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" 
                               id="is_active" 
                               name="is_active" 
                               value="1"
                               {{ old('is_active', true) ? 'checked' : '' }}
                               class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                        <span class="text-sm font-medium text-gray-700">Akun Aktif</span>
                    </label>
                    <p class="mt-1 text-sm text-gray-500 ml-6">Centang jika akun ini aktif</p>
                </div>

                <!-- Catatan -->
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                        Catatan
                    </label>
                    <textarea id="notes" 
                              name="notes" 
                              rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                              placeholder="Catatan tambahan (opsional)">{{ old('notes') }}</textarea>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3 rounded-b-lg">
                <a href="{{ route('admin.coa-accounts.index') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Batal
                </a>
                <button type="submit" 
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg">
                    Simpan Akun
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

