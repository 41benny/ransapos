@extends('layouts.admin')

@section('title', 'Tambah BOM')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-xl shadow-md mb-6 p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h3 class="text-xl font-bold text-gray-800">Tambah Bill of Materials</h3>
                <p class="text-sm text-gray-500 mt-1">Buat resep produksi untuk produk finished good</p>
            </div>
            <a href="{{ route('admin.boms.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white text-sm font-medium rounded-lg transition-colors duration-150">
                <i class="fas fa-arrow-left mr-2"></i>Kembali
            </a>
        </div>

        <form action="{{ route('admin.boms.store') }}" method="POST">
            @csrf

            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li class="text-sm">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Left Column: Product Info -->
                <div class="space-y-6">
                    <div>
                        <label for="product_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Produk Utama <span class="text-red-500">*</span>
                        </label>
                        <select name="product_id" id="product_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('product_id') border-red-500 @enderror" required>
                            <option value="">Pilih Produk...</option>
                            @foreach($finishedProducts as $product)
                                <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                    {{ $product->name }} ({{ $product->sku }})
                                </option>
                            @endforeach
                        </select>
                        @error('product_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Nama BOM</label>
                        <input type="text" name="name" id="name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-500 @enderror"
                               value="{{ old('name') }}" placeholder="Contoh: Resep Nasi Goreng Special">
                        @error('name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" id="is_active" name="is_active" value="1"
                                   {{ old('is_active', 1) ? 'checked' : '' }}>
                            <span class="ml-2 text-sm text-gray-700 font-medium">Aktif</span>
                        </label>
                        <p class="text-xs text-gray-500 mt-1">BOM hanya digunakan jika dalam status aktif</p>
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Catatan</label>
                        <textarea name="notes" id="notes" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('notes') border-red-500 @enderror"
                                  rows="3" placeholder="Catatan tambahan...">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Right Column: Components -->
                <div>
                    <h5 class="text-lg font-semibold text-gray-800 mb-4">
                        Komponen BOM <span class="text-red-500">*</span>
                    </h5>
                    <div id="components-container" class="space-y-3">
                        @if(old('components'))
                            @foreach(old('components') as $index => $component)
                                <div class="component-row bg-gray-50 border border-gray-200 rounded-lg p-4">
                                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                                        <div class="md:col-span-6">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Bahan/Komponen</label>
                                            <select name="components[{{ $index }}][component_product_id]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm" required>
                                                <option value="">Pilih Bahan...</option>
                                                @foreach($rawMaterials as $raw)
                                                    <option value="{{ $raw->id }}" {{ $component['component_product_id'] == $raw->id ? 'selected' : '' }}>
                                                        {{ $raw->name }} ({{ $raw->sku }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="md:col-span-3">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah</label>
                                            <input type="number" name="components[{{ $index }}][quantity]"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                                   step="0.0001" min="0.0001" value="{{ $component['quantity'] }}" required>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Satuan</label>
                                            <input type="text" name="components[{{ $index }}][uom]"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                                   placeholder="kg, liter..." value="{{ $component['uom'] ?? '' }}">
                                        </div>
                                        <div class="md:col-span-1 flex items-end">
                                            <button type="button" class="w-full px-3 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-colors duration-150 remove-component">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <!-- Default first component row -->
                            <div class="component-row bg-gray-50 border border-gray-200 rounded-lg p-4">
                                <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                                    <div class="md:col-span-6">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Bahan/Komponen</label>
                                        <select name="components[0][component_product_id]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm" required>
                                            <option value="">Pilih Bahan...</option>
                                            @foreach($rawMaterials as $raw)
                                                <option value="{{ $raw->id }}">{{ $raw->name }} ({{ $raw->sku }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="md:col-span-3">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah</label>
                                        <input type="number" name="components[0][quantity]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                               step="0.0001" min="0.0001" required>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Satuan</label>
                                        <input type="text" name="components[0][uom]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                               placeholder="kg, liter...">
                                    </div>
                                    <div class="md:col-span-1 flex items-end">
                                        <button type="button" class="w-full px-3 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-colors duration-150 remove-component">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <button type="button" id="add-component" class="mt-4 inline-flex items-center px-4 py-2 bg-green-500 hover:bg-green-600 text-white text-sm font-medium rounded-lg transition-colors duration-150">
                        <i class="fas fa-plus mr-2"></i>Tambah Komponen
                    </button>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-8 pt-6 border-t border-gray-200">
                <a href="{{ route('admin.boms.index') }}" class="px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors duration-150">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2 bg-gradient-to-r from-blue-500 to-cyan-600 hover:from-blue-600 hover:to-cyan-700 text-white rounded-lg shadow-md transition-all duration-150">
                    <i class="fas fa-save mr-2"></i>Simpan BOM
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Data for JavaScript (without mixing Blade syntax in script tags) -->
<div id="app-data" style="display: none;"
     data-component-index="{{ old('components') ? count(old('components')) : 1 }}"
     data-raw-materials="{{ json_encode($rawMaterials->map(function ($raw) { return ['id' => $raw->id, 'name' => $raw->name, 'sku' => $raw->sku]; })) }}">
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get data from data attributes (clean approach)
    var appData = document.getElementById('app-data');
    var componentIndex = parseInt(appData.getAttribute('data-component-index'));
    var rawMaterials = JSON.parse(appData.getAttribute('data-raw-materials'));

    // Build options HTML
    var rawMaterialsOptions = '';
    rawMaterials.forEach(function(raw) {
        rawMaterialsOptions += '<option value="' + raw.id + '">' + raw.name + ' (' + raw.sku + ')</option>';
    });

    // Add component
    document.getElementById('add-component').addEventListener('click', function() {
        var container = document.getElementById('components-container');
        var newRow = createComponentRow(componentIndex);
        container.insertAdjacentHTML('beforeend', newRow);
        componentIndex++;
    });

    // Remove component
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-component') || e.target.closest('.remove-component')) {
            var row = e.target.closest('.component-row');
            if (document.querySelectorAll('.component-row').length > 1) {
                row.remove();
            } else {
                alert('Minimal harus ada 1 komponen');
            }
        }
    });

    function createComponentRow(index) {
        return '<div class="component-row bg-gray-50 border border-gray-200 rounded-lg p-4">' +
                    '<div class="grid grid-cols-1 md:grid-cols-12 gap-3">' +
                        '<div class="md:col-span-6">' +
                            '<label class="block text-sm font-medium text-gray-700 mb-1">Bahan/Komponen</label>' +
                            '<select name="components[' + index + '][component_product_id]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm" required>' +
                                '<option value="">Pilih Bahan...</option>' +
                                rawMaterialsOptions +
                            '</select>' +
                        '</div>' +
                        '<div class="md:col-span-3">' +
                            '<label class="block text-sm font-medium text-gray-700 mb-1">Jumlah</label>' +
                            '<input type="number" name="components[' + index + '][quantity]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm" step="0.0001" min="0.0001" required>' +
                        '</div>' +
                        '<div class="md:col-span-2">' +
                            '<label class="block text-sm font-medium text-gray-700 mb-1">Satuan</label>' +
                            '<input type="text" name="components[' + index + '][uom]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm" placeholder="kg, liter...">' +
                        '</div>' +
                        '<div class="md:col-span-1 flex items-end">' +
                            '<button type="button" class="w-full px-3 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-colors duration-150 remove-component"><i class="fas fa-trash"></i></button>' +
                        '</div>' +
                    '</div>' +
                '</div>';
    }
});
</script>
@endpush
@endsection
