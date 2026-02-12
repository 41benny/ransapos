@extends('layouts.admin')

@section('title', 'Edit Produk')
@section('page-title', 'Edit Produk')
@section('page-subtitle', 'Perbarui informasi produk, POS, dan level harga')

@section('content')
    <div class="max-w-6xl">
        <form action="{{ route('admin.products.update', $product) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900">Informasi Produk</h3>
                    <p class="text-sm text-gray-500 mt-1">Perbarui data produk {{ $product->name }}</p>
                </div>

                <div class="p-6 space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="sku" class="block text-sm font-medium text-gray-700 mb-2">
                                SKU <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="sku" id="sku" value="{{ old('sku', $product->sku) }}"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('sku') border-red-500 @enderror"
                                placeholder="Contoh: MENU-001" required>
                            @error('sku')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                Nama Produk <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" id="name" value="{{ old('name', $product->name) }}"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('name') border-red-500 @enderror"
                                placeholder="Contoh: Nasi Goreng Spesial" required>
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
                            <select name="category_id" id="category_id"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('category_id') border-red-500 @enderror"
                                required>
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

                        <div>
                            <label for="product_type" class="block text-sm font-medium text-gray-700 mb-2">
                                Jenis Produk <span class="text-red-500">*</span>
                            </label>
                            <select name="product_type" id="product_type"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('product_type') border-red-500 @enderror"
                                required>
                                <option value="finished_good" {{ old('product_type', $product->product_type) == 'finished_good' ? 'selected' : '' }}>Produk Jadi</option>
                                <option value="raw_material" {{ old('product_type', $product->product_type) == 'raw_material' ? 'selected' : '' }}>Bahan Baku</option>
                                <option value="service" {{ old('product_type', $product->product_type) == 'service' ? 'selected' : '' }}>Jasa</option>
                            </select>
                            @error('product_type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="unit" class="block text-sm font-medium text-gray-700 mb-2">
                                Satuan <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="unit" id="unit" value="{{ old('unit', $product->unit) }}"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('unit') border-red-500 @enderror"
                                placeholder="Contoh: pcs, porsi, cup" required>
                            @error('unit')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            Deskripsi
                        </label>
                        <textarea name="description" id="description" rows="3"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('description') border-red-500 @enderror"
                            placeholder="Deskripsi produk (opsional)">{{ old('description', $product->description) }}</textarea>
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
                                <input type="number" name="purchase_price" id="purchase_price"
                                    value="{{ old('purchase_price', $product->purchase_price) }}"
                                    class="w-full pl-12 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('purchase_price') border-red-500 @enderror"
                                    placeholder="0" min="0" step="0.01" required>
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
                                <input type="number" name="selling_price" id="selling_price"
                                    value="{{ old('selling_price', $product->selling_price) }}"
                                    class="w-full pl-12 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('selling_price') border-red-500 @enderror"
                                    placeholder="0" min="0" step="0.01" required>
                            </div>
                            @error('selling_price')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="min_stock" class="block text-sm font-medium text-gray-700 mb-2">
                                Stok Minimal
                            </label>
                            <input type="number" name="min_stock" id="min_stock"
                                value="{{ old('min_stock', $product->min_stock) }}"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('min_stock') border-red-500 @enderror"
                                placeholder="0" min="0">
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
                                    <input type="checkbox" name="is_sellable" id="is_sellable" value="1" {{ old('is_sellable', $product->is_sellable ?? true) ? 'checked' : '' }}
                                        class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                    <span class="text-sm text-gray-700">Saya menjual produk ini</span>
                                </label>

                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }}
                                        class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                    <span class="text-sm text-gray-700">Produk aktif</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-base font-semibold text-gray-900">Informasi POS</h4>
                            <p class="text-xs text-gray-500 mt-1">Atur ketersediaan produk di POS</p>

                            <div class="mt-4 space-y-3">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" name="is_pos_available" id="is_pos_available" value="1" {{ old('is_pos_available', $product->is_pos_available ?? true) ? 'checked' : '' }}
                                        class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                    <span class="text-sm text-gray-700">Tersedia di POS</span>
                                </label>

                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" name="is_online_order_available" id="is_online_order_available"
                                        value="1" {{ old('is_online_order_available', $product->is_online_order_available ?? false) ? 'checked' : '' }}
                                        class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                    <span class="text-sm text-gray-700">Tersedia di Online Order</span>
                                </label>

                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" name="is_available_all_outlets" id="is_available_all_outlets"
                                        value="1" {{ old('is_available_all_outlets', $product->is_available_all_outlets ?? true) ? 'checked' : '' }}
                                        class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                    <span class="text-sm text-gray-700">Tersedia di semua outlet</span>
                                </label>

                                @php
                                    $selectedOutlets = old('pos_outlet_ids', $product->pos_outlet_ids ?? []);
                                @endphp
                                <div id="outlet-selector-wrap" class="hidden pt-1">
                                    <label for="pos_outlet_ids" class="block text-sm font-medium text-gray-700 mb-1">
                                        Pilih outlet
                                    </label>
                                    <select name="pos_outlet_ids[]" id="pos_outlet_ids" multiple
                                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent h-32">
                                        @foreach($outlets as $outlet)
                                            <option value="{{ $outlet->id }}" {{ in_array($outlet->id, $selectedOutlets) ? 'selected' : '' }}>
                                                {{ $outlet->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="text-xs text-gray-500 mt-1">Tekan Ctrl/Cmd untuk pilih lebih dari satu outlet
                                    </p>
                                    @error('pos_outlet_ids')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" name="is_available_all_users" value="1" {{ old('is_available_all_users', $product->is_available_all_users ?? true) ? 'checked' : '' }}
                                        class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                    <span class="text-sm text-gray-700">Tersedia untuk semua pengguna POS</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="p-5 border border-gray-200 rounded-xl">
                        <h4 class="text-base font-semibold text-gray-900">Pengaturan Harga</h4>
                        <p class="text-xs text-gray-500 mt-1 mb-4">Harga mengikuti tipe penjualan di POS. Default
                            menggunakan Reguler.</p>

                        <!-- Info Banner -->
                        <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <p class="text-sm text-gray-700">
                                <i class="fas fa-info-circle text-blue-600 mr-1"></i>
                                <strong>Pengaturan Harga Per Outlet:</strong><br>
                                Pilih outlet yang ingin diatur harga khusus. Outlet yang tidak dipilih akan menggunakan
                                harga default.
                            </p>
                        </div>

                        @foreach($priceLevels as $levelKey => $levelLabel)
                            @php
                                $priceData = data_get($product->price_levels, $levelKey);
                                $defaultPrice = is_array($priceData) ? ($priceData['default'] ?? 0) : $priceData;
                                if ($defaultPrice === null && $levelKey === 'regular') {
                                    $defaultPrice = $product->selling_price;
                                }
                                $outletPrices = is_array($priceData) ? ($priceData['outlets'] ?? []) : [];
                            @endphp
                            <div class="mb-6 border border-gray-200 rounded-lg overflow-hidden">
                                <!-- Header Price Level -->
                                <div
                                    class="bg-gradient-to-r from-gray-50 to-gray-100 px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                                    <h4 class="text-sm font-bold text-gray-900">
                                        <i class="fas fa-tag text-gray-500 mr-2"></i>{{ $levelLabel }}
                                    </h4>
                                    <button type="button"
                                        class="text-xs text-blue-600 hover:text-blue-700 font-medium toggle-outlet-selection"
                                        data-level="{{ $levelKey }}">
                                        <i class="fas fa-store mr-1"></i>Pilih Outlet
                                    </button>
                                </div>

                                <div class="p-4 space-y-4">
                                    <!-- Harga Default -->
                                    <div>
                                        <label class="form-label text-xs font-semibold mb-2">
                                            Harga Default
                                            @if($levelKey === 'regular')
                                                <span class="text-red-500">*</span>
                                                <span class="text-gray-400 font-normal">(wajib diisi)</span>
                                            @endif
                                        </label>
                                        <div class="flex items-center gap-2 max-w-md">
                                            <div class="flex flex-1">
                                                <span
                                                    class="inline-flex items-center px-3 text-xs text-gray-600 bg-gray-100 border border-r-0 border-gray-300 rounded-l-md">Rp</span>
                                                <input type="number" name="price_levels[{{ $levelKey }}][default]"
                                                    value="{{ old('price_levels.' . $levelKey . '.default', $defaultPrice ?? '') }}"
                                                    min="0" step="0.01" @if($levelKey === 'regular') required @endif
                                                    class="form-input rounded-l-none flex-1 text-sm @if($levelKey === 'regular') bg-blue-50/30 border-blue-200 font-semibold @endif price-default-input"
                                                    data-level="{{ $levelKey }}" placeholder="0">
                                            </div>
                                            <button type="button" class="btn btn-secondary btn-sm copy-to-all-outlets-btn"
                                                data-level="{{ $levelKey }}"
                                                title="Salin harga default ke semua outlet yang dipilih">
                                                <i class="fas fa-copy mr-1"></i>Copy ke Semua
                                            </button>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1.5">
                                            <i class="fas fa-info-circle text-gray-400 mr-1"></i>
                                            Digunakan untuk outlet yang tidak punya harga khusus
                                        </p>
                                    </div>

                                    <!-- Outlet Selection Accordion -->
                                    <div class="outlet-selection-section hidden" data-level="{{ $levelKey }}">
                                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                                            <!-- Outlet Selection Header -->
                                            <div
                                                class="bg-gray-50 px-3 py-2 border-b border-gray-200 flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <i class="fas fa-store text-gray-500 text-xs"></i>
                                                    <span class="text-xs font-semibold text-gray-700">Harga Khusus Per
                                                        Outlet</span>
                                                    <span class="outlet-selected-count badge badge-sm bg-gray-100 text-gray-500"
                                                        data-level="{{ $levelKey }}">0 dipilih</span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <button type="button"
                                                        class="text-xs text-blue-600 hover:text-blue-700 select-all-outlets"
                                                        data-level="{{ $levelKey }}">
                                                        Pilih Semua
                                                    </button>
                                                    <span class="text-gray-300">|</span>
                                                    <button type="button"
                                                        class="text-xs text-gray-600 hover:text-gray-700 deselect-all-outlets"
                                                        data-level="{{ $levelKey }}">
                                                        Batal Semua
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Outlet List with Checkboxes -->
                                            <div class="max-h-64 overflow-y-auto">
                                                @foreach($outlets as $outlet)
                                                    @php
                                                        $outletPrice = old('price_levels.' . $levelKey . '.outlets.' . $outlet->id, $outletPrices[$outlet->id] ?? '');
                                                    @endphp
                                                    <div class="outlet-price-row border-b border-gray-100 last:border-0 hover:bg-gray-50 transition-colors"
                                                        data-outlet-id="{{ $outlet->id }}" data-level="{{ $levelKey }}">
                                                        <label class="flex items-center gap-3 px-3 py-2.5 cursor-pointer">
                                                            <input type="checkbox"
                                                                class="outlet-checkbox w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                                                data-level="{{ $levelKey }}" data-outlet-id="{{ $outlet->id }}" {{ $outletPrice ? 'checked' : '' }}>
                                                            <div class="flex-1 flex items-center justify-between gap-3">
                                                                <span
                                                                    class="text-sm text-gray-700 font-medium">{{ $outlet->name }}</span>
                                                                <div
                                                                    class="flex items-center gap-2 outlet-price-input-wrapper {{ $outletPrice ? '' : 'hidden' }}">
                                                                    <div class="flex items-center">
                                                                        <span
                                                                            class="inline-flex items-center px-2 text-xs text-gray-500 bg-gray-50 border border-r-0 border-gray-300 rounded-l-md">Rp</span>
                                                                        <input type="number"
                                                                            name="price_levels[{{ $levelKey }}][outlets][{{ $outlet->id }}]"
                                                                            value="{{ $outletPrice }}" min="0" step="0.01"
                                                                            placeholder="0"
                                                                            class="form-input rounded-l-none text-sm w-32 outlet-price-input"
                                                                            data-level="{{ $levelKey }}"
                                                                            data-outlet-id="{{ $outlet->id }}" {{ $outletPrice ? '' : 'disabled' }}>
                                                                    </div>
                                                                    <button type="button"
                                                                        class="text-xs text-gray-400 hover:text-blue-600 copy-from-default-btn"
                                                                        data-level="{{ $levelKey }}"
                                                                        data-outlet-id="{{ $outlet->id }}"
                                                                        title="Salin dari harga default">
                                                                        <i class="fas fa-arrow-left"></i>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        <p class="text-xs text-gray-500 mt-2">
                                            <i class="fas fa-lightbulb text-yellow-500 mr-1"></i>
                                            Centang outlet untuk mengatur harga khusus. Kosongkan input untuk menggunakan harga
                                            default.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="p-6 border-t border-gray-100 bg-gray-50 flex items-center justify-end space-x-3">
                    <a href="{{ session('product_index_url', route('admin.products.index')) }}"
                        class="px-5 py-2.5 bg-white border border-amber-300 text-amber-900 rounded-lg hover:bg-amber-50 transition">
                        Batal
                    </a>
                    <button type="submit"
                        class="px-5 py-2.5 bg-gradient-to-r from-emerald-500 via-teal-500 to-sky-500 text-white rounded-lg transition shadow-md hover:shadow-lg flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Perbarui Produk
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
            const regularPriceDefaultInput = document.querySelector('input[name="price_levels[regular][default]"]');
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

            if (sellingPriceInput && regularPriceDefaultInput) {
                sellingPriceInput.addEventListener('input', function () {
                    syncPrice(sellingPriceInput, regularPriceDefaultInput);
                });
                regularPriceDefaultInput.addEventListener('input', function () {
                    syncPrice(regularPriceDefaultInput, sellingPriceInput);
                });
            }

            if (productTypeInput) {
                applyProductTypeRules();
                productTypeInput.addEventListener('change', applyProductTypeRules);
            }

            // ============================================
            // OUTLET-SPECIFIC PRICING LOGIC
            // ============================================

            // Toggle outlet selection section
            document.querySelectorAll('.toggle-outlet-selection').forEach(btn => {
                btn.addEventListener('click', function () {
                    const level = this.dataset.level;
                    const section = document.querySelector(`.outlet-selection-section[data-level="${level}"]`);

                    if (section) {
                        section.classList.toggle('hidden');
                        const icon = this.querySelector('i');
                        if (section.classList.contains('hidden')) {
                            icon.classList.remove('fa-chevron-up');
                            icon.classList.add('fa-store');
                            this.innerHTML = '<i class="fas fa-store mr-1"></i>Pilih Outlet';
                        } else {
                            icon.classList.remove('fa-store');
                            icon.classList.add('fa-chevron-up');
                            this.innerHTML = '<i class="fas fa-chevron-up mr-1"></i>Sembunyikan';
                        }
                    }
                });
            });

            // Handle outlet checkbox change
            document.querySelectorAll('.outlet-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', function () {
                    const level = this.dataset.level;
                    const outletId = this.dataset.outletId;
                    const row = this.closest('.outlet-price-row');
                    const inputWrapper = row.querySelector('.outlet-price-input-wrapper');
                    const priceInput = row.querySelector('.outlet-price-input');

                    if (this.checked) {
                        inputWrapper.classList.remove('hidden');
                        priceInput.disabled = false;
                        priceInput.focus();
                    } else {
                        inputWrapper.classList.add('hidden');
                        priceInput.disabled = true;
                        priceInput.value = '';
                    }

                    updateOutletSelectedCount(level);
                });
            });

            // Select all outlets
            document.querySelectorAll('.select-all-outlets').forEach(btn => {
                btn.addEventListener('click', function () {
                    const level = this.dataset.level;
                    document.querySelectorAll(`.outlet-checkbox[data-level="${level}"]`).forEach(checkbox => {
                        if (!checkbox.checked) {
                            checkbox.checked = true;
                            checkbox.dispatchEvent(new Event('change'));
                        }
                    });
                });
            });

            // Deselect all outlets
            document.querySelectorAll('.deselect-all-outlets').forEach(btn => {
                btn.addEventListener('click', function () {
                    const level = this.dataset.level;
                    document.querySelectorAll(`.outlet-checkbox[data-level="${level}"]`).forEach(checkbox => {
                        if (checkbox.checked) {
                            checkbox.checked = false;
                            checkbox.dispatchEvent(new Event('change'));
                        }
                    });
                });
            });

            // Copy default price to all selected outlets
            document.querySelectorAll('.copy-to-all-outlets-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    const level = this.dataset.level;
                    const defaultInput = document.querySelector(`.price-default-input[data-level="${level}"]`);
                    const defaultPrice = defaultInput ? defaultInput.value : '';

                    if (!defaultPrice || parseFloat(defaultPrice) <= 0) {
                        alert('Harap isi harga default terlebih dahulu.');
                        return;
                    }

                    let copiedCount = 0;
                    document.querySelectorAll(`.outlet-checkbox[data-level="${level}"]:checked`).forEach(checkbox => {
                        const outletId = checkbox.dataset.outletId;
                        const priceInput = document.querySelector(`.outlet-price-input[data-level="${level}"][data-outlet-id="${outletId}"]`);
                        if (priceInput) {
                            priceInput.value = defaultPrice;
                            copiedCount++;
                        }
                    });

                    if (copiedCount > 0) {
                        showToast(`Harga berhasil disalin ke ${copiedCount} outlet`, 'success');
                    } else {
                        alert('Tidak ada outlet yang dipilih. Centang outlet terlebih dahulu.');
                    }
                });
            });

            // Copy from default price (individual)
            document.querySelectorAll('.copy-from-default-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    const level = this.dataset.level;
                    const outletId = this.dataset.outletId;
                    const defaultInput = document.querySelector(`.price-default-input[data-level="${level}"]`);
                    const priceInput = document.querySelector(`.outlet-price-input[data-level="${level}"][data-outlet-id="${outletId}"]`);

                    if (defaultInput && priceInput) {
                        const defaultPrice = defaultInput.value;
                        if (defaultPrice && parseFloat(defaultPrice) > 0) {
                            priceInput.value = defaultPrice;
                            showToast('Harga disalin dari default', 'success');
                        } else {
                            alert('Harga default belum diisi.');
                        }
                    }
                });
            });

            // Update selected outlet count
            function updateOutletSelectedCount(level) {
                const count = document.querySelectorAll(`.outlet-checkbox[data-level="${level}"]:checked`).length;
                const badge = document.querySelector(`.outlet-selected-count[data-level="${level}"]`);
                if (badge) {
                    badge.textContent = `${count} dipilih`;
                    badge.classList.toggle('bg-blue-100', count > 0);
                    badge.classList.toggle('text-blue-700', count > 0);
                    badge.classList.toggle('bg-gray-100', count === 0);
                    badge.classList.toggle('text-gray-500', count === 0);
                }
            }

            // Initialize counts on page load
            document.querySelectorAll('.outlet-selected-count').forEach(badge => {
                const level = badge.dataset.level;
                updateOutletSelectedCount(level);
            });

            // Toast notification helper
            function showToast(message, type = 'success') {
                const toast = document.createElement('div');
                toast.className = `fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg ${type === 'success' ? 'bg-green-500' : 'bg-red-500'
                    } text-white text-sm font-medium`;
                toast.style.animation = 'slideInRight 0.3s ease';
                toast.innerHTML = `
                    <div class="flex items-center gap-2">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                        <span>${message}</span>
                    </div>
                `;
                document.body.appendChild(toast);

                setTimeout(() => {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateX(20px)';
                    toast.style.transition = 'all 0.3s ease';
                    setTimeout(() => toast.remove(), 300);
                }, 3000);
            }
        });
    </script>
@endpush