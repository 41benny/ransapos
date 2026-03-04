@extends('layouts.admin')

@section('title', 'Tambah Bundle')
@section('page-title', 'Tambah Bundle Baru')
@section('page-subtitle', 'Atur data bundle, ketersediaan POS, komponen bahan, dan harga per level')

@section('content')
    @php
        $oldOutletIds = collect(old('pos_outlet_ids', []))->map(fn($id) => (int) $id)->values()->all();
        $oldUserIds = collect(old('pos_user_ids', []))->map(fn($id) => (int) $id)->values()->all();
        $componentMetaById = collect($rawMaterials ?? [])->mapWithKeys(function ($raw) {
            return [
                (string) $raw->id => [
                    'unit' => (string) ($raw->unit ?? ''),
                    'purchase_price' => (float) ($raw->purchase_price ?? 0),
                ],
            ];
        })->all();
    @endphp

    <div class="max-w-6xl mx-auto w-full">
        @if(session('error'))
            <div class="mb-4 bg-red-50 text-red-700 p-4 rounded-lg border border-red-200">
                <i class="fas fa-exclamation-circle text-lg mr-2"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 bg-red-50 text-red-700 p-4 rounded-lg border border-red-200">
                <i class="fas fa-exclamation-triangle text-lg mr-2"></i>
                <span>Periksa kembali input bundle Anda.</span>
            </div>
        @endif

        <form action="{{ route('admin.products.store') }}" method="POST" id="bundleForm" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="bundle_mode" value="1">
            <input type="hidden" name="product_type" id="product_type" value="finished_good">

            <div class="card p-0 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50">
                    <h3 class="text-base font-bold text-gray-900">Informasi Bundle</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Bundle disimpan sebagai produk jadi dan dapat langsung dipakai
                        di POS.</p>
                </div>

                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                        <div>
                            <label class="form-label mb-2">Gambar Produk</label>
                            <div class="border border-gray-200 rounded-lg p-3 bg-white">
                                <div
                                    class="aspect-square rounded-lg bg-gray-100 border border-gray-200 overflow-hidden flex items-center justify-center relative">
                                    <img id="imagePreview" src="" alt="Preview" class="hidden w-full h-full object-cover">
                                    <div id="imagePlaceholder" class="text-gray-400 text-center">
                                        <i class="fas fa-image text-3xl"></i>
                                        <p class="text-[10px] mt-1">No Image</p>
                                    </div>
                                </div>
                                <label for="image" class="mt-3 btn btn-secondary w-full justify-center text-xs">
                                    <i class="fas fa-upload mr-1"></i> Upload
                                </label>
                                <input type="file" id="image" name="image" accept="image/*" class="hidden">
                                <p class="text-[10px] text-gray-400 mt-2 text-center">Maks 2MB.</p>
                                @error('image')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="lg:col-span-3 grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label for="name" class="form-label">
                                    Nama Bundle <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}" class="form-input"
                                    placeholder="Contoh: Paket Nasi + Es Teh" required>
                                @error('name')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="sku" class="form-label">
                                    SKU <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="sku" id="sku" value="{{ old('sku') }}" class="form-input"
                                    placeholder="Contoh: BUNDLE-001" required>
                                @error('sku')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="category_id" class="form-label">
                                    Kategori <span class="text-red-500">*</span>
                                </label>
                                <select name="category_id" id="category_id" class="form-input" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="unit" class="form-label">
                                    Unit <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="unit" id="unit" value="{{ old('unit', 'pcs') }}" class="form-input"
                                    placeholder="pcs" required>
                                @error('unit')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="purchase_price" class="form-label">
                                    Harga Modal <span class="text-red-500">*</span>
                                </label>
                                <input type="number" name="purchase_price" id="purchase_price"
                                    value="{{ old('purchase_price', 0) }}" class="form-input bg-gray-50 text-gray-500"
                                    placeholder="0" min="0" step="0.01" readonly required>
                                <p class="mt-1 text-xs text-gray-400">Otomatis dari total komponen.</p>
                                @error('purchase_price')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="min_stock" class="form-label">
                                    Stok Minimal
                                </label>
                                <input type="number" name="min_stock" id="min_stock" value="{{ old('min_stock', 0) }}"
                                    class="form-input" placeholder="0" min="0">
                                @error('min_stock')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 p-5 border border-gray-100 rounded-lg bg-gray-50/50">
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 mb-1">Informasi Produk</h4>
                            <p class="text-xs text-gray-500 mb-4">Pengaturan harga jual utama dan status.</p>

                            <div class="space-y-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="is_sellable" id="is_sellable" value="1" {{ old('is_sellable', data_get($defaults, 'is_sellable', true)) ? 'checked' : '' }}
                                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                    <span class="text-sm text-gray-700">Saya menjual bundle ini</span>
                                </label>

                                <div>
                                    <label for="selling_price" class="form-label">
                                        Harga Jual Reguler <span class="text-red-500">*</span>
                                    </label>
                                    <div class="flex">
                                        <span
                                            class="inline-flex items-center px-3 text-sm text-gray-600 bg-gray-100 border border-r-0 border-gray-300 rounded-l-md">Rp</span>
                                        <input type="number" name="selling_price" id="selling_price"
                                            value="{{ old('selling_price', 0) }}" class="form-input rounded-l-none flex-1"
                                            min="0" step="0.01" required>
                                    </div>
                                    @error('selling_price')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                    <span class="text-sm text-gray-700">Bundle aktif</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-sm font-bold text-gray-900 mb-1">Informasi POS</h4>
                            <p class="text-xs text-gray-500 mb-4">Tampilkan di outlet dan channel penjualan.</p>

                            <div class="space-y-3">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="is_pos_available" id="is_pos_available" value="1" {{ old('is_pos_available', data_get($defaults, 'is_pos_available', true)) ? 'checked' : '' }} class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                    <span class="text-sm text-gray-700">Tersedia di POS</span>
                                </label>

                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="is_online_order_available" value="1" {{ old('is_online_order_available', data_get($defaults, 'is_online_order_available', false)) ? 'checked' : '' }}
                                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                    <span class="text-sm text-gray-700">Tersedia di Online Order</span>
                                </label>

                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="is_available_all_outlets" id="is_available_all_outlets"
                                        value="1" {{ old('is_available_all_outlets', data_get($defaults, 'is_available_all_outlets', true)) ? 'checked' : '' }}
                                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                    <span class="text-sm text-gray-700">Tersedia di semua outlet</span>
                                </label>

                                <div id="outlet-selector-tools" class="pl-6 pt-1">
                                    <p class="text-xs text-gray-500" id="selected-outlet-count">0 outlet dipilih</p>
                                    <button type="button" id="open-outlet-modal"
                                        class="text-blue-600 hover:text-blue-700 text-xs font-medium mt-1">
                                        Pilih outlet manual
                                    </button>
                                </div>
                                <div id="posOutletHiddenInputs"></div>
                                @error('pos_outlet_ids')
                                    <p class="text-xs text-red-600 pl-6">{{ $message }}</p>
                                @enderror

                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="is_available_all_users" id="is_available_all_users"
                                        value="1" {{ old('is_available_all_users', data_get($defaults, 'is_available_all_users', true)) ? 'checked' : '' }}
                                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                    <span class="text-sm text-gray-700">Tersedia untuk semua pengguna</span>
                                </label>

                                <div id="user-selector-tools" class="pl-6 pt-1">
                                    <p class="text-xs text-gray-500" id="selected-user-count">0 pengguna dipilih</p>
                                    <button type="button" id="open-user-modal"
                                        class="text-blue-600 hover:text-blue-700 text-xs font-medium mt-1">
                                        Pilih pengguna manual
                                    </button>
                                </div>
                                <div id="posUserHiddenInputs"></div>
                                @error('pos_user_ids')
                                    <p class="text-xs text-red-600 pl-6">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <div class="flex items-center gap-1 bg-gray-50 border-b border-gray-200 px-2 pt-2" id="bundle-tabs">
                            <button type="button"
                                class="bundle-tab-btn px-4 py-2 rounded-t-lg text-sm font-medium bg-white text-blue-600 border-t border-x border-gray-200 relative top-[1px]"
                                data-target="tab-components">Bundle/Bahan</button>
                            <button type="button"
                                class="bundle-tab-btn px-4 py-2 rounded-t-lg text-sm font-medium text-gray-500 hover:text-gray-700"
                                data-target="tab-pricing">Pengaturan Harga</button>
                            <button type="button"
                                class="bundle-tab-btn px-4 py-2 rounded-t-lg text-sm font-medium text-gray-500 hover:text-gray-700"
                                data-target="tab-extra">Info Tambahan</button>
                        </div>

                        <div class="p-5 bg-white">
                            <div id="tab-components" class="bundle-tab-panel">
                                <div class="table-container mb-4">
                                    <table class="table-modern" id="components-table">
                                        <thead>
                                            <tr>
                                                <th>Item</th>
                                                <th class="w-24">Qty</th>
                                                <th class="w-24">Satuan</th>
                                                <th class="w-32 text-right">Biaya</th>
                                                <th class="w-16 text-center"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="bundle-components-body">
                                            @php
                                                $oldComponents = old('bundle_components', [['component_product_id' => '', 'quantity' => '1', 'uom' => '']]);
                                            @endphp
                                            @foreach($oldComponents as $index => $component)
                                                <tr class="component-row">
                                                    <td class="p-2">
                                                        <select name="bundle_components[{{ $index }}][component_product_id]"
                                                            class="component-product form-input text-sm">
                                                            <option value="">Ketik nama bahan...</option>
                                                            @foreach($rawMaterials as $raw)
                                                                <option value="{{ $raw->id }}" data-unit="{{ $raw->unit }}"
                                                                    data-purchase-price="{{ (float) ($raw->purchase_price ?? 0) }}"
                                                                    {{ (string) ($component['component_product_id'] ?? '') === (string) $raw->id ? 'selected' : '' }}>
                                                                    {{ $raw->name }} ({{ $raw->sku }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td class="p-2">
                                                        <input type="number" name="bundle_components[{{ $index }}][quantity]"
                                                            value="{{ $component['quantity'] ?? '' }}" min="0.0001"
                                                            step="0.0001" class="component-quantity form-input text-sm"
                                                            placeholder="0">
                                                    </td>
                                                    <td class="p-2">
                                                        <input type="text" name="bundle_components[{{ $index }}][uom]"
                                                            value="{{ $component['uom'] ?? '' }}"
                                                            class="component-uom form-input text-sm" placeholder="pcs">
                                                    </td>
                                                    <td
                                                        class="p-2 text-right font-medium text-gray-700 component-row-cost-value text-sm">
                                                        Rp 0</td>
                                                    <td class="p-2 text-center">
                                                        <button type="button"
                                                            class="remove-component-row text-red-500 hover:text-red-700 text-sm">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                @error('bundle_components')
                                    <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                                @enderror

                                <div class="flex items-center justify-between mt-4">
                                    <div class="flex items-center gap-2">
                                        <button type="button" id="add-component-row" class="btn btn-secondary btn-sm">
                                            <i class="fas fa-plus mr-1"></i> Tambah Item
                                        </button>
                                        <span class="text-[10px] text-gray-400">Minimal 1 bahan.</span>
                                    </div>
                                    <div class="px-4 py-2 border border-gray-100 rounded bg-gray-50">
                                        <span class="text-xs text-gray-500 mr-2">Total Harga Modal:</span>
                                        <span id="components-total-cost" class="text-sm font-bold text-gray-900">Rp
                                            0,00</span>
                                    </div>
                                </div>
                            </div>

                            <div id="tab-pricing" class="bundle-tab-panel hidden">
                                <!-- Info Banner -->
                                <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                    <p class="text-sm text-gray-700">
                                        <i class="fas fa-info-circle text-blue-600 mr-1"></i>
                                        <strong>Pengaturan Harga Per Outlet:</strong><br>
                                        Pilih outlet yang ingin diatur harga khusus. Outlet yang tidak dipilih akan
                                        menggunakan harga default.
                                    </p>
                                </div>

                                @foreach($priceLevels as $levelKey => $levelLabel)
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
                                                            value="{{ old('price_levels.' . $levelKey . '.default', $levelKey === 'regular' ? old('selling_price', 0) : '') }}"
                                                            min="0" step="0.01" @if($levelKey === 'regular') required @endif
                                                            class="form-input rounded-l-none flex-1 text-sm @if($levelKey === 'regular') bg-blue-50/30 border-blue-200 font-semibold @endif price-default-input"
                                                            data-level="{{ $levelKey }}" placeholder="0">
                                                    </div>
                                                    <button type="button"
                                                        class="btn btn-secondary btn-sm copy-to-all-outlets-btn"
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
                                                            <span
                                                                class="outlet-selected-count badge badge-sm bg-gray-100 text-gray-500"
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
                                                            <div class="outlet-price-row border-b border-gray-100 last:border-0 hover:bg-gray-50 transition-colors"
                                                                data-outlet-id="{{ $outlet->id }}" data-level="{{ $levelKey }}">
                                                                <label class="flex items-center gap-3 px-3 py-2.5 cursor-pointer">
                                                                    <input type="checkbox"
                                                                        class="outlet-checkbox w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                                                        data-level="{{ $levelKey }}"
                                                                        data-outlet-id="{{ $outlet->id }}">
                                                                    <div class="flex-1 flex items-center justify-between gap-3">
                                                                        <span
                                                                            class="text-sm text-gray-700 font-medium">{{ $outlet->name }}</span>
                                                                        <div
                                                                            class="flex items-center gap-2 outlet-price-input-wrapper hidden">
                                                                            <div class="flex items-center">
                                                                                <span
                                                                                    class="inline-flex items-center px-2 text-xs text-gray-500 bg-gray-50 border border-r-0 border-gray-300 rounded-l-md">Rp</span>
                                                                                <input type="number"
                                                                                    name="price_levels[{{ $levelKey }}][outlets][{{ $outlet->id }}]"
                                                                                    value="{{ old('price_levels.' . $levelKey . '.outlets.' . $outlet->id, '') }}"
                                                                                    min="0" step="0.01" placeholder="0"
                                                                                    class="form-input rounded-l-none text-sm w-32 outlet-price-input"
                                                                                    data-level="{{ $levelKey }}"
                                                                                    data-outlet-id="{{ $outlet->id }}" disabled>
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
                                                    Centang outlet untuk mengatur harga khusus. Kosongkan input untuk
                                                    menggunakan harga default.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div id="tab-extra" class="bundle-tab-panel hidden">
                                <label for="description" class="form-label mb-2">Deskripsi Bundle</label>
                                <textarea name="description" id="description" rows="5" class="form-input"
                                    placeholder="Catatan tambahan untuk bundle ini...">{{ old('description') }}</textarea>
                                <p class="text-xs text-gray-400 mt-2">Catatan ini juga akan dipakai sebagai notes BOM awal.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-end gap-3">
                    <a href="{{ session('product_index_url', route('admin.products.index')) }}"
                        class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Simpan Bundle
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Modal Templates (Outlet & User) -->
    <div id="outletModal"
        class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-sm p-4 flex items-center justify-center">
        <div class="ui-card bg-white rounded-lg shadow-xl border border-gray-100 w-full max-w-md">
            <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between bg-gray-50 rounded-t-lg">
                <h3 class="text-sm font-bold text-gray-900">Pilih Outlet</h3>
                <button type="button" class="close-modal text-gray-400 hover:text-gray-600" data-target="outletModal"><i
                        class="fas fa-times"></i></button>
            </div>
            <div class="p-2 max-h-[60vh] overflow-y-auto">
                @foreach($outlets as $outlet)
                    <label
                        class="flex items-center gap-3 p-3 rounded hover:bg-gray-50 cursor-pointer border-b border-gray-50 last:border-0">
                        <input type="checkbox"
                            class="outlet-option w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500"
                            value="{{ $outlet->id }}">
                        <span class="text-sm text-gray-700">{{ $outlet->name }}</span>
                    </label>
                @endforeach
            </div>
            <div class="px-5 py-3 border-t border-gray-100 flex justify-end gap-2 bg-gray-50 rounded-b-lg">
                <button type="button" class="close-modal btn btn-secondary btn-sm" data-target="outletModal">Tutup</button>
                <button type="button" class="close-modal btn btn-primary btn-sm" data-target="outletModal">Simpan</button>
            </div>
        </div>
    </div>

    <div id="userModal"
        class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-sm p-4 flex items-center justify-center">
        <div class="ui-card bg-white rounded-lg shadow-xl border border-gray-100 w-full max-w-md">
            <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between bg-gray-50 rounded-t-lg">
                <h3 class="text-sm font-bold text-gray-900">Pilih Pengguna</h3>
                <button type="button" class="close-modal text-gray-400 hover:text-gray-600" data-target="userModal"><i
                        class="fas fa-times"></i></button>
            </div>
            <div class="p-2 max-h-[60vh] overflow-y-auto">
                @foreach($posUsers as $user)
                    <label
                        class="flex items-start gap-3 p-3 rounded hover:bg-gray-50 cursor-pointer border-b border-gray-50 last:border-0">
                        <input type="checkbox"
                            class="user-option w-4 h-4 mt-1 text-blue-600 rounded border-gray-300 focus:ring-blue-500"
                            value="{{ $user->id }}">
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ $user->name }}</p>
                            <p class="text-[10px] text-gray-400">
                                {{ optional($user->role)->name ?? '-' }}
                                @if(optional($user->outlet)->name)
                                    • {{ $user->outlet->name }}
                                @endif
                            </p>
                        </div>
                    </label>
                @endforeach
            </div>
            <div class="px-5 py-3 border-t border-gray-100 flex justify-end gap-2 bg-gray-50 rounded-b-lg">
                <button type="button" class="close-modal btn btn-secondary btn-sm" data-target="userModal">Tutup</button>
                <button type="button" class="close-modal btn btn-primary btn-sm" data-target="userModal">Simpan</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <style>
        /* Aggressive Reset for Autocomplete Look */
        .ts-control {
            border-radius: 0.5rem;
            padding: 0.5rem 0.75rem !important;
            border-color: #e5e7eb;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            min-height: 42px;
            background-image: none !important;
            background-repeat: no-repeat !important;
            background-position: right 0.75rem center !important;
            background-size: 16px 12px !important;
            padding-right: 0.75rem !important;
        }
        /* Hide all arrows/carets */
        .ts-wrapper.single .ts-control::after { display: none !important; content: none !important; }
        .ts-wrapper.single .ts-control .item { padding-right: 0 !important; }
        
        .ts-wrapper.focus .ts-control {
            border-color: #3b82f6;
            box-shadow: 0 0 0 1px #3b82f6;
        }
        
        .ts-dropdown {
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            border-color: #e5e7eb;
            z-index: 99999; /* Max Z-Index */
            margin-top: 4px;
        }
        .ts-dropdown .option { padding: 0.5rem 0.75rem; }
        .ts-dropdown .active { background-color: #eff6ff; color: #1e40af; }

        /* Ensure typed search text in TomSelect is always visible */
        .ts-control > input {
            color: #111827 !important;
            -webkit-text-fill-color: #111827 !important;
            opacity: 1 !important;
        }
        
        /* Hide dropdown if no query (extra safeguard) */
        .ts-wrapper.no-search .ts-dropdown { display: none !important; }
    </style>
    <select id="product-options-source" class="hidden">
        <option value="">Ketik nama bahan...</option>
        @foreach($rawMaterials as $raw)
            <option value="{{ $raw->id }}" data-unit="{{ $raw->unit }}"
                data-purchase-price="{{ (float) ($raw->purchase_price ?? 0) }}">
                {{ $raw->name }} ({{ $raw->sku }})
            </option>
        @endforeach
    </select>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const componentMetaById = @json($componentMetaById);

            const tabs = document.querySelectorAll('.bundle-tab-btn');
            const panels = document.querySelectorAll('.bundle-tab-panel');

            function activateTab(targetId) {
                panels.forEach(panel => panel.classList.toggle('hidden', panel.id !== targetId));
                tabs.forEach(tab => {
                    const isActive = tab.dataset.target === targetId;
                    if(isActive) {
                        tab.classList.remove('text-gray-500', 'hover:text-gray-700', 'bg-transparent');
                        tab.classList.add('bg-white', 'text-blue-600', 'border-t', 'border-x', 'border-gray-200');
                        tab.style.top = '1px';
                    } else {
                        tab.classList.add('text-gray-500', 'hover:text-gray-700', 'bg-transparent');
                        tab.classList.remove('bg-white', 'text-blue-600', 'border-t', 'border-x', 'border-gray-200');
                        tab.style.top = '0px';
                    }
                });
            }

            tabs.forEach(tab => {
                tab.addEventListener('click', function () {
                    activateTab(tab.dataset.target);
                });
            });

            // Image Preview logic
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

            // Outlet & User Selection Logic
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
                if (isAllSelected) return;
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
                userCountText.textContent = `${selectedUserIds.size} pengguna dipilih`;
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

            openOutletModalBtn.addEventListener('click', () => openModal(outletModal));
            openUserModalBtn.addEventListener('click', () => openModal(userModal));

            document.querySelectorAll('.close-modal').forEach(button => {
                button.addEventListener('click', function () {
                    const target = document.getElementById(button.dataset.target);
                    if (target) closeModal(target);
                });
            });

            // Close modal on cloud click
            [outletModal, userModal].forEach(modal => {
                modal.addEventListener('click', function(event) {
                    if(event.target === modal) closeModal(modal);
                });
            });

            outletOptions.forEach(option => {
                option.addEventListener('change', function () {
                    const value = Number(option.value);
                    if (option.checked) selectedOutletIds.add(value);
                    else selectedOutletIds.delete(value);
                });
            });

            userOptions.forEach(option => {
                option.addEventListener('change', function () {
                    const value = Number(option.value);
                    if (option.checked) selectedUserIds.add(value);
                    else selectedUserIds.delete(value);
                });
            });

            allOutletsCheckbox.addEventListener('change', refreshAvailabilitySections);
            allUsersCheckbox.addEventListener('change', refreshAvailabilitySections);

            refreshSelectionTexts();
            refreshAvailabilitySections();

            // Price Sync between selling_price and price_levels[regular][default]
            const sellingPriceInput = document.getElementById('selling_price');
            const regularPriceDefaultInput = document.querySelector('input[name="price_levels[regular][default]"]');

            function syncPrice(from, to) {
                if (!from || !to) return;
                to.value = from.value;
            }

            if (sellingPriceInput && regularPriceDefaultInput) {
                sellingPriceInput.addEventListener('input', () => syncPrice(sellingPriceInput, regularPriceDefaultInput));
                regularPriceDefaultInput.addEventListener('input', () => syncPrice(regularPriceDefaultInput, sellingPriceInput));
            }

            // Dynamic Component Rows
            const componentBody = document.getElementById('bundle-components-body');
            const addRowBtn = document.getElementById('add-component-row');
            const purchasePriceInput = document.getElementById('purchase_price');
            const componentsTotalCostLabel = document.getElementById('components-total-cost');
            let componentIndex = componentBody.querySelectorAll('.component-row').length;

            const optionsSource = document.getElementById('product-options-source');
            function makeOptionsHtml() {
                return optionsSource.innerHTML;
            }

            function parseNumber(value) {
                const parsed = parseFloat(value);
                return Number.isFinite(parsed) ? parsed : 0;
            }

            function formatCurrency(value) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                }).format(value);
            }

            function getSelectedComponentOption(selectElement) {
                if (!selectElement) return null;

                let selectedValue = '';
                if (selectElement.tomselect) {
                    selectedValue = String(selectElement.tomselect.getValue() || '');
                }

                if (!selectedValue) {
                    selectedValue = String(selectElement.value || '');
                }

                if (!selectedValue) {
                    if (selectElement.selectedIndex < 0) return null;
                    return selectElement.options[selectElement.selectedIndex] || null;
                }

                return Array.from(selectElement.options).find(option => String(option.value) === selectedValue) || null;
            }

            function getSelectedComponentValue(selectElement) {
                if (!selectElement) return '';
                if (selectElement.tomselect) {
                    return String(selectElement.tomselect.getValue() || '');
                }
                return String(selectElement.value || '');
            }

            function getComponentPurchasePrice(selectElement) {
                const selectedOption = getSelectedComponentOption(selectElement);
                let price = 0;

                if (selectedOption) {
                    price = parseNumber(selectedOption.dataset.purchasePrice ?? selectedOption.getAttribute('data-purchase-price'));
                }

                if (price <= 0) {
                    const selectedValue = getSelectedComponentValue(selectElement);
                    if (selectedValue && componentMetaById[selectedValue]) {
                        price = parseNumber(componentMetaById[selectedValue].purchase_price);
                    }
                }

                return price;
            }

            function getComponentUnit(selectElement) {
                const selectedOption = getSelectedComponentOption(selectElement);
                let unit = '';

                if (selectedOption) {
                    unit = selectedOption.dataset.unit ?? selectedOption.getAttribute('data-unit') ?? '';
                }

                if (!unit) {
                    const selectedValue = getSelectedComponentValue(selectElement);
                    if (selectedValue && componentMetaById[selectedValue]) {
                        unit = componentMetaById[selectedValue].unit || '';
                    }
                }

                return unit;
            }

            function recalculateComponentCosts() {
                let totalCost = 0;
                const rows = componentBody.querySelectorAll('.component-row');
                rows.forEach(function (row) {
                    const select = row.querySelector('.component-product');
                    const quantityInput = row.querySelector('.component-quantity');
                    const rowCostLabel = row.querySelector('.component-row-cost-value');
                    const quantity = parseNumber(quantityInput ? quantityInput.value : 0);
                    const purchasePrice = getComponentPurchasePrice(select);
                    const rowCost = quantity * purchasePrice;
                    if (rowCostLabel) rowCostLabel.textContent = formatCurrency(rowCost);
                    totalCost += rowCost;
                });

                if (componentsTotalCostLabel) componentsTotalCostLabel.textContent = formatCurrency(totalCost);
                if (purchasePriceInput) purchasePriceInput.value = totalCost.toFixed(2);
            }

        function initTomSelect(selectElement) {
            if (typeof TomSelect === 'undefined') {
                console.error('TomSelect library not loaded!');
                return;
            }
            if (selectElement.tomselect) return;
            try {
                new TomSelect(selectElement, {
                    create: false,
                    dropdownParent: 'body', 
                    sortField: { field: "text", direction: "asc" },
                    placeholder: 'Ketik nama bahan...',
                    openOnFocus: false,
                    maxOptions: 50,
                    onChange: function(value) {
                        const row = selectElement.closest('.component-row');
                        if(row) {
                            const unitInput = row.querySelector('.component-uom');
                            const quantityInput = row.querySelector('.component-quantity');
                            const unit = getComponentUnit(selectElement);
                            if(unitInput && !unitInput.value && unit) {
                                unitInput.value = unit;
                            }
                            if (quantityInput && parseNumber(quantityInput.value) <= 0) {
                                quantityInput.value = '1';
                            }
                            recalculateComponentCosts();
                        }
                    }
                });
            } catch (e) {
                console.error('TomSelect initialization failed:', e);
            }
        }

            function bindComponentRowEvents(row) {
                const select = row.querySelector('.component-product');
                const unitInput = row.querySelector('.component-uom');
                const quantityInput = row.querySelector('.component-quantity');

                if (!select) return;

                initTomSelect(select);

                select.addEventListener('change', function () {
                    const unit = getComponentUnit(select);
                    const quantityValue = parseNumber(quantityInput ? quantityInput.value : 0);
                    if (unitInput && !unitInput.value && unit) unitInput.value = unit;
                    if (quantityInput && quantityValue <= 0) quantityInput.value = '1';
                    recalculateComponentCosts();
                });

                if (quantityInput) {
                    quantityInput.addEventListener('input', recalculateComponentCosts);
                }
            }

            function addComponentRow() {
                const row = document.createElement('tr');
                row.className = 'component-row';
                row.innerHTML = `
                    <td class="p-2">
                        <select name="bundle_components[${componentIndex}][component_product_id]" class="component-product form-input text-sm">
                            ${makeOptionsHtml()}
                        </select>
                    </td>
                    <td class="p-2">
                        <input type="number" name="bundle_components[${componentIndex}][quantity]" min="0.0001" step="0.0001"
                            class="component-quantity form-input text-sm" placeholder="0" value="1">
                    </td>
                    <td class="p-2">
                        <input type="text" name="bundle_components[${componentIndex}][uom]"
                            class="component-uom form-input text-sm" placeholder="pcs">
                    </td>
                    <td class="p-2 text-right font-medium text-gray-700 component-row-cost-value text-sm">Rp 0</td>
                    <td class="p-2 text-center">
                        <button type="button" class="remove-component-row text-red-500 hover:text-red-700 text-sm"><i class="fas fa-trash"></i></button>
                    </td>
                `;
                componentBody.appendChild(row);
                bindComponentRowEvents(row);
                recalculateComponentCosts();
                componentIndex += 1;
            }

            if (addRowBtn) addRowBtn.addEventListener('click', addComponentRow);

            componentBody.querySelectorAll('.component-row').forEach(bindComponentRowEvents);
            recalculateComponentCosts();

            componentBody.addEventListener('click', function (event) {
                const removeBtn = event.target.closest('.remove-component-row');
                if (!removeBtn) return;
                const rows = componentBody.querySelectorAll('.component-row');
                if (rows.length <= 1) {
                    alert('Minimal harus ada 1 komponen bahan.');
                    return;
                }
                if(confirm('Hapus baris ini?')) {
                     const row = removeBtn.closest('.component-row');
                     const select = row.querySelector('.component-product');
                     if(select && select.tomselect) select.tomselect.destroy();
                     row.remove();
                     recalculateComponentCosts();
                }
            });

            // ============================================
            // OUTLET-SPECIFIC PRICING LOGIC
            // ============================================

            // Toggle outlet selection section
            document.querySelectorAll('.toggle-outlet-selection').forEach(btn => {
                btn.addEventListener('click', function() {
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
                checkbox.addEventListener('change', function() {
                    const level = this.dataset.level;
                    const outletId = this.dataset.outletId;
                    const row = this.closest('.outlet-price-row');
                    const inputWrapper = row.querySelector('.outlet-price-input-wrapper');
                    const priceInput = row.querySelector('.outlet-price-input');

                    if (this.checked) {
                        // Show input when checked
                        inputWrapper.classList.remove('hidden');
                        priceInput.disabled = false;
                        priceInput.focus();
                    } else {
                        // Hide input and clear value when unchecked
                        inputWrapper.classList.add('hidden');
                        priceInput.disabled = true;
                        priceInput.value = '';
                    }

                    updateOutletSelectedCount(level);
                });
            });

            // Select all outlets for a price level
            document.querySelectorAll('.select-all-outlets').forEach(btn => {
                btn.addEventListener('click', function() {
                    const level = this.dataset.level;
                    document.querySelectorAll(`.outlet-checkbox[data-level="${level}"]`).forEach(checkbox => {
                        if (!checkbox.checked) {
                            checkbox.checked = true;
                            checkbox.dispatchEvent(new Event('change'));
                        }
                    });
                });
            });

            // Deselect all outlets for a price level
            document.querySelectorAll('.deselect-all-outlets').forEach(btn => {
                btn.addEventListener('click', function() {
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
                btn.addEventListener('click', function() {
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

            // Copy from default price (individual outlet)
            document.querySelectorAll('.copy-from-default-btn').forEach(btn => {
                btn.addEventListener('click', function() {
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

            // Initialize counts
            document.querySelectorAll('.outlet-selected-count').forEach(badge => {
                const level = badge.dataset.level;
                updateOutletSelectedCount(level);
            });

            // Toast notification helper
            function showToast(message, type = 'success') {
                const toast = document.createElement('div');
                toast.className = `fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg ${
                    type === 'success' ? 'bg-green-500' : 'bg-red-500'
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

            // Restore checked state from old values on page load (if there's validation error)
            @if(old('price_levels'))
                @foreach($priceLevels as $levelKey => $levelLabel)
                    @php
                        $oldOutletPrices = old('price_levels.' . $levelKey . '.outlets', []);
                    @endphp
                    @if(!empty($oldOutletPrices) && is_array($oldOutletPrices))
                        @foreach($oldOutletPrices as $outletId => $price)
                            @if($price)
                                (() => {
                                    const checkbox = document.querySelector(`.outlet-checkbox[data-level="{{ $levelKey }}"][data-outlet-id="{{ $outletId }}"]`);
                                    if (checkbox) {
                                        checkbox.checked = true;
                                        checkbox.dispatchEvent(new Event('change'));
                                    }
                                })();
                            @endif
                        @endforeach
                    @endif
                @endforeach
            @endif

            const form = document.getElementById('bundleForm');
            form.addEventListener('submit', function (event) {
                if (!allOutletsCheckbox.checked && selectedOutletIds.size === 0) {
                    event.preventDefault();
                    alert('Pilih minimal 1 outlet atau centang tersedia di semua outlet.');
                    return;
                }
                if (!allUsersCheckbox.checked && selectedUserIds.size === 0) {
                    event.preventDefault();
                    alert('Pilih minimal 1 pengguna atau centang tersedia semua pengguna.');
                    return;
                }
            });
        });
    </script>
@endpush
