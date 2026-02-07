@extends('layouts.admin')

@php
    $formMode = $formMode ?? 'product';
    $isBundleForm = $formMode === 'bundle';
    $defaults = $defaults ?? [];
@endphp

@section('title', $isBundleForm ? 'Tambah Bundle' : 'Tambah Produk')
@section('page-title', $isBundleForm ? 'Tambah Bundle Baru' : 'Tambah Produk Baru')
@section('page-subtitle', $isBundleForm ? 'Isi form bundle/menu siap jual untuk POS' : 'Isi form di bawah untuk menambah produk atau bundle siap jual')

@section('content')
<div class="max-w-6xl">
    <form action="{{ route('admin.products.store') }}" method="POST">
        @csrf

        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="p-6 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">{{ $isBundleForm ? 'Informasi Bundle' : 'Informasi Produk' }}</h3>
                <p class="text-sm text-gray-500 mt-1">{{ $isBundleForm ? 'Master bundle/menu, pengaturan POS, dan level harga penjualan' : 'Master produk, pengaturan POS, dan level harga penjualan' }}</p>
            </div>

            <div class="p-6 space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="sku" class="block text-sm font-medium text-gray-700 mb-2">
                            SKU <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="sku"
                            id="sku"
                            value="{{ old('sku') }}"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('sku') border-red-500 @enderror"
                            placeholder="Contoh: MENU-001"
                            required
                        >
                        @error('sku')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                            Nama Produk <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="name"
                            id="name"
                            value="{{ old('name') }}"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('name') border-red-500 @enderror"
                            placeholder="Contoh: Nasi Goreng Spesial"
                            required
                        >
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Kategori <span class="text-red-500">*</span>
                        </label>
                        <select
                            name="category_id"
                            id="category_id"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('category_id') border-red-500 @enderror"
                            required
                        >
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    @if($isBundleForm)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Jenis Produk <span class="text-red-500">*</span>
                            </label>
                            <input type="hidden" name="product_type" id="product_type" value="finished_good">
                            <div class="w-full px-4 py-2.5 border border-indigo-200 bg-indigo-50/70 text-indigo-800 rounded-lg font-medium">
                                Bundle / Produk Jadi
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Mode bundle selalu disimpan sebagai produk jadi.</p>
                        </div>
                    @else
                        <div>
                            <label for="product_type" class="block text-sm font-medium text-gray-700 mb-2">
                                Jenis Produk <span class="text-red-500">*</span>
                            </label>
                            <select
                                name="product_type"
                                id="product_type"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('product_type') border-red-500 @enderror"
                                required
                            >
                                <option value="finished_good" {{ old('product_type', data_get($defaults, 'product_type', 'finished_good')) == 'finished_good' ? 'selected' : '' }}>Produk Jadi</option>
                                <option value="raw_material" {{ old('product_type', data_get($defaults, 'product_type', 'finished_good')) == 'raw_material' ? 'selected' : '' }}>Bahan Baku</option>
                                <option value="service" {{ old('product_type', data_get($defaults, 'product_type', 'finished_good')) == 'service' ? 'selected' : '' }}>Jasa</option>
                            </select>
                            @error('product_type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    <div>
                        <label for="unit" class="block text-sm font-medium text-gray-700 mb-2">
                            Satuan <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="unit"
                            id="unit"
                            value="{{ old('unit') }}"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('unit') border-red-500 @enderror"
                            placeholder="Contoh: pcs, porsi, cup"
                            required
                        >
                        @error('unit')
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
                        rows="3"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('description') border-red-500 @enderror"
                        placeholder="Deskripsi produk (opsional)"
                    >{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
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
                                value="{{ old('purchase_price') }}"
                                class="w-full pl-12 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('purchase_price') border-red-500 @enderror"
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

                    <div>
                        <label for="selling_price" class="block text-sm font-medium text-gray-700 mb-2">
                            Harga Jual Reguler <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500">Rp</span>
                            <input
                                type="number"
                                name="selling_price"
                                id="selling_price"
                                value="{{ old('selling_price') }}"
                                class="w-full pl-12 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('selling_price') border-red-500 @enderror"
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

                    <div>
                        <label for="min_stock" class="block text-sm font-medium text-gray-700 mb-2">
                            Stok Minimal
                        </label>
                        <input
                            type="number"
                            name="min_stock"
                            id="min_stock"
                            value="{{ old('min_stock', 0) }}"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('min_stock') border-red-500 @enderror"
                            placeholder="0"
                            min="0"
                        >
                        <p class="mt-1 text-xs text-gray-500">Untuk notifikasi stok menipis</p>
                        @error('min_stock')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 p-5 border border-gray-200 rounded-xl bg-gray-50/60">
                    <div>
                        <h4 class="text-base font-semibold text-gray-900">Informasi Produk</h4>
                        <p class="text-xs text-gray-500 mt-1">Atur status jual produk ini</p>

                        <div class="mt-4 space-y-4">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    name="is_sellable"
                                    id="is_sellable"
                                    value="1"
                                    {{ old('is_sellable', data_get($defaults, 'is_sellable', true)) ? 'checked' : '' }}
                                    class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                >
                                <span class="text-sm text-gray-700">Saya menjual produk ini</span>
                            </label>

                            <label class="flex items-center gap-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    name="is_active"
                                    value="1"
                                    {{ old('is_active', true) ? 'checked' : '' }}
                                    class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                >
                                <span class="text-sm text-gray-700">Produk aktif</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-base font-semibold text-gray-900">Informasi POS</h4>
                        <p class="text-xs text-gray-500 mt-1">Atur ketersediaan produk di POS</p>

                        <div class="mt-4 space-y-3">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    name="is_pos_available"
                                    id="is_pos_available"
                                    value="1"
                                    {{ old('is_pos_available', data_get($defaults, 'is_pos_available', true)) ? 'checked' : '' }}
                                    class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                >
                                <span class="text-sm text-gray-700">Tersedia di POS</span>
                            </label>

                            <label class="flex items-center gap-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    name="is_online_order_available"
                                    id="is_online_order_available"
                                    value="1"
                                    {{ old('is_online_order_available', data_get($defaults, 'is_online_order_available', false)) ? 'checked' : '' }}
                                    class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                >
                                <span class="text-sm text-gray-700">Tersedia di Online Order</span>
                            </label>

                            <label class="flex items-center gap-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    name="is_available_all_outlets"
                                    id="is_available_all_outlets"
                                    value="1"
                                    {{ old('is_available_all_outlets', data_get($defaults, 'is_available_all_outlets', true)) ? 'checked' : '' }}
                                    class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                >
                                <span class="text-sm text-gray-700">Tersedia di semua outlet</span>
                            </label>

                            <div id="outlet-selector-wrap" class="hidden pt-1">
                                <label for="pos_outlet_ids" class="block text-sm font-medium text-gray-700 mb-1">
                                    Pilih outlet
                                </label>
                                <select
                                    name="pos_outlet_ids[]"
                                    id="pos_outlet_ids"
                                    multiple
                                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent h-32"
                                >
                                    @foreach($outlets as $outlet)
                                        <option
                                            value="{{ $outlet->id }}"
                                            {{ in_array($outlet->id, old('pos_outlet_ids', [])) ? 'selected' : '' }}
                                        >
                                            {{ $outlet->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Tekan Ctrl/Cmd untuk pilih lebih dari satu outlet</p>
                                @error('pos_outlet_ids')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <label class="flex items-center gap-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    name="is_available_all_users"
                                    value="1"
                                    {{ old('is_available_all_users', data_get($defaults, 'is_available_all_users', true)) ? 'checked' : '' }}
                                    class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                >
                                <span class="text-sm text-gray-700">Tersedia untuk semua pengguna POS</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="p-5 border border-gray-200 rounded-xl">
                    <h4 class="text-base font-semibold text-gray-900">Pengaturan Harga</h4>
                    <p class="text-xs text-gray-500 mt-1">Harga mengikuti tipe penjualan di POS. Default menggunakan Reguler.</p>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full border border-gray-200 rounded-lg overflow-hidden">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="text-left text-sm font-semibold text-gray-700 px-4 py-3 border-b border-gray-200 w-1/2">Price Level</th>
                                    <th class="text-left text-sm font-semibold text-gray-700 px-4 py-3 border-b border-gray-200">Harga</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($priceLevels as $levelKey => $levelLabel)
                                    <tr class="border-b border-gray-200 last:border-b-0">
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $levelLabel }}</td>
                                        <td class="px-4 py-3">
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm">Rp</span>
                                                <input
                                                    type="number"
                                                    name="price_levels[{{ $levelKey }}]"
                                                    value="{{ old('price_levels.' . $levelKey, $levelKey === 'regular' ? old('selling_price') : '') }}"
                                                    min="0"
                                                    step="0.01"
                                                    @if($levelKey === 'regular') required @endif
                                                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('price_levels.' . $levelKey) border-red-500 @enderror {{ $levelKey === 'regular' ? 'bg-indigo-50/40' : '' }}"
                                                    placeholder="0"
                                                    @if($levelKey === 'regular') id="price_level_regular" @endif
                                                >
                                            </div>
                                            @error('price_levels.' . $levelKey)
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="p-6 border-t border-gray-100 bg-gray-50 flex items-center justify-end space-x-3">
                <a
                    href="{{ route('admin.products.index') }}"
                    class="px-5 py-2.5 bg-white border border-amber-300 text-amber-900 rounded-lg hover:bg-amber-50 transition"
                >
                    Batal
                </a>
                <button
                    type="submit"
                    class="px-5 py-2.5 bg-gradient-to-r from-emerald-500 via-teal-500 to-sky-500 text-white rounded-lg transition shadow-md hover:shadow-lg flex items-center"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ $isBundleForm ? 'Simpan Bundle' : 'Simpan Produk' }}
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const allOutletsCheckbox = document.getElementById('is_available_all_outlets');
        const outletWrap = document.getElementById('outlet-selector-wrap');
        const outletSelect = document.getElementById('pos_outlet_ids');
        const sellingPriceInput = document.getElementById('selling_price');
        const regularPriceInput = document.getElementById('price_level_regular');
        const productTypeInput = document.getElementById('product_type');
        const sellableInput = document.getElementById('is_sellable');
        const posAvailableInput = document.getElementById('is_pos_available');
        const onlineOrderInput = document.getElementById('is_online_order_available');

        function toggleOutletSelector() {
            const useAllOutlets = allOutletsCheckbox.checked;
            outletWrap.classList.toggle('hidden', useAllOutlets);
            outletSelect.disabled = useAllOutlets;
        }

        function syncPrice(from, to) {
            if (!from || !to) {
                return;
            }
            to.value = from.value;
        }

        function applyProductTypeRules() {
            if (!productTypeInput) {
                return;
            }

            if (productTypeInput.value === 'raw_material') {
                if (sellableInput) {
                    sellableInput.checked = false;
                }
                if (posAvailableInput) {
                    posAvailableInput.checked = false;
                }
                if (onlineOrderInput) {
                    onlineOrderInput.checked = false;
                }
            }
        }

        if (allOutletsCheckbox) {
            toggleOutletSelector();
            allOutletsCheckbox.addEventListener('change', toggleOutletSelector);
        }

        if (sellingPriceInput && regularPriceInput) {
            sellingPriceInput.addEventListener('input', function () {
                syncPrice(sellingPriceInput, regularPriceInput);
            });
            regularPriceInput.addEventListener('input', function () {
                syncPrice(regularPriceInput, sellingPriceInput);
            });
        }

        if (productTypeInput) {
            applyProductTypeRules();
            productTypeInput.addEventListener('change', applyProductTypeRules);
        }
    });
</script>
@endpush
