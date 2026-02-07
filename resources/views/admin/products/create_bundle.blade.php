@extends('layouts.admin')

@section('title', 'Tambah Bundle')
@section('page-title', 'Tambah Bundle Baru')
@section('page-subtitle', 'Atur data bundle, ketersediaan POS, komponen bahan, dan harga per level')

@section('content')
@php
    $oldOutletIds = collect(old('pos_outlet_ids', []))->map(fn ($id) => (int) $id)->values()->all();
    $oldUserIds = collect(old('pos_user_ids', []))->map(fn ($id) => (int) $id)->values()->all();
@endphp

<div class="max-w-6xl">
    @if(session('error'))
        <div class="alert alert-error mb-4">
            <i class="fas fa-exclamation-circle text-lg"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-error mb-4">
            <i class="fas fa-exclamation-triangle text-lg"></i>
            <span>Periksa kembali input bundle Anda.</span>
        </div>
    @endif

    <form action="{{ route('admin.products.store') }}" method="POST" id="bundleForm" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="bundle_mode" value="1">
        <input type="hidden" name="product_type" id="product_type" value="finished_good">

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Informasi Bundle</h3>
                <p class="text-sm text-gray-500 mt-1">Bundle disimpan sebagai produk jadi dan dapat langsung dipakai di POS.</p>
            </div>

            <div class="p-6 space-y-8">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Gambar Produk</label>
                        <div class="border border-gray-200 rounded-xl p-3 bg-gray-50">
                            <div class="aspect-square rounded-lg bg-gray-100 border border-gray-200 overflow-hidden flex items-center justify-center">
                                <img id="imagePreview" src="" alt="Preview gambar produk" class="hidden w-full h-full object-cover">
                                <div id="imagePlaceholder" class="text-gray-400 text-center">
                                    <i class="fas fa-image text-4xl"></i>
                                    <p class="text-xs mt-2">Belum ada gambar</p>
                                </div>
                            </div>
                            <label for="image" class="mt-3 inline-flex items-center gap-2 px-3 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 cursor-pointer">
                                <i class="fas fa-upload"></i>
                                Upload Gambar
                            </label>
                            <input type="file" id="image" name="image" accept="image/*" class="hidden">
                            <p class="text-xs text-gray-500 mt-2">Gambar ini akan tampil di POS kasir. Maks 2MB.</p>
                            @error('image')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="lg:col-span-3 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                Nama Bundle <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                name="name"
                                id="name"
                                value="{{ old('name') }}"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('name') border-red-500 @enderror"
                                placeholder="Contoh: Paket Nasi + Es Teh"
                                required
                            >
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

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
                                placeholder="Contoh: BUNDLE-001"
                                required
                            >
                            @error('sku')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
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

                    <div>
                        <label for="unit" class="block text-sm font-medium text-gray-700 mb-2">
                            Unit <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="unit"
                            id="unit"
                            value="{{ old('unit', 'pcs') }}"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('unit') border-red-500 @enderror"
                            placeholder="pcs"
                            required
                        >
                        @error('unit')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="purchase_price" class="block text-sm font-medium text-gray-700 mb-2">
                            Harga Modal <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="number"
                            name="purchase_price"
                            id="purchase_price"
                            value="{{ old('purchase_price', 0) }}"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('purchase_price') border-red-500 @enderror"
                            placeholder="0"
                            min="0"
                            step="0.01"
                            required
                        >
                        @error('purchase_price')
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
                        @error('min_stock')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 p-5 border border-gray-200 rounded-xl bg-gray-50/60">
                    <div>
                        <h4 class="text-base font-semibold text-gray-900">Informasi Produk</h4>
                        <p class="text-xs text-gray-500 mt-1">Pengaturan harga jual utama dan status bundle.</p>

                        <div class="mt-4 space-y-4">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="is_sellable" id="is_sellable" value="1"
                                    {{ old('is_sellable', data_get($defaults, 'is_sellable', true)) ? 'checked' : '' }}
                                    class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                <span class="text-sm text-gray-700">Saya menjual bundle ini</span>
                            </label>

                            <div>
                                <label for="selling_price" class="block text-sm font-medium text-gray-700 mb-2">
                                    Harga Jual Reguler <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500">Rp</span>
                                    <input type="number" name="selling_price" id="selling_price"
                                        value="{{ old('selling_price', 0) }}"
                                        class="w-full pl-12 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('selling_price') border-red-500 @enderror"
                                        min="0" step="0.01" required>
                                </div>
                                @error('selling_price')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="is_active" value="1"
                                    {{ old('is_active', true) ? 'checked' : '' }}
                                    class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                <span class="text-sm text-gray-700">Bundle aktif</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-base font-semibold text-gray-900">Informasi POS</h4>
                        <p class="text-xs text-gray-500 mt-1">Atur bundle tampil di outlet dan channel penjualan.</p>

                        <div class="mt-4 space-y-3">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="is_pos_available" id="is_pos_available" value="1"
                                    {{ old('is_pos_available', data_get($defaults, 'is_pos_available', true)) ? 'checked' : '' }}
                                    class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                <span class="text-sm text-gray-700">Tersedia di POS</span>
                            </label>

                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="is_online_order_available" value="1"
                                    {{ old('is_online_order_available', data_get($defaults, 'is_online_order_available', false)) ? 'checked' : '' }}
                                    class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                <span class="text-sm text-gray-700">Tersedia di Online Order</span>
                            </label>

                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="is_available_all_outlets" id="is_available_all_outlets" value="1"
                                    {{ old('is_available_all_outlets', data_get($defaults, 'is_available_all_outlets', true)) ? 'checked' : '' }}
                                    class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                <span class="text-sm text-gray-700">Tersedia di semua outlet</span>
                            </label>

                            <div id="outlet-selector-tools" class="pt-1">
                                <p class="text-sm text-gray-600" id="selected-outlet-count">0 outlet dipilih</p>
                                <button type="button" id="open-outlet-modal" class="mt-2 px-3 py-2 bg-indigo-700 text-white rounded-lg text-sm font-medium hover:bg-indigo-800">
                                    Pilih outlet
                                </button>
                            </div>
                            <div id="posOutletHiddenInputs"></div>

                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="is_available_all_users" id="is_available_all_users" value="1"
                                    {{ old('is_available_all_users', data_get($defaults, 'is_available_all_users', true)) ? 'checked' : '' }}
                                    class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                <span class="text-sm text-gray-700">Tersedia untuk semua pengguna POS</span>
                            </label>

                            <div id="user-selector-tools" class="pt-1">
                                <p class="text-sm text-gray-600" id="selected-user-count">0 pengguna POS dipilih</p>
                                <button type="button" id="open-user-modal" class="mt-2 px-3 py-2 bg-indigo-700 text-white rounded-lg text-sm font-medium hover:bg-indigo-800">
                                    Pilih pengguna POS
                                </button>
                            </div>
                            <div id="posUserHiddenInputs"></div>
                        </div>
                    </div>
                </div>

                <div class="p-5 border border-gray-200 rounded-xl">
                    <div class="flex items-center gap-2 overflow-x-auto" id="bundle-tabs">
                        <button type="button" class="bundle-tab-btn px-4 py-2 rounded-lg text-sm font-semibold bg-indigo-600 text-white" data-target="tab-components">Bundle/Bahan</button>
                        <button type="button" class="bundle-tab-btn px-4 py-2 rounded-lg text-sm font-semibold bg-gray-100 text-gray-700" data-target="tab-pricing">Pengaturan Harga</button>
                        <button type="button" class="bundle-tab-btn px-4 py-2 rounded-lg text-sm font-semibold bg-gray-100 text-gray-700" data-target="tab-extra">Info Tambahan</button>
                    </div>

                    <div class="mt-5">
                        <div id="tab-components" class="bundle-tab-panel">
                            <div class="overflow-x-auto">
                                <table class="min-w-full border border-gray-200 rounded-lg overflow-hidden" id="components-table">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="text-left text-sm font-semibold text-gray-700 px-4 py-3 border-b border-gray-200">Item</th>
                                            <th class="text-left text-sm font-semibold text-gray-700 px-4 py-3 border-b border-gray-200 w-32">Qty</th>
                                            <th class="text-left text-sm font-semibold text-gray-700 px-4 py-3 border-b border-gray-200 w-32">Satuan</th>
                                            <th class="text-center text-sm font-semibold text-gray-700 px-4 py-3 border-b border-gray-200 w-20">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="bundle-components-body">
                                        @php
                                            $oldComponents = old('bundle_components', [['component_product_id' => '', 'quantity' => '', 'uom' => '']]);
                                        @endphp
                                        @foreach($oldComponents as $index => $component)
                                            <tr class="component-row border-b border-gray-200 last:border-b-0">
                                                <td class="px-4 py-3">
                                                    <select name="bundle_components[{{ $index }}][component_product_id]" class="component-product w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                                        <option value="">Pilih bahan...</option>
                                                        @foreach($rawMaterials as $raw)
                                                            <option value="{{ $raw->id }}" data-unit="{{ $raw->unit }}" {{ (string)($component['component_product_id'] ?? '') === (string)$raw->id ? 'selected' : '' }}>
                                                                {{ $raw->name }} ({{ $raw->sku }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <input type="number" name="bundle_components[{{ $index }}][quantity]" value="{{ $component['quantity'] ?? '' }}" min="0.0001" step="0.0001"
                                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="0">
                                                </td>
                                                <td class="px-4 py-3">
                                                    <input type="text" name="bundle_components[{{ $index }}][uom]" value="{{ $component['uom'] ?? '' }}"
                                                        class="component-uom w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="pcs">
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <button type="button" class="remove-component-row text-red-600 hover:text-red-800">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            @error('bundle_components')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror

                            <div class="mt-4 flex items-center gap-3">
                                <button type="button" id="add-component-row" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium">
                                    <i class="fas fa-plus mr-1"></i> Tambahkan Item
                                </button>
                                <span class="text-xs text-gray-500">Minimal 1 bahan agar BOM bundle terbentuk otomatis.</span>
                            </div>
                        </div>

                        <div id="tab-pricing" class="bundle-tab-panel hidden">
                            <div class="overflow-x-auto">
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
                                                            value="{{ old('price_levels.' . $levelKey, $levelKey === 'regular' ? old('selling_price', 0) : '') }}"
                                                            min="0"
                                                            step="0.01"
                                                            @if($levelKey === 'regular') required @endif
                                                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @if($levelKey === 'regular') bg-indigo-50/40 @endif"
                                                            @if($levelKey === 'regular') id="price_level_regular" @endif
                                                        >
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div id="tab-extra" class="bundle-tab-panel hidden">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Deskripsi Bundle</label>
                            <textarea name="description" id="description" rows="5"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                placeholder="Catatan tambahan untuk bundle ini...">{{ old('description') }}</textarea>
                            <p class="text-xs text-gray-500 mt-2">Catatan ini juga akan dipakai sebagai notes BOM awal.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-6 border-t border-gray-100 bg-gray-50 flex items-center justify-end gap-3">
                <a href="{{ route('admin.products.index') }}" class="px-5 py-2.5 bg-white border border-amber-300 text-amber-900 rounded-lg hover:bg-amber-50 transition">Batal</a>
                <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-emerald-500 via-teal-500 to-sky-500 text-white rounded-lg transition shadow-md hover:shadow-lg flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Simpan Bundle
                </button>
            </div>
        </div>
    </form>
</div>

<div id="outletModal" class="fixed inset-0 z-50 hidden bg-black/40 backdrop-blur-sm p-4">
    <div class="max-w-xl w-full mx-auto mt-16 bg-white rounded-xl shadow-2xl border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-base font-semibold text-gray-900">Pilih Outlet</h3>
            <button type="button" class="close-modal text-gray-500 hover:text-gray-700" data-target="outletModal"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-5 max-h-[60vh] overflow-y-auto space-y-2">
            @foreach($outlets as $outlet)
                <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox" class="outlet-option w-4 h-4" value="{{ $outlet->id }}">
                    <span class="text-sm text-gray-700">{{ $outlet->name }}</span>
                </label>
            @endforeach
        </div>
        <div class="px-5 py-4 border-t border-gray-100 flex justify-end gap-2">
            <button type="button" class="close-modal px-4 py-2 text-sm bg-white border border-gray-300 rounded-lg" data-target="outletModal">Tutup</button>
            <button type="button" class="close-modal px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg" data-target="outletModal">Simpan Pilihan</button>
        </div>
    </div>
</div>

<div id="userModal" class="fixed inset-0 z-50 hidden bg-black/40 backdrop-blur-sm p-4">
    <div class="max-w-xl w-full mx-auto mt-16 bg-white rounded-xl shadow-2xl border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-base font-semibold text-gray-900">Pilih Pengguna POS</h3>
            <button type="button" class="close-modal text-gray-500 hover:text-gray-700" data-target="userModal"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-5 max-h-[60vh] overflow-y-auto space-y-2">
            @foreach($posUsers as $user)
                <label class="flex items-start gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox" class="user-option w-4 h-4 mt-1" value="{{ $user->id }}">
                    <div>
                        <p class="text-sm font-medium text-gray-800">{{ $user->name }}</p>
                        <p class="text-xs text-gray-500">
                            {{ optional($user->role)->name ?? '-' }}
                            @if(optional($user->outlet)->name)
                                • {{ $user->outlet->name }}
                            @endif
                        </p>
                    </div>
                </label>
            @endforeach
        </div>
        <div class="px-5 py-4 border-t border-gray-100 flex justify-end gap-2">
            <button type="button" class="close-modal px-4 py-2 text-sm bg-white border border-gray-300 rounded-lg" data-target="userModal">Tutup</button>
            <button type="button" class="close-modal px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg" data-target="userModal">Simpan Pilihan</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tabs = document.querySelectorAll('.bundle-tab-btn');
        const panels = document.querySelectorAll('.bundle-tab-panel');

        function activateTab(targetId) {
            panels.forEach(panel => panel.classList.toggle('hidden', panel.id !== targetId));
            tabs.forEach(tab => {
                const isActive = tab.dataset.target === targetId;
                tab.classList.toggle('bg-indigo-600', isActive);
                tab.classList.toggle('text-white', isActive);
                tab.classList.toggle('bg-gray-100', !isActive);
                tab.classList.toggle('text-gray-700', !isActive);
            });
        }

        tabs.forEach(tab => {
            tab.addEventListener('click', function () {
                activateTab(tab.dataset.target);
            });
        });

        const imageInput = document.getElementById('image');
        const imagePreview = document.getElementById('imagePreview');
        const imagePlaceholder = document.getElementById('imagePlaceholder');

        if (imageInput) {
            imageInput.addEventListener('change', function (event) {
                const file = event.target.files && event.target.files[0];
                if (!file) {
                    imagePreview.src = '';
                    imagePreview.classList.add('hidden');
                    imagePlaceholder.classList.remove('hidden');
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (loadEvent) {
                    imagePreview.src = loadEvent.target.result;
                    imagePreview.classList.remove('hidden');
                    imagePlaceholder.classList.add('hidden');
                };
                reader.readAsDataURL(file);
            });
        }

        const selectedOutletIds = new Set(@json($oldOutletIds));
        const selectedUserIds = new Set(@json($oldUserIds));

        const allOutletsCheckbox = document.getElementById('is_available_all_outlets');
        const allUsersCheckbox = document.getElementById('is_available_all_users');
        const outletTools = document.getElementById('outlet-selector-tools');
        const userTools = document.getElementById('user-selector-tools');
        const outletCountText = document.getElementById('selected-outlet-count');
        const userCountText = document.getElementById('selected-user-count');
        const outletHiddenInputs = document.getElementById('posOutletHiddenInputs');
        const userHiddenInputs = document.getElementById('posUserHiddenInputs');

        const outletModal = document.getElementById('outletModal');
        const userModal = document.getElementById('userModal');
        const openOutletModalBtn = document.getElementById('open-outlet-modal');
        const openUserModalBtn = document.getElementById('open-user-modal');

        const outletOptions = document.querySelectorAll('.outlet-option');
        const userOptions = document.querySelectorAll('.user-option');

        function renderHiddenInputs(container, fieldName, idSet, isAllSelected) {
            container.innerHTML = '';

            if (isAllSelected) {
                return;
            }

            idSet.forEach(function (id) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = fieldName + '[]';
                input.value = String(id);
                container.appendChild(input);
            });
        }

        function refreshSelectionTexts() {
            outletCountText.textContent = `${selectedOutletIds.size} outlet dipilih`;
            userCountText.textContent = `${selectedUserIds.size} pengguna POS dipilih`;
        }

        function refreshAvailabilitySections() {
            const allOutlets = allOutletsCheckbox.checked;
            const allUsers = allUsersCheckbox.checked;

            outletTools.classList.toggle('hidden', allOutlets);
            userTools.classList.toggle('hidden', allUsers);

            renderHiddenInputs(outletHiddenInputs, 'pos_outlet_ids', selectedOutletIds, allOutlets);
            renderHiddenInputs(userHiddenInputs, 'pos_user_ids', selectedUserIds, allUsers);
        }

        function syncModalChecks() {
            outletOptions.forEach(option => {
                option.checked = selectedOutletIds.has(Number(option.value));
            });
            userOptions.forEach(option => {
                option.checked = selectedUserIds.has(Number(option.value));
            });
        }

        function openModal(modalElement) {
            modalElement.classList.remove('hidden');
            syncModalChecks();
        }

        function closeModal(modalElement) {
            modalElement.classList.add('hidden');
            refreshSelectionTexts();
            refreshAvailabilitySections();
        }

        openOutletModalBtn.addEventListener('click', function () {
            openModal(outletModal);
        });

        openUserModalBtn.addEventListener('click', function () {
            openModal(userModal);
        });

        document.querySelectorAll('.close-modal').forEach(button => {
            button.addEventListener('click', function () {
                const target = document.getElementById(button.dataset.target);
                if (target) {
                    closeModal(target);
                }
            });
        });

        outletModal.addEventListener('click', function (event) {
            if (event.target === outletModal) {
                closeModal(outletModal);
            }
        });

        userModal.addEventListener('click', function (event) {
            if (event.target === userModal) {
                closeModal(userModal);
            }
        });

        outletOptions.forEach(option => {
            option.addEventListener('change', function () {
                const value = Number(option.value);
                if (option.checked) {
                    selectedOutletIds.add(value);
                } else {
                    selectedOutletIds.delete(value);
                }
                refreshSelectionTexts();
            });
        });

        userOptions.forEach(option => {
            option.addEventListener('change', function () {
                const value = Number(option.value);
                if (option.checked) {
                    selectedUserIds.add(value);
                } else {
                    selectedUserIds.delete(value);
                }
                refreshSelectionTexts();
            });
        });

        allOutletsCheckbox.addEventListener('change', refreshAvailabilitySections);
        allUsersCheckbox.addEventListener('change', refreshAvailabilitySections);

        refreshSelectionTexts();
        refreshAvailabilitySections();

        const sellingPriceInput = document.getElementById('selling_price');
        const regularPriceInput = document.getElementById('price_level_regular');

        function syncPrice(from, to) {
            if (!from || !to) return;
            to.value = from.value;
        }

        if (sellingPriceInput && regularPriceInput) {
            sellingPriceInput.addEventListener('input', function () {
                syncPrice(sellingPriceInput, regularPriceInput);
            });

            regularPriceInput.addEventListener('input', function () {
                syncPrice(regularPriceInput, sellingPriceInput);
            });
        }

        const componentBody = document.getElementById('bundle-components-body');
        const addRowBtn = document.getElementById('add-component-row');
        let componentIndex = componentBody.querySelectorAll('.component-row').length;

        function makeOptionsHtml() {
            const firstRowSelect = componentBody.querySelector('select[name*="[component_product_id]"]');
            return firstRowSelect ? firstRowSelect.innerHTML : '<option value="">Pilih bahan...</option>';
        }

        function bindAutoUnit(row) {
            const select = row.querySelector('.component-product');
            const unitInput = row.querySelector('.component-uom');
            if (!select || !unitInput) return;

            select.addEventListener('change', function () {
                const selected = select.options[select.selectedIndex];
                const unit = selected ? selected.getAttribute('data-unit') : '';
                if (!unitInput.value && unit) {
                    unitInput.value = unit;
                }
            });
        }

        function addComponentRow() {
            const row = document.createElement('tr');
            row.className = 'component-row border-b border-gray-200 last:border-b-0';
            row.innerHTML = `
                <td class="px-4 py-3">
                    <select name="bundle_components[${componentIndex}][component_product_id]" class="component-product w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        ${makeOptionsHtml()}
                    </select>
                </td>
                <td class="px-4 py-3">
                    <input type="number" name="bundle_components[${componentIndex}][quantity]" min="0.0001" step="0.0001"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="0">
                </td>
                <td class="px-4 py-3">
                    <input type="text" name="bundle_components[${componentIndex}][uom]"
                        class="component-uom w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="pcs">
                </td>
                <td class="px-4 py-3 text-center">
                    <button type="button" class="remove-component-row text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button>
                </td>
            `;
            componentBody.appendChild(row);
            bindAutoUnit(row);
            componentIndex += 1;
        }

        if (addRowBtn) {
            addRowBtn.addEventListener('click', addComponentRow);
        }

        componentBody.querySelectorAll('.component-row').forEach(bindAutoUnit);

        componentBody.addEventListener('click', function (event) {
            const removeBtn = event.target.closest('.remove-component-row');
            if (!removeBtn) return;

            const rows = componentBody.querySelectorAll('.component-row');
            if (rows.length <= 1) {
                alert('Minimal harus ada 1 komponen bahan.');
                return;
            }

            removeBtn.closest('.component-row').remove();
        });

        const form = document.getElementById('bundleForm');
        form.addEventListener('submit', function (event) {
            if (!allOutletsCheckbox.checked && selectedOutletIds.size === 0) {
                event.preventDefault();
                alert('Pilih minimal 1 outlet atau centang tersedia di semua outlet.');
                return;
            }

            if (!allUsersCheckbox.checked && selectedUserIds.size === 0) {
                event.preventDefault();
                alert('Pilih minimal 1 pengguna POS atau centang tersedia untuk semua pengguna.');
            }
        });
    });
</script>
@endpush
