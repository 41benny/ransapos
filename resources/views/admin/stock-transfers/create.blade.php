@extends('layouts.admin')

@section('title', 'Buat Transfer Stok')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-5xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Buat Transfer Stok Baru</h1>
            <p class="text-sm text-gray-600 mt-1">Transfer stok antar outlet</p>
        </div>

        <!-- Form -->
        <div class="bg-white rounded-lg shadow p-6">
            <form method="POST" action="{{ route('admin.stock-transfers.store') }}" id="transferForm">
                @csrf

                <!-- Transfer Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="from_outlet_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Dari Outlet <span class="text-red-500">*</span>
                        </label>
                        <select name="from_outlet_id" id="from_outlet_id" required
                                class="w-full border-gray-300 rounded-lg focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Pilih Outlet Pengirim</option>
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->id }}" {{ old('from_outlet_id') == $outlet->id ? 'selected' : '' }}>
                                    {{ $outlet->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('from_outlet_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="to_outlet_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Ke Outlet <span class="text-red-500">*</span>
                        </label>
                        <select name="to_outlet_id" id="to_outlet_id" required
                                class="w-full border-gray-300 rounded-lg focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Pilih Outlet Penerima</option>
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->id }}" {{ old('to_outlet_id') == $outlet->id ? 'selected' : '' }}>
                                    {{ $outlet->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('to_outlet_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="transfer_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Tanggal Transfer <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="transfer_date" id="transfer_date" required
                               value="{{ old('transfer_date', date('Y-m-d')) }}"
                               class="w-full border-gray-300 rounded-lg focus:border-indigo-500 focus:ring-indigo-500">
                        @error('transfer_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                            Catatan
                        </label>
                        <input type="text" name="notes" id="notes" value="{{ old('notes') }}"
                               placeholder="Catatan opsional..."
                               class="w-full border-gray-300 rounded-lg focus:border-indigo-500 focus:ring-indigo-500">
                        @error('notes')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Items Section -->
                <div class="border-t pt-6 mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Produk yang Ditransfer</h3>
                        <button type="button" id="addItemBtn"
                                class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition text-sm">
                            <i class="fas fa-plus mr-2"></i>Tambah Produk
                        </button>
                    </div>

                    <div id="itemsContainer" class="space-y-3">
                        <!-- Items will be added here dynamically -->
                    </div>

                    @error('items')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit Buttons -->
                <div class="flex gap-3 pt-6 border-t">
                    <button type="submit" class="flex-1 bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition font-semibold">
                        <i class="fas fa-check mr-2"></i>Simpan Transfer
                    </button>
                    <a href="{{ route('admin.stock-transfers.index') }}" class="bg-gray-200 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-300 transition font-semibold">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let itemIndex = 0;
    const itemsContainer = document.getElementById('itemsContainer');
    const addItemBtn = document.getElementById('addItemBtn');
    const fromOutletSelect = document.getElementById('from_outlet_id');
    const products = @json($products);

    // Add first item by default
    addItem();

    addItemBtn.addEventListener('click', addItem);

    function addItem() {
        const itemHtml = `
            <div class="flex gap-3 items-start bg-gray-50 p-4 rounded-lg item-row" data-index="${itemIndex}">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Produk</label>
                    <select name="items[${itemIndex}][product_id]" class="product-select w-full border-gray-300 rounded-lg" required>
                        <option value="">Pilih Produk</option>
                        ${products.map(p => `<option value="${p.id}">${p.name} - ${p.sku || 'No SKU'}</option>`).join('')}
                    </select>
                </div>
                <div class="w-32">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                    <input type="number" name="items[${itemIndex}][quantity]" step="0.01" min="0.01" required
                           class="quantity-input w-full border-gray-300 rounded-lg" placeholder="0">
                </div>
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Stok Tersedia</label>
                    <div class="available-stock text-sm text-gray-600 bg-white border border-gray-300 rounded-lg px-3 py-2">
                        -
                    </div>
                </div>
                <div class="w-24">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                    <input type="text" name="items[${itemIndex}][notes]"
                           class="w-full border-gray-300 rounded-lg" placeholder="Opsional">
                </div>
                <div class="pt-6">
                    <button type="button" class="remove-item-btn text-red-600 hover:text-red-800 p-2">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;

        itemsContainer.insertAdjacentHTML('beforeend', itemHtml);

        const newRow = itemsContainer.lastElementChild;
        const productSelect = newRow.querySelector('.product-select');
        const removeBtn = newRow.querySelector('.remove-item-btn');

        // Event listener for product change
        productSelect.addEventListener('change', function() {
            checkAvailableStock(newRow);
        });

        // Event listener for remove button
        removeBtn.addEventListener('click', function() {
            if (itemsContainer.children.length > 1) {
                newRow.remove();
            } else {
                alert('Minimal harus ada 1 produk');
            }
        });

        itemIndex++;
    }

    // Check available stock
    function checkAvailableStock(row) {
        const outletId = fromOutletSelect.value;
        const productId = row.querySelector('.product-select').value;
        const availableStockDiv = row.querySelector('.available-stock');

        if (!outletId || !productId) {
            availableStockDiv.textContent = '-';
            return;
        }

        fetch(`/admin/stock-transfers/available-stock?product_id=${productId}&outlet_id=${outletId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    availableStockDiv.textContent = `${data.available_stock} ${data.unit}`;
                    availableStockDiv.classList.remove('text-red-600');
                    availableStockDiv.classList.add('text-gray-600');

                    if (data.available_stock <= 0) {
                        availableStockDiv.classList.remove('text-gray-600');
                        availableStockDiv.classList.add('text-red-600');
                    }
                }
            })
            .catch(error => console.error('Error:', error));
    }

    // Refresh stock when from outlet changes
    fromOutletSelect.addEventListener('change', function() {
        document.querySelectorAll('.item-row').forEach(row => {
            checkAvailableStock(row);
        });
    });

    // Form validation
    document.getElementById('transferForm').addEventListener('submit', function(e) {
        const fromOutlet = document.getElementById('from_outlet_id').value;
        const toOutlet = document.getElementById('to_outlet_id').value;

        if (fromOutlet === toOutlet) {
            e.preventDefault();
            alert('Outlet pengirim dan penerima tidak boleh sama!');
            return false;
        }

        const items = document.querySelectorAll('.item-row');
        if (items.length === 0) {
            e.preventDefault();
            alert('Minimal harus ada 1 produk!');
            return false;
        }
    });
});
</script>
@endpush
@endsection
