@extends('layouts.admin')

@section('title', 'Edit Produk')
@section('page-title', 'Edit Produk')
@section('page-subtitle', 'Perbarui informasi produk')

@section('content')
<div class="max-w-4xl">
    <form action="{{ route('admin.products.update', $product) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <!-- Header -->
            <div class="p-6 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Informasi Produk</h3>
                <p class="text-sm text-gray-500 mt-1">Perbarui data produk {{ $product->name }}</p>
            </div>

            <!-- Form Content -->
            <div class="p-6 space-y-6">
                <!-- SKU & Nama (2 kolom) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- SKU -->
                    <div>
                        <label for="sku" class="block text-sm font-medium text-gray-700 mb-2">
                            SKU <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="sku" 
                            id="sku" 
                            value="{{ old('sku', $product->sku) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('sku') border-red-500 @enderror"
                            placeholder="Contoh: BEV001"
                            required
                        >
                        @error('sku')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Nama Produk -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                            Nama Produk <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="name" 
                            id="name" 
                            value="{{ old('name', $product->name) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('name') border-red-500 @enderror"
                            placeholder="Contoh: Cappuccino"
                            required
                        >
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Kategori, Jenis, & Satuan -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Kategori -->
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Kategori <span class="text-red-500">*</span>
                        </label>
                        <select 
                            name="category_id" 
                            id="category_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('category_id') border-red-500 @enderror"
                            required
                        >
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Jenis Produk -->
                    <div>
                        <label for="product_type" class="block text-sm font-medium text-gray-700 mb-2">
                            Jenis Produk <span class="text-red-500">*</span>
                        </label>
                        <select
                            name="product_type"
                            id="product_type"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('product_type') border-red-500 @enderror"
                            required
                        >
                            <option value="finished_good" {{ old('product_type', $product->product_type) == 'finished_good' ? 'selected' : '' }}>Produk Jadi</option>
                            <option value="raw_material" {{ old('product_type', $product->product_type) == 'raw_material' ? 'selected' : '' }}>Bahan Baku</option>
                            <option value="service" {{ old('product_type', $product->product_type) == 'service' ? 'selected' : '' }}>Jasa</option>
                        </select>
                        @error('product_type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Satuan -->
                    <div>
                        <label for="unit" class="block text-sm font-medium text-gray-700 mb-2">
                            Satuan <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="unit" 
                            id="unit" 
                            value="{{ old('unit', $product->unit) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('unit') border-red-500 @enderror"
                            placeholder="Contoh: cup, pcs, pack"
                            required
                        >
                        @error('unit')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Deskripsi -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        Deskripsi
                    </label>
                    <textarea 
                        name="description" 
                        id="description" 
                        rows="3"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('description') border-red-500 @enderror"
                        placeholder="Deskripsi produk (opsional)"
                    >{{ old('description', $product->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Harga Beli & Harga Jual -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Harga Beli -->
                    <div>
                        <label for="purchase_price" class="block text-sm font-medium text-gray-700 mb-2">
                            Harga Beli <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500">Rp</span>
                            <input 
                                type="number" 
                                name="purchase_price" 
                                id="purchase_price" 
                                value="{{ old('purchase_price', $product->purchase_price) }}"
                                class="w-full pl-12 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('purchase_price') border-red-500 @enderror"
                                placeholder="0"
                                min="0"
                                step="0.01"
                                required
                            >
                        </div>
                        @error('purchase_price')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Harga Jual -->
                    <div>
                        <label for="selling_price" class="block text-sm font-medium text-gray-700 mb-2">
                            Harga Jual <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500">Rp</span>
                            <input 
                                type="number" 
                                name="selling_price" 
                                id="selling_price" 
                                value="{{ old('selling_price', $product->selling_price) }}"
                                class="w-full pl-12 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('selling_price') border-red-500 @enderror"
                                placeholder="0"
                                min="0"
                                step="0.01"
                                required
                            >
                        </div>
                        @error('selling_price')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Stok Minimal & Status -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Stok Minimal -->
                    <div>
                        <label for="min_stock" class="block text-sm font-medium text-gray-700 mb-2">
                            Stok Minimal
                        </label>
                        <input 
                            type="number" 
                            name="min_stock" 
                            id="min_stock" 
                            value="{{ old('min_stock', $product->min_stock) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('min_stock') border-red-500 @enderror"
                            placeholder="0"
                            min="0"
                        >
                        <p class="mt-1 text-xs text-gray-500">Untuk notifikasi jika stok menipis</p>
                        @error('min_stock')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Status Aktif -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Status
                        </label>
                        <label class="flex items-center cursor-pointer">
                            <input 
                                type="checkbox" 
                                name="is_active" 
                                value="1"
                                {{ old('is_active', $product->is_active) ? 'checked' : '' }}
                                class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                            >
                            <span class="ml-3 text-sm text-gray-700">Produk Aktif</span>
                        </label>
                        <p class="mt-1 text-xs text-gray-500">Produk nonaktif tidak akan muncul di POS</p>
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="p-6 border-t border-gray-100 bg-gray-50 flex items-center justify-end space-x-3">
                <a 
                    href="{{ route('admin.products.index') }}"
                    class="px-5 py-2 bg-white border border-amber-300 text-amber-900 rounded-lg hover:bg-amber-50 transition"
                >
                    Batal
                </a>
                <button 
                    type="submit"
                    class="px-5 py-2 bg-gradient-to-r from-emerald-500 via-teal-500 to-sky-500 text-white rounded-lg transition shadow-md hover:shadow-lg flex items-center"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Perbarui Produk
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

