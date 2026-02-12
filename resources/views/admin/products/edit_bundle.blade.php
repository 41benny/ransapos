@extends('layouts.admin')

@section('title', 'Edit Bundle')
@section('page-title', 'Edit Bundle')
@section('page-subtitle', 'Perbarui data bundle, komponen bahan, dan harga')

@section('content')
    @php
        $oldOutletIds = collect(old('pos_outlet_ids', $product->pos_outlet_ids ?? []))->map(fn($id) => (int) $id)->values()->all();
        $oldUserIds = collect(old('pos_user_ids', $product->pos_user_ids ?? []))->map(fn($id) => (int) $id)->values()->all();

        // Siapkan data komponen untuk JS jika ada error validasi atau dari DB
        // Struktur yang diharapkan JS: { id: 1, name: 'Bahan A', unit: 'gr', cost: 500, quantity: 2 }
        $existingComponents = [];
        if (old('components')) {
            // Jika ada input old (validasi error), gunakan itu
            // Perlu mapping manual karena old('components') strukturnya array of inputs
            // Ini agak kompleks parsingnya, simplified: kita abaikan old components detail kompleks saat error dulu 
            // atau kita ambil dari session jika memungkinkan. 
            // Untuk saat ini kita ambil dari database jika old kosong.
        }

        // Ambil dari DB BOM details
        $bomDetails = $product->bomHeader->bomDetails ?? collect([]);
        $componentData = $bomDetails->map(function ($detail) {
            return [
                'id' => $detail->product_id,
                'name' => $detail->product->name ?? 'Unknown',
                'unit' => $detail->product->unit ?? '-',
                'cost' => $detail->product->purchase_price ?? 0,
                'quantity' => (float) $detail->quantity
            ];
        });
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

        <form action="{{ route('admin.products.update', $product) }}" method="POST" id="bundleForm"
            enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <input type="hidden" name="bundle_mode" value="1">
            <input type="hidden" name="product_type" id="product_type" value="finished_good">

            <div class="card p-0 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50">
                    <h3 class="text-base font-bold text-gray-900">Informasi Bundle</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Edit komponen dan informasi bundle.</p>
                </div>

                <div class="p-6 space-y-6">
                    <!-- Image Upload Section -->
                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                        <div>
                            <label class="form-label mb-2">Gambar Produk</label>
                            <div class="border border-gray-200 rounded-lg p-3 bg-white">
                                <div
                                    class="aspect-square rounded-lg bg-gray-100 border border-gray-200 overflow-hidden flex items-center justify-center relative">
                                    <img id="imagePreview"
                                        src="{{ $product->image_path ? asset('storage/' . $product->image_path) : '' }}"
                                        alt="Preview"
                                        class="{{ $product->image_path ? '' : 'hidden' }} w-full h-full object-cover">
                                    <div id="imagePlaceholder"
                                        class="{{ $product->image_path ? 'hidden' : '' }} text-gray-400 text-center">
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

                        <div class="lg:col-span-3 space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="name" class="form-label">
                                        Nama Bundle <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="name" id="name" value="{{ old('name', $product->name) }}"
                                        class="form-input" placeholder="Contoh: Paket Nasi + Es Teh" required>
                                    @error('name')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="sku" class="form-label">
                                        SKU <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="sku" id="sku" value="{{ old('sku', $product->sku) }}"
                                        class="form-input" placeholder="Contoh: BUNDLE-001" required>
                                    @error('sku')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="category_id" class="form-label">
                                        Kategori <span class="text-red-500">*</span>
                                    </label>
                                    <select name="category_id" id="category_id" class="form-input" required>
                                        <option value="">-- Pilih Kategori --</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
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
                                    <input type="text" name="unit" id="unit" value="{{ old('unit', $product->unit) }}"
                                        class="form-input" placeholder="pcs" required>
                                    @error('unit')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label for="purchase_price" class="form-label">
                                        Harga Modal <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" name="purchase_price" id="purchase_price"
                                        value="{{ old('purchase_price', $product->purchase_price) }}"
                                        class="form-input bg-gray-50 text-gray-500" placeholder="0" min="0" step="0.01"
                                        readonly required>
                                    <p class="mt-1 text-xs text-gray-400">Otomatis dari total komponen.</p>
                                    @error('purchase_price')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="selling_price" class="form-label">
                                        Harga Jual Reguler <span class="text-red-500">*</span>
                                    </label>
                                    <div class="flex">
                                        <span
                                            class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                            Rp
                                        </span>
                                        <input type="number" name="selling_price" id="selling_price"
                                            value="{{ old('selling_price', $product->selling_price) }}"
                                            class="form-input rounded-l-none" placeholder="0" min="0" step="0.01" required>
                                    </div>
                                    @error('selling_price')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="min_stock" class="form-label">
                                        Stok Minimal
                                    </label>
                                    <input type="number" name="min_stock" id="min_stock"
                                        value="{{ old('min_stock', $product->min_stock) }}" class="form-input"
                                        placeholder="0" min="0">
                                    @error('min_stock')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 p-5 border border-gray-200 rounded-xl bg-gray-50/60">
                        <div>
                            <h4 class="text-base font-semibold text-gray-900">Informasi Produk</h4>
                            <p class="text-xs text-gray-500 mt-1">Atur status jual bundle ini</p>

                            <div class="mt-4 space-y-4">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" name="is_sellable" id="is_sellable" value="1" {{ old('is_sellable', $product->is_sellable ?? true) ? 'checked' : '' }}
                                        class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                    <span class="text-sm text-gray-700">Saya menjual bundle ini</span>
                                </label>

                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }}
                                        class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                    <span class="text-sm text-gray-700">Bundle aktif</span>
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
                                    <span class="text-sm text-gray-700">Tampil di menu POS</span>
                                </label>

                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" name="is_available_all_outlets" id="is_available_all_outlets"
                                        value="1" {{ old('is_available_all_outlets', $product->is_available_all_outlets ?? true) ? 'checked' : '' }}
                                        class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                    <span class="text-sm text-gray-700">Tersedia di SEMUA Outlet</span>
                                </label>
                                <div id="outlet-selector-wrap"
                                    class="{{ old('is_available_all_outlets', $product->is_available_all_outlets ?? true) ? 'hidden' : '' }} pt-1 pl-6">
                                    <button type="button"
                                        onclick="document.getElementById('outletModal').classList.remove('hidden')"
                                        class="text-xs text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1">
                                        <i class="fas fa-store"></i> Pilih Outlet Spesifik
                                        <span class="bg-blue-100 text-blue-800 px-1.5 py-0.5 rounded-full text-[10px]"
                                            id="outlet-count">
                                            {{ count($oldOutletIds) }}
                                        </span>
                                    </button>
                                    <input type="hidden" name="pos_outlet_ids" id="pos_outlet_ids_input"
                                        value="{{ implode(',', $oldOutletIds) }}">
                                </div>

                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" name="is_available_all_users" id="is_available_all_users"
                                        value="1" {{ old('is_available_all_users', $product->is_available_all_users ?? true) ? 'checked' : '' }}
                                        class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                    <span class="text-sm text-gray-700">Tersedia untuk SEMUA Akun POS</span>
                                </label>
                                <div id="user-selector-wrap"
                                    class="{{ old('is_available_all_users', $product->is_available_all_users ?? true) ? 'hidden' : '' }} pt-1 pl-6">
                                    <button type="button"
                                        onclick="document.getElementById('userModal').classList.remove('hidden')"
                                        class="text-xs text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1">
                                        <i class="fas fa-users"></i> Pilih User Spesifik
                                        <span class="bg-blue-100 text-blue-800 px-1.5 py-0.5 rounded-full text-[10px]"
                                            id="user-count">
                                            {{ count($oldUserIds) }}
                                        </span>
                                    </button>
                                    <input type="hidden" name="pos_user_ids" id="pos_user_ids_input"
                                        value="{{ implode(',', $oldUserIds) }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs Navigation -->
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex gap-6" aria-label="Tabs">
                            <button type="button" data-target="tab-panel-components"
                                class="bundle-tab-btn active border-b-2 border-blue-500 py-4 px-1 text-sm font-medium text-blue-600 transition-colors duration-200 relative top-[1px] bg-white border-t border-x border-gray-200 rounded-t-lg">
                                <i class="fas fa-cubes mr-2"></i>Bundle / Bahan
                            </button>
                            <button type="button" data-target="tab-panel-prices"
                                class="bundle-tab-btn border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700 transition-colors duration-200">
                                <i class="fas fa-tags mr-2"></i>Pengaturan Harga
                            </button>
                            <button type="button" data-target="tab-panel-extra"
                                class="bundle-tab-btn border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700 transition-colors duration-200">
                                <i class="fas fa-info-circle mr-2"></i>Info Tambahan
                            </button>
                        </nav>
                    </div>

                    <!-- Tab Components -->
                    <div id="tab-panel-components" class="bundle-tab-panel space-y-4 pt-2">
                        <div class="flex justify-between items-center mb-2">
                            <div>
                                <h4 class="text-sm font-bold text-gray-900">Komponen Bundle</h4>
                                <p class="text-xs text-gray-500">Tambahkan bahan baku atau produk yang membentuk bundle ini.
                                </p>
                            </div>
                            <button type="button"
                                onclick="document.getElementById('materialModal').classList.remove('hidden')"
                                class="btn btn-primary btn-sm">
                                <i class="fas fa-plus mr-1"></i> Tambah Bahan
                            </button>
                        </div>

                        <div class="overflow-x-auto border border-gray-200 rounded-lg">
                            <table class="table-modern" id="components-table">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="w-10 text-center">#</th>
                                        <th>Nama Bahan/Produk</th>
                                        <th class="w-32 text-center">Unit</th>
                                        <th class="w-40 text-right">Harga Modal</th>
                                        <th class="w-32 text-center">Qty</th>
                                        <th class="w-40 text-right">Subtotal</th>
                                        <th class="w-20 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="components-list" class="bg-white divide-y divide-gray-100">
                                    <tr id="empty-row" class="hidden">
                                        <td colspan="7" class="text-center py-6 text-gray-400">
                                            Belum ada komponen ditambahkan.
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="bg-gray-50 font-bold text-gray-800">
                                    <tr>
                                        <td colspan="5" class="text-right px-4 py-3">Total Modal Bundle:</td>
                                        <td class="text-right px-4 py-3" id="total-cost-display">Rp 0</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div id="hidden-inputs-container"></div>
                    </div>

                    <!-- Tab Prices -->
                    <div id="tab-panel-prices" class="bundle-tab-panel hidden space-y-6 pt-2">
                        <!-- Info Banner -->
                        <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <p class="text-sm text-gray-700">
                                <i class="fas fa-info-circle text-blue-600 mr-1"></i>
                                <strong>Pengaturan Harga Per Outlet:</strong><br>
                                Pilih outlet yang ingin diatur harga khusus. Outlet yang tidak dipilih akan menggunakan harga default.
                            </p>
                        </div>

                        @foreach($priceLevels as $levelKey => $levelLabel)
                            @php
                                $priceData = data_get($product->price_levels, $levelKey);
                                $defaultPrice = is_array($priceData) ? ($priceData['default'] ?? 0) : $priceData;
                                $outletPrices = is_array($priceData) ? ($priceData['outlets'] ?? []) : [];
                            @endphp
                            <div class="mb-6 border border-gray-200 rounded-lg overflow-hidden">
                                <!-- Header Price Level -->
                                <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                                    <h4 class="text-sm font-bold text-gray-900">
                                        <i class="fas fa-tag text-gray-500 mr-2"></i>{{ ucfirst($levelLabel) }}
                                    </h4>
                                    <button 
                                        type="button" 
                                        class="text-xs text-blue-600 hover:text-blue-700 font-medium toggle-outlet-selection"
                                        data-level="{{ $levelKey }}"
                                    >
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
                                                <span class="inline-flex items-center px-3 text-xs text-gray-600 bg-gray-100 border border-r-0 border-gray-300 rounded-l-md">Rp</span>
                                                <input
                                                    type="number"
                                                    name="price_levels[{{ $levelKey }}][default]"
                                                    value="{{ old("price_levels.$levelKey.default", $defaultPrice == 0 ? '' : $defaultPrice) }}"
                                                    min="0"
                                                    step="0.01"
                                                    @if($levelKey === 'regular') required @endif
                                                    class="form-input rounded-l-none flex-1 text-sm @if($levelKey === 'regular') bg-blue-50/30 border-blue-200 font-semibold @endif price-default-input"
                                                    data-level="{{ $levelKey }}"
                                                    placeholder="0"
                                                >
                                            </div>
                                            <button 
                                                type="button" 
                                                class="btn btn-secondary btn-sm copy-to-all-outlets-btn"
                                                data-level="{{ $levelKey }}"
                                                title="Salin harga default ke semua outlet yang dipilih"
                                            >
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
                                            <div class="bg-gray-50 px-3 py-2 border-b border-gray-200 flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <i class="fas fa-store text-gray-500 text-xs"></i>
                                                    <span class="text-xs font-semibold text-gray-700">Harga Khusus Per Outlet</span>
                                                    <span class="outlet-selected-count badge badge-sm bg-gray-100 text-gray-500" data-level="{{ $levelKey }}">0 dipilih</span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <button 
                                                        type="button" 
                                                        class="text-xs text-blue-600 hover:text-blue-700 select-all-outlets"
                                                        data-level="{{ $levelKey }}"
                                                    >
                                                        Pilih Semua
                                                    </button>
                                                    <span class="text-gray-300">|</span>
                                                    <button 
                                                        type="button" 
                                                        class="text-xs text-gray-600 hover:text-gray-700 deselect-all-outlets"
                                                        data-level="{{ $levelKey }}"
                                                    >
                                                        Batal Semua
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Outlet List with Checkboxes -->
                                            <div class="max-h-64 overflow-y-auto">
                                                @foreach($outlets as $outlet)
                                                    @php
                                                        // Check if this outlet has a specific price set
                                                        $hasSpecificPrice = isset($outletPrices[$outlet->id]) && $outletPrices[$outlet->id] != 0;
                                                        $val = $hasSpecificPrice ? $outletPrices[$outlet->id] : '';
                                                    @endphp
                                                    <div class="outlet-price-row border-b border-gray-100 last:border-0 hover:bg-gray-50 transition-colors" data-outlet-id="{{ $outlet->id }}" data-level="{{ $levelKey }}">
                                                        <label class="flex items-center gap-3 px-3 py-2.5 cursor-pointer">
                                                            <input
                                                                type="checkbox"
                                                                class="outlet-checkbox w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                                                data-level="{{ $levelKey }}"
                                                                data-outlet-id="{{ $outlet->id }}"
                                                                {{ $hasSpecificPrice ? 'checked' : '' }}
                                                            >
                                                            <div class="flex-1 flex items-center justify-between gap-3">
                                                                <span class="text-sm text-gray-700 font-medium">{{ $outlet->name }}</span>
                                                                <div class="flex items-center gap-2 outlet-price-input-wrapper {{ $hasSpecificPrice ? '' : 'hidden' }}">
                                                                    <div class="flex items-center">
                                                                        <span class="inline-flex items-center px-2 text-xs text-gray-500 bg-gray-50 border border-r-0 border-gray-300 rounded-l-md">Rp</span>
                                                                        <input
                                                                            type="number"
                                                                            name="price_levels[{{ $levelKey }}][outlets][{{ $outlet->id }}]"
                                                                            value="{{ old("price_levels.$levelKey.outlets.$outlet->id", $val) }}"
                                                                            min="0"
                                                                            step="0.01"
                                                                            placeholder="0"
                                                                            class="form-input rounded-l-none text-sm w-32 outlet-price-input"
                                                                            data-level="{{ $levelKey }}"
                                                                            data-outlet-id="{{ $outlet->id }}"
                                                                            {{ $hasSpecificPrice ? '' : 'disabled' }}
                                                                        >
                                                                    </div>
                                                                    <button 
                                                                        type="button" 
                                                                        class="text-xs text-gray-400 hover:text-blue-600 copy-from-default-btn"
                                                                        data-level="{{ $levelKey }}"
                                                                        data-outlet-id="{{ $outlet->id }}"
                                                                        title="Salin dari harga default"
                                                                    >
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
                                            Centang outlet untuk mengatur harga khusus. Kosongkan input untuk menggunakan harga default.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Tab Extra Info -->
                    <div id="tab-panel-extra" class="bundle-tab-panel hidden space-y-6 pt-2">
                        <label for="description" class="form-label mb-2">Deskripsi Bundle</label>
                        <textarea name="description" id="description" rows="5"
                            class="form-input"
                            placeholder="Catatan tambahan untuk bundle ini...">{{ old('description', $product->description) }}</textarea>
                        <p class="text-xs text-gray-400 mt-2">Catatan ini juga akan dipakai sebagai notes BOM awal.</p>
                    </div>

                    <div class="flex justify-end gap-3 pt-6 border-t border-gray-100">
                        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">
                            Batal
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i> Simpan Perubahan
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Modal Pilih Raw Material -->
    <div id="materialModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog"
        aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"
                onclick="document.getElementById('materialModal').classList.add('hidden')"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div
                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">Pilih Bahan/Produk</h3>
                    <div class="mb-4">
                        <input type="text" id="searchMaterial" placeholder="Cari nama bahan..." class="form-input w-full">
                    </div>
                    <div class="max-h-60 overflow-y-auto border border-gray-200 rounded-md">
                        <ul class="divide-y divide-gray-200" id="materialList">
                            @foreach($rawMaterials as $mat)
                                <li class="p-3 hover:bg-gray-50 cursor-pointer flex justify-between items-center material-item"
                                    data-id="{{ $mat->id }}" data-name="{{ $mat->name }}" data-unit="{{ $mat->unit }}"
                                    data-cost="{{ $mat->purchase_price }}" onclick="selectMaterial(this)">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ $mat->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $mat->sku }} • Stok:
                                            {{ $mat->stocks_sum_quantity ?? 0 }} {{ $mat->unit }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs font-semibold text-gray-700">Rp
                                            {{ number_format($mat->purchase_price, 0, ',', '.') }}</p>
                                        <p class="text-[10px] text-gray-400">/{{ $mat->unit }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" class="btn btn-secondary w-full sm:w-auto sm:text-sm"
                        onclick="document.getElementById('materialModal').classList.add('hidden')">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Pilih Outlet -->
    <div id="outletModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
                onclick="document.getElementById('outletModal').classList.add('hidden')"></div>
            <div
                class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="px-4 pt-5 pb-4 bg-white sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Pilih Outlet Ketersediaan</h3>
                    <p class="text-sm text-gray-500 mb-4">Pilih outlet mana saja yang menjual bundle ini.</p>
                    <div class="max-h-60 overflow-y-auto border border-gray-200 rounded-md p-2 space-y-2">
                        @foreach($outlets as $outlet)
                            <label class="flex items-center p-2 rounded hover:bg-gray-50 gap-3 cursor-pointer">
                                <input type="checkbox" class="outlet-checkbox w-4 h-4 text-blue-600 rounded border-gray-300"
                                    value="{{ $outlet->id }}" {{ in_array($outlet->id, $oldOutletIds) ? 'checked' : '' }}>
                                <span class="text-sm text-gray-700">{{ $outlet->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 flex justify-end">
                    <button type="button" class="btn btn-primary" onclick="saveOutletSelection()">Selesai</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Pilih User -->
    <div id="userModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
                onclick="document.getElementById('userModal').classList.add('hidden')"></div>
            <div
                class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="px-4 pt-5 pb-4 bg-white sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Pilih User Akses</h3>
                    <p class="text-sm text-gray-500 mb-4">Pilih user POS mana saja yang bisa melihat bundle ini.</p>
                    <div class="mb-2">
                        <input type="text" id="searchUser" placeholder="Cari user..." class="form-input w-full text-sm">
                    </div>
                    <div class="max-h-60 overflow-y-auto border border-gray-200 rounded-md p-2 space-y-2" id="userList">
                        @foreach($posUsers as $user)
                            <label class="flex items-center p-2 rounded hover:bg-gray-50 gap-3 cursor-pointer user-item">
                                <input type="checkbox" class="user-checkbox w-4 h-4 text-blue-600 rounded border-gray-300"
                                    value="{{ $user->id }}" {{ in_array($user->id, $oldUserIds) ? 'checked' : '' }}>
                                <div>
                                    <p class="text-sm font-medium text-gray-700">{{ $user->name }}</p>
                                    <p class="text-[10px] text-gray-500">{{ $user->role->name ?? '-' }} @
                                        {{ $user->outlet->name ?? 'All' }}</p>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 flex justify-end">
                    <button type="button" class="btn btn-primary" onclick="saveUserSelection()">Selesai</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // ============================================
            // TABS LOGIC
            // ============================================
            const tabs = document.querySelectorAll('.bundle-tab-btn');
            const panels = document.querySelectorAll('.bundle-tab-panel');

            function activateTab(targetId) {
                panels.forEach(panel => panel.classList.toggle('hidden', panel.id !== targetId));
                tabs.forEach(tab => {
                    const isActive = tab.dataset.target === targetId;
                    if(isActive) {
                        tab.classList.remove('text-gray-500', 'hover:text-gray-700', 'bg-transparent');
                        tab.classList.add('bg-white', 'text-blue-600', 'border-t', 'border-x', 'border-gray-200');
                        tab.classList.add('active'); // Ensure active class is present
                        tab.style.top = '1px';
                    } else {
                        tab.classList.add('text-gray-500', 'hover:text-gray-700', 'bg-transparent');
                        tab.classList.remove('bg-white', 'text-blue-600', 'border-t', 'border-x', 'border-gray-200');
                        tab.classList.remove('active');
                        tab.style.top = '0px';
                    }
                });
            }

            tabs.forEach(tab => {
                tab.addEventListener('click', function () {
                    activateTab(tab.dataset.target);
                });
            });

            // ============================================
            // POS AVAILABILITY LOGIC (Outlet & User Selector)
            // ============================================
            const selectedOutletIds = new Set(@json($oldOutletIds));
            const selectedUserIds = new Set(@json($oldUserIds));

            const allOutletsCheckbox = document.getElementById('is_available_all_outlets');
            const allUsersCheckbox = document.getElementById('is_available_all_users');
            const outletWrap = document.getElementById('outlet-selector-wrap');
            const userWrap = document.getElementById('user-selector-wrap');
            const outletCountText = document.getElementById('outlet-count');
            const userCountText = document.getElementById('user-count');
            const outletHiddenInput = document.getElementById('pos_outlet_ids_input');
            const userHiddenInput = document.getElementById('pos_user_ids_input');

            const outletOptions = document.querySelectorAll('.outlet-option');
            const userOptions = document.querySelectorAll('.user-option');

            function updateHiddenInputs() {
                if(outletHiddenInput) outletHiddenInput.value = Array.from(selectedOutletIds).join(',');
                if(userHiddenInput) userHiddenInput.value = Array.from(selectedUserIds).join(',');
            }

            function refreshSelectionTexts() {
                if(outletCountText) outletCountText.textContent = `${selectedOutletIds.size}`;
                if(userCountText) userCountText.textContent = `${selectedUserIds.size}`;
            }

            function refreshAvailabilitySections() {
                const allOutlets = allOutletsCheckbox ? allOutletsCheckbox.checked : true;
                const allUsers = allUsersCheckbox ? allUsersCheckbox.checked : true;

                if (outletWrap) {
                    if (allOutlets) {
                        outletWrap.classList.add('hidden');
                    } else {
                        outletWrap.classList.remove('hidden');
                    }
                }

                if (userWrap) {
                    if (allUsers) {
                        userWrap.classList.add('hidden');
                    } else {
                        userWrap.classList.remove('hidden');
                    }
                }
                updateHiddenInputs();
            }

            // Sync Checkboxes in Modals
            function syncModalChecks() {
                outletOptions.forEach(option => {
                    option.checked = selectedOutletIds.has(Number(option.value));
                });
                userOptions.forEach(option => {
                    option.checked = selectedUserIds.has(Number(option.value));
                });
            }

            // Event Listeners for Modals
            outletOptions.forEach(option => {
                option.addEventListener('change', function () {
                    const value = Number(option.value);
                    if (option.checked) selectedOutletIds.add(value);
                    else selectedOutletIds.delete(value);
                    refreshSelectionTexts();
                    updateHiddenInputs();
                });
            });

            userOptions.forEach(option => {
                option.addEventListener('change', function () {
                    const value = Number(option.value);
                    if (option.checked) selectedUserIds.add(value);
                    else selectedUserIds.delete(value);
                    refreshSelectionTexts();
                    updateHiddenInputs();
                });
            });

            if (allOutletsCheckbox) allOutletsCheckbox.addEventListener('change', refreshAvailabilitySections);
            if (allUsersCheckbox) allUsersCheckbox.addEventListener('change', refreshAvailabilitySections);

            // Close modal functions
            window.closeOutletModal = function() {
                document.getElementById('outletModal').classList.add('hidden');
            }
            window.closeUserModal = function() {
                document.getElementById('userModal').classList.add('hidden');
            }
            window.saveOutletSelection = function() {
                closeOutletModal();
            }
            window.saveUserSelection = function() {
                closeUserModal();
            }

            // Initial Sync for POS
            syncModalChecks();
            refreshSelectionTexts();
            refreshAvailabilitySections();

            // ============================================
            // PRICING LOGIC (Expanded Cards)
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
                            this.innerHTML = '<i class="fas fa-store mr-1"></i>Pilih Outlet';
                        } else {
                            this.innerHTML = '<i class="fas fa-chevron-up mr-1"></i>Sembunyikan';
                        }
                    }
                });
            });

            // Handle outlet checkbox change (Enable/Disable input)
            document.querySelectorAll('.outlet-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
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
                        // Optional: Show toast
                        alert(`Harga berhasil disalin ke ${copiedCount} outlet`);
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

            // Sync Selling Price with Regular Default
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

            // Initialize counts
            document.querySelectorAll('.outlet-selected-count').forEach(badge => {
                const level = badge.dataset.level;
                updateOutletSelectedCount(level);
            });
        });

        // ============================================
        // COMPONENTS LOGIC
        // ============================================
        let components = @json($componentData ?? []); 
        
        function renderComponents() {
            const tbody = document.getElementById('components-list');
            const emptyRow = document.getElementById('empty-row');
            const inputsContainer = document.getElementById('hidden-inputs-container');

            if(!tbody || !inputsContainer) return;

            // Clear current list (except empty row template)
            Array.from(tbody.children).forEach(child => {
                if (child.id !== 'empty-row') child.remove(); 
            });
            inputsContainer.innerHTML = '';

            if (components.length === 0) {
                if(emptyRow) emptyRow.classList.remove('hidden');
                const totalDisplay = document.getElementById('total-cost-display');
                if(totalDisplay) totalDisplay.innerText = 'Rp 0';
                const purchaseInput = document.getElementById('purchase_price');
                if(purchaseInput) purchaseInput.value = 0;
                return;
            }

            if(emptyRow) emptyRow.classList.add('hidden');
            let totalCost = 0;

            components.forEach((comp, index) => {
                const subtotal = comp.cost * comp.quantity;
                totalCost += subtotal;

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="text-center text-gray-500 text-xs">${index + 1}</td>
                    <td>
                        <p class="text-sm font-medium text-gray-900">${comp.name}</p>
                    </td>
                    <td class="text-center text-xs text-gray-500">${comp.unit}</td>
                    <td class="text-right text-xs text-gray-700">Rp ${new Intl.NumberFormat('id-ID').format(comp.cost)}</td>
                    <td class="text-center">
                        <input type="number" 
                            class="form-input text-center text-sm py-1 px-2 w-20" 
                            value="${comp.quantity}" 
                            min="0.01" step="0.01"
                            onchange="updateQuantity(${index}, this.value)">
                    </td>
                    <td class="text-right text-sm font-semibold text-gray-900">Rp ${new Intl.NumberFormat('id-ID').format(subtotal)}</td>
                    <td class="text-center">
                        <button type="button" onclick="removeComponent(${index})" class="text-red-500 hover:text-red-700 transition">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);

                // Hidden inputs
                inputsContainer.innerHTML += `
                    <input type="hidden" name="components[${index}][id]" value="${comp.id}">
                    <input type="hidden" name="components[${index}][quantity]" value="${comp.quantity}">
                    <input type="hidden" name="components[${index}][unit]" value="${comp.unit}">
                    <input type="hidden" name="components[${index}][cost]" value="${comp.cost}">
                `;
            });

            const totalDisplay = document.getElementById('total-cost-display');
            if(totalDisplay) totalDisplay.innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(totalCost);
            const purchaseInput = document.getElementById('purchase_price');
            if(purchaseInput) purchaseInput.value = totalCost;
        }

        function updateQuantity(index, newQty) {
            if (newQty <= 0) newQty = 1;
            components[index].quantity = parseFloat(newQty);
            renderComponents();
        }

        function removeComponent(index) {
            components.splice(index, 1);
            renderComponents();
        }
        
        // Window level functions for modal (onclick attributes)
        window.selectMaterial = function(el) {
            const id = el.dataset.id;
            const name = el.dataset.name;
            const unit = el.dataset.unit;
            const cost = parseFloat(el.dataset.cost);

            // Cek duplikat
            const exists = components.find(c => c.id == id);
            if (exists) {
                alert('Bahan ini sudah ada dalam list.');
                return;
            }

            components.push({
                id: id,
                name: name,
                unit: unit,
                cost: cost,
                quantity: 1 // Default qty
            });

            renderComponents();
            document.getElementById('materialModal').classList.add('hidden');
        }

        // Initialize Components and Image Preview
        document.addEventListener('DOMContentLoaded', function() {
            renderComponents();
            
            // Image Preview
            const imageInput = document.getElementById('image');
            const imagePreview = document.getElementById('imagePreview');
            const imagePlaceholder = document.getElementById('imagePlaceholder');

            if(imageInput){
                imageInput.addEventListener('change', function(event) {
                    const file = event.target.files && event.target.files[0];
                    if (!file) return;
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imagePreview.src = e.target.result;
                        imagePreview.classList.remove('hidden');
                        imagePlaceholder.classList.add('hidden');
                    };
                    reader.readAsDataURL(file);
                });
            }
        });
    </script>
@endsection
