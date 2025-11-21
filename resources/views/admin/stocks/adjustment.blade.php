@extends('layouts.admin')

@section('title', 'Stock Adjustment')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Stock Adjustment</h1>
            <p class="text-sm text-gray-600 mt-1">Penyesuaian stok manual (opname)</p>
        </div>

        <!-- Alert Info -->
        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-blue-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-700">
                        Gunakan fitur ini untuk menyesuaikan stok berdasarkan hasil stock opname fisik.
                        Sistem akan otomatis mencatat mutasi adjustment.
                    </p>
                </div>
            </div>
        </div>

        <!-- Form -->
        <div class="bg-white rounded-lg shadow p-6">
            <form method="POST" action="{{ route('admin.stocks.adjustment.store') }}" id="adjustmentForm">
                @csrf

                <div class="space-y-6">
                    <!-- Outlet Selection -->
                    <div>
                        <label for="outlet_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Outlet <span class="text-red-500">*</span>
                        </label>
                        <select name="outlet_id" id="outlet_id" required
                                class="w-full border-gray-300 rounded-lg focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Pilih Outlet</option>
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->id }}" {{ old('outlet_id') == $outlet->id ? 'selected' : '' }}>
                                    {{ $outlet->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('outlet_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Product Selection -->
                    <div>
                        <label for="product_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Produk <span class="text-red-500">*</span>
                        </label>
                        <select name="product_id" id="product_id" required
                                class="w-full border-gray-300 rounded-lg focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Pilih Produk</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                    {{ $product->name }} - {{ $product->sku ?? 'No SKU' }}
                                </option>
                            @endforeach
                        </select>
                        @error('product_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Current Stock Display -->
                    <div id="currentStockDisplay" class="hidden bg-gray-50 rounded-lg p-4 border border-gray-200">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600 mb-1">Stok Saat Ini (Sistem)</p>
                                <p class="text-2xl font-bold text-gray-900" id="currentStockValue">0</p>
                                <p class="text-xs text-gray-500" id="productUnit">pcs</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 mb-1">Produk</p>
                                <p class="font-semibold text-gray-900" id="productName">-</p>
                            </div>
                        </div>
                    </div>

                    <!-- New Quantity Input -->
                    <div>
                        <label for="new_quantity" class="block text-sm font-medium text-gray-700 mb-2">
                            Stok Aktual (Hasil Opname) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="new_quantity" id="new_quantity" step="0.01" min="0" required
                               value="{{ old('new_quantity') }}"
                               class="w-full border-gray-300 rounded-lg focus:border-indigo-500 focus:ring-indigo-500"
                               placeholder="Masukkan jumlah stok aktual">
                        @error('new_quantity')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Difference Display -->
                    <div id="differenceDisplay" class="hidden bg-yellow-50 rounded-lg p-4 border border-yellow-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Selisih</p>
                                <p class="text-xl font-bold" id="differenceValue">0</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-600">Status</p>
                                <p class="font-semibold" id="differenceStatus">-</p>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                            Catatan/Alasan <span class="text-red-500">*</span>
                        </label>
                        <textarea name="notes" id="notes" rows="3" required
                                  class="w-full border-gray-300 rounded-lg focus:border-indigo-500 focus:ring-indigo-500"
                                  placeholder="Contoh: Stock opname tanggal 20 Nov 2025, ditemukan selisih karena...">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Submit Buttons -->
                    <div class="flex gap-3 pt-4 border-t">
                        <button type="submit" class="flex-1 bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition font-semibold">
                            <i class="fas fa-save mr-2"></i>Simpan Adjustment
                        </button>
                        <a href="{{ route('admin.stocks.index') }}" class="bg-gray-200 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-300 transition font-semibold">
                            Batal
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const outletSelect = document.getElementById('outlet_id');
    const productSelect = document.getElementById('product_id');
    const newQuantityInput = document.getElementById('new_quantity');
    const currentStockDisplay = document.getElementById('currentStockDisplay');
    const differenceDisplay = document.getElementById('differenceDisplay');

    let currentStock = 0;

    // Fetch current stock when outlet and product are selected
    function fetchCurrentStock() {
        const outletId = outletSelect.value;
        const productId = productSelect.value;

        if (!outletId || !productId) {
            currentStockDisplay.classList.add('hidden');
            differenceDisplay.classList.add('hidden');
            return;
        }

        fetch(`{{ route('admin.stocks.current') }}?product_id=${productId}&outlet_id=${outletId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentStock = parseFloat(data.current_stock);
                    document.getElementById('currentStockValue').textContent = currentStock.toFixed(2);
                    document.getElementById('productName').textContent = data.product_name;
                    document.getElementById('productUnit').textContent = data.unit;
                    currentStockDisplay.classList.remove('hidden');

                    // Calculate difference if new quantity is entered
                    calculateDifference();
                }
            })
            .catch(error => console.error('Error:', error));
    }

    // Calculate difference
    function calculateDifference() {
        const newQty = parseFloat(newQuantityInput.value) || 0;
        const difference = newQty - currentStock;

        if (currentStockDisplay.classList.contains('hidden')) {
            differenceDisplay.classList.add('hidden');
            return;
        }

        document.getElementById('differenceValue').textContent = difference.toFixed(2);

        const statusElement = document.getElementById('differenceStatus');
        const differenceValueElement = document.getElementById('differenceValue');

        if (difference > 0) {
            statusElement.textContent = 'Kelebihan (Plus)';
            statusElement.className = 'font-semibold text-green-600';
            differenceValueElement.className = 'text-xl font-bold text-green-600';
        } else if (difference < 0) {
            statusElement.textContent = 'Kekurangan (Minus)';
            statusElement.className = 'font-semibold text-red-600';
            differenceValueElement.className = 'text-xl font-bold text-red-600';
        } else {
            statusElement.textContent = 'Sama (No Change)';
            statusElement.className = 'font-semibold text-gray-600';
            differenceValueElement.className = 'text-xl font-bold text-gray-600';
        }

        differenceDisplay.classList.remove('hidden');
    }

    // Event listeners
    outletSelect.addEventListener('change', fetchCurrentStock);
    productSelect.addEventListener('change', fetchCurrentStock);
    newQuantityInput.addEventListener('input', calculateDifference);
});
</script>
@endpush
@endsection
