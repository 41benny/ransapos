@extends('layouts.admin')

@section('title', 'Edit Kategori Produk')
@section('page-title', 'Edit Kategori Produk')
@section('page-subtitle', 'Perbarui kategori produk sesuai kebutuhan operasional')

@section('content')
    <div class="max-w-4xl">
        <form action="{{ route('admin.product-categories.update', $productCategory) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="ui-card bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900">Informasi Kategori</h3>
                    <p class="text-sm text-gray-500 mt-1">Perubahan akan mempengaruhi daftar produk, POS, promo, dan laporan</p>
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
                                value="{{ old('code', $productCategory->code) }}"
                                class="ui-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('code') border-red-500 @enderror"
                                placeholder="Contoh: MAKANAN atau MINUMAN_DINGIN"
                                required
                            >
                            <p class="mt-1 text-xs text-gray-500">Akan dinormalisasi otomatis ke format huruf besar + underscore.</p>
                            @error('code')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                Nama Kategori <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                name="name"
                                id="name"
                                value="{{ old('name', $productCategory->name) }}"
                                class="ui-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('name') border-red-500 @enderror"
                                placeholder="Contoh: Makanan"
                                required
                            >
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            Deskripsi
                        </label>
                        <textarea
                            name="description"
                            id="description"
                            rows="4"
                            class="ui-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('description') border-red-500 @enderror"
                            placeholder="Catatan opsional untuk kategori ini"
                        >{{ old('description', $productCategory->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="flex items-center cursor-pointer">
                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                {{ old('is_active', $productCategory->is_active) ? 'checked' : '' }}
                                class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                            >
                            <span class="ml-3 text-sm text-gray-700">Aktif</span>
                        </label>
                        <p class="mt-1 text-xs text-gray-500">Kategori nonaktif tidak muncul saat membuat atau mengedit produk.</p>
                        @error('is_active')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="p-6 border-t border-gray-100 bg-gray-50 flex items-center justify-end space-x-3">
                    <a
                        href="{{ route('admin.product-categories.index') }}"
                        class="ui-btn ui-btn-ghost px-5 py-2 bg-white border border-amber-300 text-amber-900 rounded-lg hover:bg-amber-50 transition"
                    >
                        Batal
                    </a>
                    <button
                        type="submit"
                        class="ui-btn ui-btn-primary px-5 py-2 bg-gradient-to-r from-indigo-500 to-blue-600 text-white rounded-lg transition shadow-md hover:shadow-lg"
                    >
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
