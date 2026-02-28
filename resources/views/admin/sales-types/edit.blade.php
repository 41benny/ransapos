@extends('layouts.admin')

@section('title', 'Edit Metode Penjualan')
@section('page-title', 'Edit Metode Penjualan')
@section('page-subtitle', 'Perbarui data metode penjualan')

@section('content')
    <div class="max-w-4xl">
        <form action="{{ route('admin.sales-types.update', $salesType) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900">Informasi Metode Penjualan</h3>
                    <p class="text-sm text-gray-500 mt-1">Perbarui data metode penjualan sesuai kebutuhan operasional</p>
                </div>

                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="code" class="block text-sm font-medium text-gray-700 mb-2">
                                Kode <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                name="code"
                                id="code"
                                value="{{ old('code', $salesType->code) }}"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('code') border-red-500 @enderror {{ strtoupper($salesType->code) === 'REGULAR' ? 'bg-gray-100 text-gray-500' : '' }}"
                                placeholder="Contoh: TOKOPEDIA atau ONLINE_ORDER"
                                required
                                {{ strtoupper($salesType->code) === 'REGULAR' ? 'readonly' : '' }}
                            >
                            @if(strtoupper($salesType->code) === 'REGULAR')
                                <p class="mt-1 text-xs text-amber-700">Kode REGULAR dikunci karena dipakai sebagai acuan sistem.</p>
                            @else
                                <p class="mt-1 text-xs text-gray-500">Akan dinormalisasi otomatis ke format huruf besar + underscore.</p>
                            @endif
                            @error('code')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                Nama Tampilan <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                name="name"
                                id="name"
                                value="{{ old('name', $salesType->name) }}"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('name') border-red-500 @enderror"
                                placeholder="Contoh: Tokopedia"
                                required
                            >
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-2">
                            Urutan Tampil
                        </label>
                        <input
                            type="number"
                            name="sort_order"
                            id="sort_order"
                            value="{{ old('sort_order', $salesType->sort_order) }}"
                            min="0"
                            class="w-32 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('sort_order') border-red-500 @enderror"
                        >
                        <p class="mt-1 text-xs text-gray-500">Semakin kecil angka, semakin atas posisinya di daftar.</p>
                        @error('sort_order')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        @if(strtoupper($salesType->code) === 'REGULAR')
                            <input type="hidden" name="is_active" value="1">
                            <label class="flex items-center cursor-not-allowed">
                                <input
                                    type="checkbox"
                                    checked
                                    disabled
                                    class="w-5 h-5 text-indigo-600 border-gray-300 rounded"
                                >
                                <span class="ml-3 text-sm text-gray-700">Aktif (wajib untuk REGULAR)</span>
                            </label>
                        @else
                            <label class="flex items-center cursor-pointer">
                                <input
                                    type="checkbox"
                                    name="is_active"
                                    value="1"
                                    {{ old('is_active', $salesType->is_active) ? 'checked' : '' }}
                                    class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                >
                                <span class="ml-3 text-sm text-gray-700">Aktif</span>
                            </label>
                            <p class="mt-1 text-xs text-gray-500">Metode nonaktif tidak muncul di pilihan tipe penjualan POS.</p>
                        @endif
                        @error('is_active')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="p-6 border-t border-gray-100 bg-gray-50 flex items-center justify-end space-x-3">
                    <a
                        href="{{ route('admin.sales-types.index') }}"
                        class="px-5 py-2 bg-white border border-amber-300 text-amber-900 rounded-lg hover:bg-amber-50 transition"
                    >
                        Batal
                    </a>
                    <button
                        type="submit"
                        class="px-5 py-2 bg-gradient-to-r from-indigo-500 to-blue-600 text-white rounded-lg transition shadow-md hover:shadow-lg"
                    >
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
