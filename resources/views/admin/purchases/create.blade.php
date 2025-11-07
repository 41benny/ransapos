@extends('layouts.admin')

@section('title', 'Buat Pembelian Baru')
@section('page-title', 'Buat Pembelian Baru')
@section('page-subtitle', 'Isi form untuk membuat pembelian barang dari supplier')

@section('content')
<form action="{{ route('admin.purchases.store') }}" method="POST" id="purchaseForm">
    @csrf
    
    <div class="max-w-6xl space-y-6">
        <!-- Info Pembelian -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="p-6 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Informasi Pembelian</h3>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Outlet -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Outlet Tujuan <span class="text-red-500">*</span>
                    </label>
                    <select name="outlet_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('outlet_id') border-red-500 @enderror">
                        <option value="">Pilih outlet...</option>
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

                <!-- Supplier -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Supplier <span class="text-red-500">*</span>
                    </label>
                    <select name="supplier_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('supplier_id') border-red-500 @enderror">
                        <option value="">Pilih supplier...</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('supplier_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tanggal -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Tanggal Pembelian <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="purchase_date" value="{{ old('purchase_date', date('Y-m-d')) }}" required 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('purchase_date') border-red-500 @enderror">
                    @error('purchase_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Items Pembelian -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Item Pembelian</h3>
                <button type="button" onclick="addItem()" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm transition">
                    + Tambah Item
                </button>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="w-full" id="itemsTable">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Produk</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Qty</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Harga Satuan</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Diskon</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Subtotal</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            <!-- Items will be added here -->
                        </tbody>
                    </table>
                </div>
                <p class="text-sm text-gray-500 mt-4">* Klik "Tambah Item" untuk menambahkan produk</p>
            </div>
        </div>

        <!-- Summary -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <!-- Pajak -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Pajak (Rp)</label>
                            <input type="number" name="tax_amount" value="{{ old('tax_amount', 0) }}" min="0" step="0.01" 
                                   onchange="calculateTotal()" id="taxAmount"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <!-- Diskon Global -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Diskon Global (Rp)</label>
                            <input type="number" name="discount_amount" value="{{ old('discount_amount', 0) }}" min="0" step="0.01" 
                                   onchange="calculateTotal()" id="discountAmount"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <!-- Catatan -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Catatan</label>
                            <textarea name="notes" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    <div class="space-y-3 bg-gray-50 p-6 rounded-lg">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Subtotal Items:</span>
                            <span class="font-semibold" id="displaySubtotal">Rp 0</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Pajak:</span>
                            <span class="font-semibold" id="displayTax">Rp 0</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Diskon:</span>
                            <span class="font-semibold text-red-600" id="displayDiscount">Rp 0</span>
                        </div>
                        <div class="border-t border-gray-300 pt-3 flex justify-between">
                            <span class="text-lg font-semibold text-gray-900">Total:</span>
                            <span class="text-xl font-bold text-indigo-600" id="displayTotal">Rp 0</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end space-x-3">
            <a href="{{ route('admin.purchases.index') }}" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                Batal
            </a>
            <button type="submit" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition">
                Simpan Pembelian
            </button>
        </div>
    </div>
</form>

<script>
let itemIndex = 0;
const products = @json($categories->flatMap->products);

function addItem() {
    const tbody = document.getElementById('itemsBody');
    const row = document.createElement('tr');
    row.className = 'border-b';
    row.id = `item-${itemIndex}`;
    
    row.innerHTML = `
        <td class="px-4 py-3">
            <select name="items[${itemIndex}][product_id]" required onchange="updateItemPrice(${itemIndex})" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="">Pilih produk...</option>
                ${products.map(p => `<option value="${p.id}" data-price="${p.purchase_price}">${p.name} (${p.sku})</option>`).join('')}
            </select>
        </td>
        <td class="px-4 py-3">
            <input type="number" name="items[${itemIndex}][quantity]" value="1" min="0.01" step="0.01" required
                   onchange="calculateItemSubtotal(${itemIndex})"
                   class="w-24 px-3 py-2 border border-gray-300 rounded-lg text-sm" id="qty-${itemIndex}">
        </td>
        <td class="px-4 py-3">
            <input type="number" name="items[${itemIndex}][unit_price]" value="0" min="0" step="0.01" required
                   onchange="calculateItemSubtotal(${itemIndex})"
                   class="w-32 px-3 py-2 border border-gray-300 rounded-lg text-sm" id="price-${itemIndex}">
        </td>
        <td class="px-4 py-3">
            <input type="number" name="items[${itemIndex}][discount_amount]" value="0" min="0" step="0.01"
                   onchange="calculateItemSubtotal(${itemIndex})"
                   class="w-28 px-3 py-2 border border-gray-300 rounded-lg text-sm" id="discount-${itemIndex}">
        </td>
        <td class="px-4 py-3">
            <span class="font-semibold text-gray-900" id="subtotal-${itemIndex}">Rp 0</span>
        </td>
        <td class="px-4 py-3 text-center">
            <button type="button" onclick="removeItem(${itemIndex})" 
                    class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </button>
        </td>
    `;
    
    tbody.appendChild(row);
    itemIndex++;
}

function updateItemPrice(index) {
    const select = document.querySelector(`select[name="items[${index}][product_id]"]`);
    const priceInput = document.getElementById(`price-${index}`);
    const selectedOption = select.options[select.selectedIndex];
    const price = selectedOption.getAttribute('data-price') || 0;
    priceInput.value = price;
    calculateItemSubtotal(index);
}

function calculateItemSubtotal(index) {
    const qty = parseFloat(document.getElementById(`qty-${index}`).value) || 0;
    const price = parseFloat(document.getElementById(`price-${index}`).value) || 0;
    const discount = parseFloat(document.getElementById(`discount-${index}`).value) || 0;
    
    const subtotal = (qty * price) - discount;
    document.getElementById(`subtotal-${index}`).textContent = formatRupiah(subtotal);
    
    calculateTotal();
}

function removeItem(index) {
    document.getElementById(`item-${index}`).remove();
    calculateTotal();
}

function calculateTotal() {
    let subtotal = 0;
    const tbody = document.getElementById('itemsBody');
    const rows = tbody.getElementsByTagName('tr');
    
    for (let row of rows) {
        const qtyInput = row.querySelector('input[name*="[quantity]"]');
        const priceInput = row.querySelector('input[name*="[unit_price]"]');
        const discountInput = row.querySelector('input[name*="[discount_amount]"]');
        
        if (qtyInput && priceInput) {
            const qty = parseFloat(qtyInput.value) || 0;
            const price = parseFloat(priceInput.value) || 0;
            const discount = parseFloat(discountInput.value) || 0;
            subtotal += (qty * price) - discount;
        }
    }
    
    const tax = parseFloat(document.getElementById('taxAmount').value) || 0;
    const discount = parseFloat(document.getElementById('discountAmount').value) || 0;
    const total = subtotal + tax - discount;
    
    document.getElementById('displaySubtotal').textContent = formatRupiah(subtotal);
    document.getElementById('displayTax').textContent = formatRupiah(tax);
    document.getElementById('displayDiscount').textContent = formatRupiah(discount);
    document.getElementById('displayTotal').textContent = formatRupiah(total);
}

function formatRupiah(amount) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
}

// Add first item on load
document.addEventListener('DOMContentLoaded', function() {
    addItem();
});
</script>
@endsection
