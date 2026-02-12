@extends('layouts.admin')

@section('title', 'Edit Bundle')
@section('page-title', 'Edit Bundle')
@section('page-subtitle', 'Perbarui data bundle, komponen bahan, dan harga')

@section('content')
@php
    $oldOutletIds = collect(old('pos_outlet_ids', $product->pos_outlet_ids ?? []))->map(fn ($id) => (int) $id)->values()->all();
    $oldUserIds = collect(old('pos_user_ids', $product->pos_user_ids ?? []))->map(fn ($id) => (int) $id)->values()->all();
    
    // Siapkan data komponen untuk JS jika ada error validasi atau dari DB
    // Struktur yang diharapkan JS: { id: 1, name: 'Bahan A', unit: 'gr', cost: 500, quantity: 2 }
    $existingComponents = [];
    if(old('components')) {
        // Jika ada input old (validasi error), gunakan itu
        // Perlu mapping manual karena old('components') strukturnya array of inputs
        // Ini agak kompleks parsingnya, simplified: kita abaikan old components detail kompleks saat error dulu 
        // atau kita ambil dari session jika memungkinkan. 
        // Untuk saat ini kita ambil dari database jika old kosong.
    } 
    
    // Ambil dari DB BOM details
    $bomDetails = $product->bomHeader->bomDetails ?? collect([]);
    $componentData = $bomDetails->map(function($detail) {
        return [
            'id' => $detail->product_id,
            'name' => $detail->product->name ?? 'Unknown',
            'unit' => $detail->product->unit ?? '-',
            'cost' => $detail->product->purchase_price ?? 0,
            'quantity' => (float)$detail->quantity
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

    <form action="{{ route('admin.products.update', $product) }}" method="POST" id="bundleForm" enctype="multipart/form-data">
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
                            <div class="aspect-square rounded-lg bg-gray-100 border border-gray-200 overflow-hidden flex items-center justify-center relative">
                                <img id="imagePreview" src="{{ $product->image_path ? asset('storage/' . $product->image_path) : '' }}" alt="Preview" class="{{ $product->image_path ? '' : 'hidden' }} w-full h-full object-cover">
                                <div id="imagePlaceholder" class="{{ $product->image_path ? 'hidden' : '' }} text-gray-400 text-center">
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
                                <input
                                    type="text"
                                    name="name"
                                    id="name"
                                    value="{{ old('name', $product->name) }}"
                                    class="form-input"
                                    placeholder="Contoh: Paket Nasi + Es Teh"
                                    required
                                >
                                @error('name')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
    
                            <div>
                                <label for="sku" class="form-label">
                                    SKU <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    name="sku"
                                    id="sku"
                                    value="{{ old('sku', $product->sku) }}"
                                    class="form-input"
                                    placeholder="Contoh: BUNDLE-001"
                                    required
                                >
                                @error('sku')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="category_id" class="form-label">
                                    Kategori <span class="text-red-500">*</span>
                                </label>
                                <select
                                    name="category_id"
                                    id="category_id"
                                    class="form-input"
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
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
    
                            <div>
                                <label for="unit" class="form-label">
                                    Unit <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    name="unit"
                                    id="unit"
                                    value="{{ old('unit', $product->unit) }}"
                                    class="form-input"
                                    placeholder="pcs"
                                    required
                                >
                                @error('unit')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="purchase_price" class="form-label">
                                    Harga Modal <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="number"
                                    name="purchase_price"
                                    id="purchase_price"
                                    value="{{ old('purchase_price', $product->purchase_price) }}"
                                    class="form-input bg-gray-50 text-gray-500"
                                    placeholder="0"
                                    min="0"
                                    step="0.01"
                                    readonly
                                    required
                                >
                                <p class="mt-1 text-xs text-gray-400">Otomatis dari total komponen.</p>
                                @error('purchase_price')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="min_stock" class="form-label">
                                Stok Minimal
                            </label>
                            <input
                                type="number"
                                name="min_stock"
                                id="min_stock"
                                value="{{ old('min_stock', $product->min_stock) }}"
                                class="form-input"
                                placeholder="0"
                                min="0"
                            >
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
                                <input type="checkbox" name="is_sellable" id="is_sellable" value="1"
                                    {{ old('is_sellable', $product->is_sellable ?? true) ? 'checked' : '' }}
                                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span class="text-sm text-gray-700">Saya menjual bundle ini</span>
                            </label>

                            <div>
                                <label for="selling_price" class="form-label">
                                    Harga Jual Reguler <span class="text-red-500">*</span>
                                </label>
                                <div class="flex">
                                    <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                        Rp
                                    </span>
                                    <input
                                        type="number"
                                        name="selling_price"
                                        id="selling_price"
                                        value="{{ old('selling_price', $product->selling_price) }}"
                                        class="form-input rounded-l-none"
                                        placeholder="0"
                                        min="0"
                                        step="0.01"
                                        required
                                    >
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Akan menjadi harga default untuk semua outlet/level.</p>
                                @error('selling_price')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div>
                         <h4 class="text-sm font-bold text-gray-900 mb-1">Setup POS</h4>
                         <p class="text-xs text-gray-500 mb-4">Tentukan dimana produk ini muncul di POS.</p>

                         <div class="space-y-3">
                             <label class="flex items-center gap-2 cursor-pointer">
                                 <input type="checkbox" name="is_pos_available" id="is_pos_available" value="1"
                                     {{ old('is_pos_available', $product->is_pos_available ?? true) ? 'checked' : '' }}
                                     class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                 <span class="text-sm text-gray-700">Tampil di menu POS</span>
                             </label>

                             <label class="flex items-center gap-2 cursor-pointer">
                                 <input type="checkbox" name="is_available_all_outlets" id="is_available_all_outlets" value="1"
                                     {{ old('is_available_all_outlets', $product->is_available_all_outlets ?? true) ? 'checked' : '' }}
                                     class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                 <span class="text-sm text-gray-700">Tersedia di SEMUA Outlet</span>
                             </label>
                             <div id="outlet-selector-wrap" class="{{ old('is_available_all_outlets', $product->is_available_all_outlets ?? true) ? 'hidden' : '' }} pt-1 pl-6">
                                <button type="button" onclick="document.getElementById('outletModal').classList.remove('hidden')"
                                    class="text-xs text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1">
                                    <i class="fas fa-store"></i> Pilih Outlet Spesifik
                                    <span class="bg-blue-100 text-blue-800 px-1.5 py-0.5 rounded-full text-[10px]" id="outlet-count">
                                        {{ count($oldOutletIds) }}
                                    </span>
                                </button>
                                <input type="hidden" name="pos_outlet_ids" id="pos_outlet_ids_input" value="{{ implode(',', $oldOutletIds) }}">
                             </div>

                             <label class="flex items-center gap-2 cursor-pointer">
                                 <input type="checkbox" name="is_available_all_users" id="is_available_all_users" value="1"
                                     {{ old('is_available_all_users', $product->is_available_all_users ?? true) ? 'checked' : '' }}
                                     class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                 <span class="text-sm text-gray-700">Tersedia untuk SEMUA Akun POS</span>
                             </label>
                             <div id="user-selector-wrap" class="{{ old('is_available_all_users', $product->is_available_all_users ?? true) ? 'hidden' : '' }} pt-1 pl-6">
                                 <button type="button" onclick="document.getElementById('userModal').classList.remove('hidden')"
                                     class="text-xs text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1">
                                     <i class="fas fa-users"></i> Pilih User Spesifik
                                     <span class="bg-blue-100 text-blue-800 px-1.5 py-0.5 rounded-full text-[10px]" id="user-count">
                                         {{ count($oldUserIds) }}
                                     </span>
                                 </button>
                                 <input type="hidden" name="pos_user_ids" id="pos_user_ids_input" value="{{ implode(',', $oldUserIds) }}">
                             </div>
                         </div>
                    </div>
                </div>

                <!-- Tabs Navigation -->
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex gap-6" aria-label="Tabs">
                        <button type="button" id="tab-btn-components" onclick="switchTab('components')" 
                            class="tab-btn active border-b-2 border-blue-500 py-4 px-1 text-sm font-medium text-blue-600">
                            <i class="fas fa-cubes mr-2"></i>Bundle / Bahan
                        </button>
                        <button type="button" id="tab-btn-prices" onclick="switchTab('prices')"
                            class="tab-btn border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">
                            <i class="fas fa-tags mr-2"></i>Pengaturan Harga
                        </button>
                    </nav>
                </div>

                <!-- Tab Components -->
                <div id="tab-panel-components" class="tab-panel space-y-4 pt-2">
                    <div class="flex justify-between items-center mb-2">
                        <div>
                            <h4 class="text-sm font-bold text-gray-900">Komponen Bundle</h4>
                            <p class="text-xs text-gray-500">Tambahkan bahan baku atau produk yang membentuk bundle ini.</p>
                        </div>
                        <button type="button" onclick="document.getElementById('materialModal').classList.remove('hidden')"
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
                <div id="tab-panel-prices" class="tab-panel hidden space-y-6 pt-2">
                    <div class="bg-orange-50 border border-orange-100 rounded-lg p-4 mb-4">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-info-circle text-orange-500 mt-0.5"></i>
                            <div>
                                <h4 class="text-sm font-bold text-orange-800">Logika Harga</h4>
                                <p class="text-xs text-orange-700 mt-1">
                                    Jika harga level/outlet dikosongkan (0), sistem akan otomatis menggunakan <b>Harga Jual Reguler</b>. 
                                    Isi hanya jika harganya BERBEDA.
                                </p>
                            </div>
                        </div>
                    </div>

                    @foreach($priceLevels as $levelKey => $levelLabel)
                        @php
                            $priceData = data_get($product->price_levels, $levelKey);
                            $defaultPrice = is_array($priceData) ? ($priceData['default'] ?? 0) : $priceData;
                            $outletPrices = is_array($priceData) ? ($priceData['outlets'] ?? []) : [];
                            
                            // Ambil nama level untuk display yang bagus
                            $displayLevel = ucfirst($levelLabel); 
                        @endphp
                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex justify-between items-center cursor-pointer" 
                                onclick="togglePriceSection('{{ $levelKey }}')">
                                <span class="font-semibold text-gray-700 text-sm">Level: {{ $displayLevel }}</span>
                                <i class="fas fa-chevron-down text-gray-400 transition-transform" id="icon-{{ $levelKey }}"></i>
                            </div>
                            <div class="p-4 hidden transition-all duration-200" id="section-{{ $levelKey }}">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                                    <div>
                                        <label class="form-label text-xs mb-1">Harga Default {{ $displayLevel }}</label>
                                        <div class="flex">
                                            <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-xs">Rp</span>
                                            <input type="number" 
                                                name="price_levels[{{ $levelKey }}][default]" 
                                                class="form-input rounded-l-none text-sm default-price-input" 
                                                data-level="{{ $levelKey }}"
                                                placeholder="Ikut Reguler"
                                                value="{{ old("price_levels.$levelKey.default", $defaultPrice == 0 ? '' : $defaultPrice) }}">
                                        </div>
                                    </div>
                                    
                                    <div class="space-y-3">
                                        <div class="flex justify-between items-end">
                                            <label class="form-label text-xs mb-0">Harga Per Outlet (Opsional)</label>
                                            <button type="button" class="text-[10px] text-blue-600 hover:underline" onclick="toggleOutletPrices('{{ $levelKey }}')">
                                                <i class="fas fa-store mr-1"></i> Atur per outlet
                                            </button>
                                        </div>
                                        
                                        <div id="outlet-prices-{{ $levelKey }}" class="hidden space-y-2 bg-gray-50 p-3 rounded-md border border-gray-100">
                                             <div class="flex justify-end mb-2">
                                                <button type="button" class="text-[10px] bg-white border border-gray-200 px-2 py-1 rounded shadow-sm text-gray-600 hover:text-blue-600"
                                                    onclick="copyDefaultToOutlets('{{ $levelKey }}')">
                                                    <i class="fas fa-copy mr-1"></i> Copy Default ke Semua Outlet
                                                </button>
                                            </div>
                                            <!-- List Outlets for Price Override -->
                                            <div class="grid grid-cols-1 gap-2 max-h-40 overflow-y-auto pr-1">
                                                @foreach($outlets as $outlet)
                                                    <div class="flex items-center justify-between text-xs">
                                                        <span class="text-gray-600 w-1/3 truncate" title="{{ $outlet->name }}">{{ $outlet->name }}</span>
                                                        <div class="flex w-2/3">
                                                            <span class="inline-flex items-center px-2 rounded-l-md border border-r-0 border-gray-300 bg-white text-gray-500 text-[10px]">Rp</span>
                                                            <input type="number" 
                                                                name="price_levels[{{ $levelKey }}][outlets][{{ $outlet->id }}]" 
                                                                class="form-input rounded-l-none text-xs py-1 outlet-price-{{ $levelKey }}" 
                                                                placeholder="Default"
                                                                value="{{ old("price_levels.$levelKey.outlets.$outlet->id", isset($outletPrices[$outlet->id]) && $outletPrices[$outlet->id] != 0 ? $outletPrices[$outlet->id] : '') }}">
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
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
<div id="materialModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="document.getElementById('materialModal').classList.add('hidden')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">Pilih Bahan/Produk</h3>
                <div class="mb-4">
                    <input type="text" id="searchMaterial" placeholder="Cari nama bahan..." class="form-input w-full">
                </div>
                <div class="max-h-60 overflow-y-auto border border-gray-200 rounded-md">
                    <ul class="divide-y divide-gray-200" id="materialList">
                        @foreach($rawMaterials as $mat)
                            <li class="p-3 hover:bg-gray-50 cursor-pointer flex justify-between items-center material-item"
                                data-id="{{ $mat->id }}"
                                data-name="{{ $mat->name }}"
                                data-unit="{{ $mat->unit }}"
                                data-cost="{{ $mat->purchase_price }}"
                                onclick="selectMaterial(this)">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $mat->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $mat->sku }} • Stok: {{ $mat->stocks_sum_quantity ?? 0 }} {{ $mat->unit }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs font-semibold text-gray-700">Rp {{ number_format($mat->purchase_price, 0, ',', '.') }}</p>
                                    <p class="text-[10px] text-gray-400">/{{ $mat->unit }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" class="btn btn-secondary w-full sm:w-auto sm:text-sm" onclick="document.getElementById('materialModal').classList.add('hidden')">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Pilih Outlet -->
<div id="outletModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" onclick="document.getElementById('outletModal').classList.add('hidden')"></div>
        <div class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
             <div class="px-4 pt-5 pb-4 bg-white sm:p-6 sm:pb-4">
                 <h3 class="text-lg font-medium leading-6 text-gray-900">Pilih Outlet Ketersediaan</h3>
                 <p class="text-sm text-gray-500 mb-4">Pilih outlet mana saja yang menjual bundle ini.</p>
                 <div class="max-h-60 overflow-y-auto border border-gray-200 rounded-md p-2 space-y-2">
                     @foreach($outlets as $outlet)
                     <label class="flex items-center p-2 rounded hover:bg-gray-50 gap-3 cursor-pointer">
                         <input type="checkbox" class="outlet-checkbox w-4 h-4 text-blue-600 rounded border-gray-300" 
                             value="{{ $outlet->id }}" 
                             {{ in_array($outlet->id, $oldOutletIds) ? 'checked' : '' }}>
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
        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" onclick="document.getElementById('userModal').classList.add('hidden')"></div>
        <div class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
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
                             value="{{ $user->id }}" 
                             {{ in_array($user->id, $oldUserIds) ? 'checked' : '' }}>
                         <div>
                             <p class="text-sm font-medium text-gray-700">{{ $user->name }}</p>
                             <p class="text-[10px] text-gray-500">{{ $user->role->name ?? '-' }} @ {{ $user->outlet->name ?? 'All' }}</p>
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
    // --- Tabs Logic ---
    function switchTab(tabName) {
        document.querySelectorAll('.tab-panel').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.tab-btn').forEach(el => {
            el.classList.remove('active', 'border-blue-500', 'text-blue-600');
            el.classList.add('border-transparent', 'text-gray-500');
        });

        document.getElementById(`tab-panel-${tabName}`).classList.remove('hidden');
        const activeBtn = document.getElementById(`tab-btn-${tabName}`);
        activeBtn.classList.add('active', 'border-blue-500', 'text-blue-600');
        activeBtn.classList.remove('border-transparent', 'text-gray-500');
    }

    // --- Components / Ingredients Logic ---
    let components = @json($componentData);

    function renderComponents() {
        const tbody = document.getElementById('components-list');
        const emptyRow = document.getElementById('empty-row');
        const inputsContainer = document.getElementById('hidden-inputs-container');
        
        // Clear current list (except empty row template)
        Array.from(tbody.children).forEach(child => {
            if (child.id !== 'empty-row') child.remove(); 
        });
        inputsContainer.innerHTML = '';

        if (components.length === 0) {
            emptyRow.classList.remove('hidden');
            document.getElementById('total-cost-display').innerText = 'Rp 0';
            document.getElementById('purchase_price').value = 0;
            return;
        }

        emptyRow.classList.add('hidden');
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

            // Hidden inputs for form submission
            // Format: components[0][id], components[0][qty], components[0][unit]
            inputsContainer.innerHTML += `
                <input type="hidden" name="components[${index}][id]" value="${comp.id}">
                <input type="hidden" name="components[${index}][quantity]" value="${comp.quantity}">
                <input type="hidden" name="components[${index}][unit]" value="${comp.unit}">
                <input type="hidden" name="components[${index}][cost]" value="${comp.cost}">
            `;
        });

        document.getElementById('total-cost-display').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(totalCost);
        document.getElementById('purchase_price').value = totalCost; // Update purchase price otomatis
    }

    function selectMaterial(el) {
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

    function updateQuantity(index, newQty) {
        if (newQty <= 0) newQty = 1;
        components[index].quantity = parseFloat(newQty);
        renderComponents();
    }

    function removeComponent(index) {
        components.splice(index, 1);
        renderComponents();
    }
    
    // --- Helper UI ---
    // Search Materials
    document.getElementById('searchMaterial').addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        const items = document.querySelectorAll('.material-item');
        items.forEach(item => {
            const text = item.innerText.toLowerCase();
            item.style.display = text.includes(filter) ? '' : 'none';
        });
    });

    // Image Preview
    const imageInput = document.getElementById('image');
    const imagePreview = document.getElementById('imagePreview');
    const imagePlaceholder = document.getElementById('imagePlaceholder');

    if(imageInput){
        imageInput.addEventListener('change', function(event) {
            const file = event.target.files && event.target.files[0];
            if (!file) {
                 // Do not clear if editing and file not changed (keep existing)
                 return;
            }
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                imagePreview.classList.remove('hidden');
                imagePlaceholder.classList.add('hidden');
            };
            reader.readAsDataURL(file);
        });
    }

    // --- Price Levels Logic ---
    function togglePriceSection(level) {
        const section = document.getElementById(`section-${level}`);
        const icon = document.getElementById(`icon-${level}`);
        section.classList.toggle('hidden');
        icon.classList.toggle('rotate-180');
    }

    function toggleOutletPrices(level) {
        const div = document.getElementById(`outlet-prices-${level}`);
        div.classList.toggle('hidden');
    }
    
    function copyDefaultToOutlets(level) {
        // Cari input default pada level ini
        const defaultInput = document.querySelector(`.default-price-input[data-level="${level}"]`);
        if (!defaultInput) return;
        
        const val = defaultInput.value;
        // Cari semua input outlet pada level ini
        const outletInputs = document.querySelectorAll(`.outlet-price-${level}`);
        outletInputs.forEach(input => {
            input.value = val;
        });
        alert(`Harga default Rp ${val} disalin ke ${outletInputs.length} outlet pada level ini.`);
    }

    // --- POS Availability Logic (Outlet & User Selector) ---
    // (Sama seperti create.blade.php / create_bundle.blade.php)
    const allOutletsCheckbox = document.getElementById('is_available_all_outlets');
    const outletWrap = document.getElementById('outlet-selector-wrap');
    
    allOutletsCheckbox.addEventListener('change', function() {
        if(this.checked) {
            outletWrap.classList.add('hidden');
        } else {
            outletWrap.classList.remove('hidden');
        }
    });

    const allUsersCheckbox = document.getElementById('is_available_all_users');
    const userWrap = document.getElementById('user-selector-wrap');
    
    allUsersCheckbox.addEventListener('change', function() {
        if(this.checked) {
            userWrap.classList.add('hidden');
        } else {
            userWrap.classList.remove('hidden');
        }
    });

    function saveOutletSelection() {
        const checkboxes = document.querySelectorAll('.outlet-checkbox:checked');
        const ids = Array.from(checkboxes).map(cb => cb.value);
        document.getElementById('pos_outlet_ids_input').value = ids.join(',');
        document.getElementById('outlet-count').innerText = ids.length;
        document.getElementById('outletModal').classList.add('hidden');
    }

    function saveUserSelection() {
        const checkboxes = document.querySelectorAll('.user-checkbox:checked');
        const ids = Array.from(checkboxes).map(cb => cb.value);
        document.getElementById('pos_user_ids_input').value = ids.join(',');
        document.getElementById('user-count').innerText = ids.length;
        document.getElementById('userModal').classList.add('hidden');
    }

    // Init components on load
    renderComponents();

</script>
@endsection
